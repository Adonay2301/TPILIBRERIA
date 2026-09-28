<?php
/**
 * Cliente mínimo de la API REST de PayPal (Orders v2), con cURL.
 *
 * Flujo:
 *   1. crearOrden(): el servidor crea la orden con el monto del carrito.
 *   2. El cliente la aprueba en la ventana de PayPal (JS SDK).
 *   3. capturar(): el servidor cobra la orden aprobada.
 *   4. reembolsar(): devuelve el dinero si el pedido se cancela.
 *
 * El monto SIEMPRE lo decide el servidor; el navegador solo recibe el id de la orden.
 * Credenciales en app/config/paypal.php (plantilla: paypal.example.php).
 *
 * Modo 'simulado': no se conecta a PayPal. Las órdenes viven en la sesión del cliente
 * y se aprueban en una ventana propia que imita PayPal (sin cuenta ni internet).
 * El resto del sistema (pedido, tabla pagos, reembolsos) funciona exactamente igual.
 */

class PayPal
{
    private const URLS = [
        'sandbox' => 'https://api-m.sandbox.paypal.com',
        'live'    => 'https://api-m.paypal.com',
    ];

    private static ?array $cfg = null;

    // -----------------------------------------------------------------
    // Configuración
    // -----------------------------------------------------------------

    private static function cfg(): array
    {
        if (self::$cfg === null) {
            $ruta = RUTA_APP . '/config/paypal.php';
            self::$cfg = is_file($ruta) ? require $ruta : [];
        }
        return self::$cfg;
    }

    /** true en modo simulado, o si hay credenciales reales (no los marcadores de la plantilla). */
    public static function configurado(): bool
    {
        $cfg = self::cfg();
        if (self::esSimulado()) {
            return true;
        }
        foreach (['client_id', 'client_secret'] as $clave) {
            if (empty($cfg[$clave]) || str_starts_with($cfg[$clave], '<')) {
                return false;
            }
        }
        return isset(self::URLS[$cfg['modo'] ?? 'sandbox']);
    }

    public static function clientId(): string
    {
        return self::cfg()['client_id'] ?? '';
    }

    public static function moneda(): string
    {
        return self::cfg()['moneda'] ?? 'USD';
    }

    public static function esSimulado(): bool
    {
        return (self::cfg()['modo'] ?? '') === 'simulado';
    }

    /** URL del JS SDK que dibuja los botones de PayPal. */
    public static function urlSdk(): string
    {
        return 'https://www.paypal.com/sdk/js?' . http_build_query([
            'client-id'       => self::clientId(),
            'currency'        => self::moneda(),
            'intent'          => 'capture',
            'components'      => 'buttons',
            'disable-funding' => 'paylater,venmo',
        ]);
    }

    // -----------------------------------------------------------------
    // Operaciones
    // -----------------------------------------------------------------

    /** Crea una orden por $monto y devuelve su id. */
    public static function crearOrden(float $monto, string $referencia, string $descripcion): string
    {
        if (self::esSimulado()) {
            return self::simCrearOrden($monto, $descripcion);
        }

        [$codigo, $r] = self::peticion('POST', '/v2/checkout/orders', [
            'intent'         => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $referencia,
                'description'  => mb_substr($descripcion, 0, 127),
                'amount'       => ['currency_code' => self::moneda(), 'value' => self::formatoMonto($monto)],
            ]],
            'application_context' => [
                'brand_name'          => APP_NOMBRE,
                'shipping_preference' => 'NO_SHIPPING', // la dirección se toma de nuestro formulario
                'user_action'         => 'PAY_NOW',
            ],
        ]);

        if ($codigo !== 201 || empty($r['id'])) {
            throw new RuntimeException('PayPal no pudo crear la orden: ' . self::describirError($r));
        }
        return $r['id'];
    }

    /**
     * Cobra una orden aprobada por el cliente.
     * Devuelve: estado (COMPLETED, PENDING…), captura_id, monto, moneda, correo.
     * Lanza DomainException si el cliente debe elegir otro medio de pago (tarjeta rechazada).
     */
    public static function capturar(string $ordenId): array
    {
        if (self::esSimulado()) {
            return self::simCapturar($ordenId);
        }

        // PayPal-Request-Id hace la captura idempotente: repetirla no cobra dos veces
        [$codigo, $r] = self::peticion('POST', '/v2/checkout/orders/' . rawurlencode($ordenId) . '/capture', null, [
            'PayPal-Request-Id: captura-' . $ordenId,
        ]);

        if ($codigo === 422 && (($r['details'][0]['issue'] ?? '') === 'INSTRUMENT_DECLINED')) {
            throw new DomainException('PayPal rechazó el medio de pago. Elige otro e inténtalo de nuevo.');
        }
        if ($codigo < 200 || $codigo >= 300) {
            throw new RuntimeException('PayPal no pudo cobrar la orden: ' . self::describirError($r));
        }

        $captura = $r['purchase_units'][0]['payments']['captures'][0] ?? [];
        return [
            'estado'     => $captura['status'] ?? ($r['status'] ?? ''),
            'captura_id' => $captura['id'] ?? null,
            'monto'      => (float) ($captura['amount']['value'] ?? 0),
            'moneda'     => $captura['amount']['currency_code'] ?? '',
            'correo'     => $r['payer']['email_address'] ?? null,
        ];
    }

    /** Reembolso total de una captura. Devuelve el estado del reembolso (COMPLETED, PENDING…). */
    public static function reembolsar(string $capturaId, string $nota = ''): string
    {
        if (self::esSimulado()) {
            // El reembolso lo hace el administrador desde otra sesión: no hay orden que buscar
            return 'COMPLETED';
        }

        [$codigo, $r] = self::peticion(
            'POST',
            '/v2/payments/captures/' . rawurlencode($capturaId) . '/refund',
            $nota !== '' ? ['note_to_payer' => mb_substr($nota, 0, 255)] : new stdClass(),
            ['PayPal-Request-Id: reembolso-' . $capturaId]
        );

        if ($codigo < 200 || $codigo >= 300) {
            throw new RuntimeException('PayPal no pudo reembolsar el pago: ' . self::describirError($r));
        }
        return $r['status'] ?? '';
    }

    /** Compara dos montos en centavos (evita errores de punto flotante). */
    public static function mismoMonto(float $a, float $b): bool
    {
        return (int) round($a * 100) === (int) round($b * 100);
    }

    // -----------------------------------------------------------------
    // Modo simulado (órdenes guardadas en $_SESSION['paypal_sim'])
    // -----------------------------------------------------------------

    private static function simCrearOrden(float $monto, string $descripcion): string
    {
        $id = 'SIM' . strtoupper(bin2hex(random_bytes(6)));
        $_SESSION['paypal_sim'][$id] = [
            'estado'      => 'CREATED',
            'monto'       => round($monto, 2),
            'descripcion' => $descripcion,
            'correo'      => null,
            'captura'     => null,
        ];
        return $id;
    }

    /**
     * Ventana simulada: el cliente aprueba la orden, o elige "rechazar" para probar
     * qué pasa cuando PayPal no acepta el medio de pago.
     */
    public static function simAprobar(string $ordenId, string $correo, bool $rechazar): void
    {
        $orden = $_SESSION['paypal_sim'][$ordenId] ?? null;
        if (!$orden || $orden['estado'] === 'COMPLETED') {
            throw new DomainException('La orden de PayPal no existe o ya fue pagada.');
        }
        $_SESSION['paypal_sim'][$ordenId]['estado'] = $rechazar ? 'DECLINED' : 'APPROVED';
        $_SESSION['paypal_sim'][$ordenId]['correo'] = $correo;
    }

    private static function simCapturar(string $ordenId): array
    {
        $orden = $_SESSION['paypal_sim'][$ordenId] ?? null;
        if (!$orden) {
            throw new RuntimeException("Orden simulada $ordenId no encontrada.");
        }
        if ($orden['estado'] === 'DECLINED') {
            throw new DomainException('PayPal rechazó el medio de pago. Elige otro e inténtalo de nuevo.');
        }
        if ($orden['estado'] === 'CREATED') {
            throw new RuntimeException("Orden simulada $ordenId sin aprobar.");
        }

        // Igual que PayPal: capturar dos veces devuelve la misma captura
        if ($orden['estado'] !== 'COMPLETED') {
            $orden['estado'] = 'COMPLETED';
            $orden['captura'] = 'SIMCAP' . strtoupper(bin2hex(random_bytes(5)));
            $_SESSION['paypal_sim'][$ordenId] = $orden;
        }
        return [
            'estado'     => 'COMPLETED',
            'captura_id' => $orden['captura'],
            'monto'      => $orden['monto'],
            'moneda'     => self::moneda(),
            'correo'     => $orden['correo'],
        ];
    }

    // -----------------------------------------------------------------
    // HTTP
    // -----------------------------------------------------------------

    /** Token OAuth2; se guarda en la sesión hasta un minuto antes de vencer. */
    private static function token(): string
    {
        $guardado = $_SESSION['paypal_token'] ?? null;
        if ($guardado && $guardado['vence'] > time()) {
            return $guardado['valor'];
        }

        $cfg = self::cfg();
        [$codigo, $r] = self::curl('POST', '/v1/oauth2/token', 'grant_type=client_credentials', [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($cfg['client_id'] . ':' . $cfg['client_secret']),
        ]);

        if ($codigo !== 200 || empty($r['access_token'])) {
            throw new RuntimeException('No se pudo autenticar con PayPal. Revisa el Client ID y el Secret en app/config/paypal.php.');
        }
        $_SESSION['paypal_token'] = ['valor' => $r['access_token'], 'vence' => time() + (int) ($r['expires_in'] ?? 300) - 60];
        return $r['access_token'];
    }

    private static function peticion(string $metodo, string $ruta, array|stdClass|null $cuerpo = null, array $cabeceras = []): array
    {
        if (!self::configurado()) {
            throw new RuntimeException('PayPal no está configurado (app/config/paypal.php).');
        }
        return self::curl($metodo, $ruta, $cuerpo === null ? '{}' : json_encode($cuerpo), array_merge([
            'Content-Type: application/json',
            'Authorization: Bearer ' . self::token(),
        ], $cabeceras));
    }

    /** Devuelve [código HTTP, respuesta JSON decodificada]. */
    private static function curl(string $metodo, string $ruta, string $cuerpo, array $cabeceras): array
    {
        $ch = curl_init(self::URLS[self::cfg()['modo'] ?? 'sandbox'] . $ruta);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $metodo,
            CURLOPT_POSTFIELDS     => $cuerpo,
            CURLOPT_HTTPHEADER     => array_merge(['Accept: application/json'], $cabeceras),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $respuesta = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($respuesta === false) {
            throw new RuntimeException('No hay conexión con PayPal: ' . $error);
        }
        return [$codigo, json_decode($respuesta, true) ?? []];
    }

    private static function formatoMonto(float $monto): string
    {
        return number_format($monto, 2, '.', '');
    }

    private static function describirError(array $r): string
    {
        $detalle = $r['details'][0]['issue'] ?? $r['name'] ?? $r['error'] ?? 'error desconocido';
        return $detalle . (isset($r['debug_id']) ? " (debug_id {$r['debug_id']})" : '');
    }
}
