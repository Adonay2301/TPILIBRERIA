<?php
/**
 * Pedidos.
 * - Cliente: confirmar y crear la compra desde el carrito.
 * - Panel: listado, detalle, cambio de estado y asignación.
 *   El administrador puede todo; el empleado se asigna pedidos pendientes y avanza solo los suyos, sin cancelar.
 */

class PedidoController extends Controller
{
    public const MOTIVOS_CANCELACION = ['Sin existencias', 'Solicitud del cliente', 'Dirección incorrecta', 'Otro'];

    // =================================================================
    // Cliente
    // =================================================================

    /** GET /pedido/confirmar */
    public function confirmar(): void
    {
        Auth::requerirRol(['cliente']);
        $idCliente = Auth::idPerfil();
        $carrito = $this->modelo('Carrito');
        $items = $carrito->items($idCliente);

        if (!$items) {
            flash('info', 'Tu carrito está vacío.');
            $this->redirigir('/carrito');
        }

        $this->vista('pedidos/confirmar', [
            'titulo'        => 'Confirmar pedido',
            'items'         => $items,
            'resumen'       => $carrito->resumen($items),
            'direcciones'   => $this->modelo('Direccion')->delCliente($idCliente),
            'departamentos' => $this->modelo('Ubicacion')->departamentos(),
            'cliente'       => $this->modelo('Cliente')->obtener($idCliente),
            'paypal'        => PayPal::configurado(),
            'paypalSimulado' => PayPal::esSimulado(),
            'scripts'       => match (true) {
                PayPal::esSimulado()   => ['js/paypal.js'],
                PayPal::configurado()  => [PayPal::urlSdk(), 'js/paypal.js'],
                default                => [],
            },
        ]);
    }

    /** POST /pedido/crear — compra con pago contra entrega */
    public function crear(): void
    {
        Auth::requerirRol(['cliente']);
        $this->validarCsrf();
        $idCliente = Auth::idPerfil();

        [$entrega, $errores] = $this->datosEntrega($idCliente);
        if ($errores) {
            $this->recordarEntrada();
            foreach ($errores as $error) flash('danger', $error);
            $this->redirigir('/pedido/confirmar');
        }

        try {
            $idPedido = $this->modelo('Pedido')->crearDesdeCarrito($idCliente, $entrega);
        } catch (DomainException $e) {
            flash('danger', $e->getMessage());
            $this->redirigir('/carrito');
        }

        $this->guardarDireccionNueva($idCliente, $entrega, $_POST);
        flash('success', '¡Gracias por tu compra! Tu pedido ' . codigoPedido($idPedido) . ' fue registrado.');
        $this->redirigir('/seguimiento/' . $idPedido);
    }

    /**
     * POST /pago/paypal/orden (JSON) — paso 1 de PayPal.
     * Valida el formulario y crea en PayPal una orden por el total del carrito.
     * Los datos de entrega quedan en la sesión hasta que el cliente apruebe el pago.
     */
    public function paypalOrden(): void
    {
        Auth::requerirRol(['cliente']);
        $this->validarCsrf();
        $idCliente = Auth::idPerfil();

        if (!PayPal::configurado()) {
            $this->json(['ok' => false, 'mensaje' => 'El pago con PayPal no está disponible por ahora.'], 503);
        }

        [$entrega, $errores] = $this->datosEntrega($idCliente);
        if ($errores) {
            $this->json(['ok' => false, 'mensaje' => implode(' ', $errores)], 422);
        }

        $carrito = $this->modelo('Carrito');
        $items = $carrito->items($idCliente);
        if (!$items) {
            $this->json(['ok' => false, 'mensaje' => 'Tu carrito está vacío.'], 422);
        }
        foreach ($items as $item) {
            if (!$item['activo'] || (int) $item['stock_actual'] < (int) $item['cantidad']) {
                $this->json(['ok' => false, 'mensaje' => "No hay suficientes unidades de «{$item['titulo']}». Ajusta la cantidad en tu carrito."], 422);
            }
        }
        $resumen = $carrito->resumen($items);

        try {
            $orden = PayPal::crearOrden(
                $resumen['total'],
                'cliente-' . $idCliente,
                APP_NOMBRE . ': ' . $resumen['articulos'] . ' libro(s)'
            );
        } catch (RuntimeException $e) {
            error_log($e->getMessage());
            $this->json(['ok' => false, 'mensaje' => 'No pudimos comunicarnos con PayPal. Inténtalo de nuevo en unos minutos.'], 502);
        }

        $_SESSION['paypal_pendiente'] = [
            'orden'     => $orden,
            'monto'     => $resumen['total'],
            'entrega'   => $entrega,
            'formulario' => array_intersect_key($_POST, array_flip(['id_direccion', 'guardar_direccion', 'alias'])),
        ];
        $this->json(['ok' => true, 'id' => $orden]);
    }

    /**
     * POST /pago/paypal/capturar (JSON) — paso 2 de PayPal, después de que el cliente aprueba.
     * Crea el pedido y cobra la orden dentro de la misma transacción: si el cobro falla,
     * el pedido no se guarda; si el pedido falla después de cobrar, se reembolsa.
     */
    public function paypalCapturar(): void
    {
        Auth::requerirRol(['cliente']);
        $this->validarCsrf();
        $idCliente = Auth::idPerfil();

        $pendiente = $_SESSION['paypal_pendiente'] ?? null;
        $ordenId = $this->entrada('orden_id');
        if (!$pendiente || !hash_equals($pendiente['orden'], $ordenId)) {
            $this->json(['ok' => false, 'mensaje' => 'El pago no coincide con tu compra. Vuelve a intentarlo.'], 422);
        }

        $pedidos = $this->modelo('Pedido');
        $captura = null;

        try {
            $idPedido = $pedidos->crearDesdeCarrito($idCliente, $pendiente['entrega'], function (int $idPedido, float $total) use ($pendiente, &$captura) {
                // El carrito pudo cambiar en otra pestaña después de abrir PayPal
                if (!PayPal::mismoMonto($total, $pendiente['monto'])) {
                    throw new DomainException('El total de tu carrito cambió mientras pagabas. No se hizo ningún cobro; vuelve a pagar.');
                }

                $captura = PayPal::capturar($pendiente['orden']);

                if (!in_array($captura['estado'], ['COMPLETED', 'PENDING'], true)) {
                    throw new DomainException('PayPal no aprobó el pago. Elige otro medio e inténtalo de nuevo.');
                }
                if (!PayPal::mismoMonto($captura['monto'], $total) || $captura['moneda'] !== PayPal::moneda()) {
                    throw new RuntimeException("Monto capturado distinto al del pedido en la orden {$pendiente['orden']}.");
                }

                return [
                    'metodo'            => 'paypal',
                    'estado'            => $captura['estado'] === 'COMPLETED' ? 'completado' : 'pendiente',
                    'paypal_orden_id'   => $pendiente['orden'],
                    'paypal_captura_id' => $captura['captura_id'],
                    'paypal_correo'     => $captura['correo'],
                ];
            });
        } catch (Throwable $e) {
            // Si ya se cobró pero el pedido no se guardó, se devuelve el dinero
            $reembolsado = $captura && !empty($captura['captura_id']) && $this->reembolsarHuerfano($captura['captura_id']);

            if ($e instanceof DomainException) {
                $this->json(['ok' => false, 'mensaje' => $e->getMessage(), 'reintentar' => str_contains($e->getMessage(), 'medio')], 422);
            }
            error_log((string) $e);
            $this->json(['ok' => false, 'mensaje' => $reembolsado
                ? 'No pudimos registrar tu pedido. El cobro en PayPal fue reembolsado.'
                : 'No pudimos completar el pago con PayPal. Inténtalo de nuevo.'], 502);
        }

        unset($_SESSION['paypal_pendiente']);
        $this->guardarDireccionNueva($idCliente, $pendiente['entrega'], $pendiente['formulario']);

        $mensaje = $captura['estado'] === 'COMPLETED'
            ? '¡Pago recibido! Tu pedido ' . codigoPedido($idPedido) . ' fue registrado.'
            : 'Tu pedido ' . codigoPedido($idPedido) . ' fue registrado. PayPal está revisando el pago.';
        flash('success', $mensaje);
        $this->json(['ok' => true, 'redirigir' => url('/seguimiento/' . $idPedido)]);
    }

    /**
     * POST /pago/paypal/simulador/aprobar (JSON) — solo en modo simulado.
     * Hace lo que en PayPal real hace el cliente en la ventana de PayPal: aprobar la orden.
     */
    public function paypalSimuladorAprobar(): void
    {
        Auth::requerirRol(['cliente']);
        $this->validarCsrf();
        if (!PayPal::esSimulado()) {
            throw new HttpError(404);
        }

        $correo = filter_var($this->entrada('correo'), FILTER_VALIDATE_EMAIL);
        if (!$correo || $this->entrada('contrasena') === '') {
            $this->json(['ok' => false, 'mensaje' => 'Escribe el correo y la contraseña de tu cuenta PayPal.'], 422);
        }

        try {
            PayPal::simAprobar($this->entrada('orden_id'), mb_substr($correo, 0, 120), $this->entrada('rechazar') === '1');
        } catch (DomainException $e) {
            $this->json(['ok' => false, 'mensaje' => $e->getMessage()], 422);
        }
        $this->json(['ok' => true]);
    }

    /** Reembolsa un cobro que no quedó asociado a ningún pedido. */
    private function reembolsarHuerfano(string $capturaId): bool
    {
        try {
            PayPal::reembolsar($capturaId, 'No se pudo registrar el pedido');
            return true;
        } catch (RuntimeException $e) {
            error_log('REEMBOLSO PENDIENTE (captura ' . $capturaId . '): ' . $e->getMessage());
            return false;
        }
    }

    /** Valida dirección y teléfono del formulario de compra. Devuelve [entrega, errores]. */
    private function datosEntrega(int $idCliente): array
    {
        $errores = [];
        $entrega = [];

        // Dirección guardada o nueva
        if ($this->entrada('id_direccion') !== 'nueva') {
            $dir = $this->modelo('Direccion')->obtenerDeCliente((int) $this->entrada('id_direccion'), $idCliente);
            if (!$dir) {
                $errores[] = 'Selecciona una dirección de entrega.';
            } else {
                $entrega = ['direccion' => $dir['direccion'], 'referencia' => $dir['referencia'], 'id_distrito' => (int) $dir['id_distrito']];
            }
        } else {
            $entrega = [
                'direccion'   => mb_substr($this->entrada('direccion'), 0, 255),
                'referencia'  => mb_substr($this->entrada('referencia'), 0, 255),
                'id_distrito' => (int) $this->entrada('id_distrito'),
            ];
            if ($entrega['direccion'] === '') $errores[] = 'Escribe la dirección de entrega.';
            if (!$this->modelo('Ubicacion')->distrito($entrega['id_distrito'])) $errores[] = 'Selecciona departamento, municipio y distrito.';
        }

        $telefono = telefonoSv($this->entrada('telefono'));
        if (!telefonoValido($telefono)) {
            $errores[] = 'El teléfono de contacto debe tener el formato 7123-4567.';
        }

        return [$entrega + ['telefono' => $telefono, 'notas' => mb_substr($this->entrada('notas'), 0, 255)], $errores];
    }

    /** Guarda la dirección nueva en la libreta si el cliente lo pidió. */
    private function guardarDireccionNueva(int $idCliente, array $entrega, array $formulario): void
    {
        if (($formulario['id_direccion'] ?? '') === 'nueva' && ($formulario['guardar_direccion'] ?? '') === '1') {
            $this->modelo('Direccion')->crear($idCliente, $entrega + ['alias' => mb_substr(trim($formulario['alias'] ?? ''), 0, 40)]);
        }
    }

    // =================================================================
    // Panel
    // =================================================================

    /** GET /admin/pedidos */
    public function index(): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);

        $filtros = [
            'q'      => mb_substr($this->parametro('q'), 0, 100),
            'estado' => $this->parametro('estado'),
            'desde'  => $this->parametroFecha('desde', ''),
            'hasta'  => $this->parametroFecha('hasta', ''),
        ];
        $pedidos = $this->modelo('Pedido');
        $paginacion = paginar($pedidos->contar($filtros), POR_PAGINA_ADMIN);

        $this->vista('admin/pedidos/index', [
            'titulo'     => 'Gestión de pedidos',
            'filtros'    => $filtros,
            'pedidos'    => $pedidos->listar($filtros, $paginacion['por_pagina'], $paginacion['offset']),
            'paginacion' => $paginacion,
            'contadores' => $pedidos->contadores(),
            'esAdmin'    => Auth::esAdmin(),
            'miIdEmpleado' => Auth::esRol('empleado') ? Auth::idPerfil() : null,
        ], 'admin');
    }

    /** GET /admin/pedidos/{id} */
    public function ver(int $id): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);
        $pedidos = $this->modelo('Pedido');
        $pedido = $pedidos->obtener($id);
        if (!$pedido) {
            throw new HttpError(404);
        }

        $this->vista('admin/pedidos/ver', [
            'titulo'     => 'Pedido ' . codigoPedido($id),
            'pedido'     => $pedido,
            'detalles'   => $pedidos->detalles($id),
            'historial'  => $pedidos->historial($id),
            'estados'    => $this->estadosPermitidos($pedido),
            'puedeTomar' => Auth::esRol('empleado') && $pedido['estado'] === 'pendiente' && $pedido['id_empleado'] === null,
            'empleados'  => Auth::esAdmin() ? $this->modelo('Empleado')->activos() : [],
            'motivos'    => self::MOTIVOS_CANCELACION,
            'esAdmin'    => Auth::esAdmin(),
        ], 'admin');
    }

    /** POST /admin/pedidos/{id}/estado */
    public function cambiarEstado(int $id): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);
        $this->validarCsrf();

        $pedidos = $this->modelo('Pedido');
        $pedido = $pedidos->obtener($id);
        if (!$pedido) {
            throw new HttpError(404);
        }

        $nuevo = $this->entrada('estado');
        if (!in_array($nuevo, $this->estadosPermitidos($pedido), true)) {
            throw new HttpError(403);
        }

        // Comentario del historial: motivo de cancelación + nota interna
        $comentario = trim(implode(' · ', array_filter([
            $nuevo === 'cancelado' ? $this->entrada('motivo') : '',
            mb_substr($this->entrada('comentario'), 0, 200),
        ])));
        if ($nuevo === 'cancelado' && !in_array($this->entrada('motivo'), self::MOTIVOS_CANCELACION, true)) {
            $this->fallar('Selecciona el motivo de la cancelación.', $id);
        }

        // Quién queda a cargo al pasar a preparación
        $idEmpleado = null;
        if ($nuevo === 'en_preparacion') {
            $idEmpleado = Auth::esRol('empleado') ? Auth::idPerfil() : ((int) $this->entrada('id_empleado') ?: null);
        }

        try {
            $pedidos->cambiarEstado($id, $nuevo, $comentario, Auth::id(), $idEmpleado);
        } catch (DomainException $e) {
            $this->fallar($e->getMessage(), $id);
        }

        $this->exito('Estado actualizado a «' . estadoPedido($nuevo)['texto'] . '».', $id);
    }

    /** POST /admin/pedidos/{id}/asignar */
    public function asignar(int $id): void
    {
        Auth::requerirRol(Auth::ROLES_PANEL);
        $this->validarCsrf();

        $pedidos = $this->modelo('Pedido');
        $pedido = $pedidos->obtener($id);
        if (!$pedido) {
            throw new HttpError(404);
        }

        try {
            if (Auth::esRol('empleado')) {
                // "Asignarme": solo pedidos pendientes sin empleado; pasa a preparación a su nombre
                if ($pedido['estado'] !== 'pendiente' || $pedido['id_empleado'] !== null) {
                    $this->fallar('Este pedido ya fue tomado por otra persona.', $id);
                }
                $pedidos->cambiarEstado($id, 'en_preparacion', 'Pedido asignado para preparación', Auth::id(), Auth::idPerfil());
                $this->exito('Pedido ' . codigoPedido($id) . ' asignado a ti.', $id);
            }

            // Administrador: asigna o quita el empleado responsable
            if (in_array($pedido['estado'], ['entregado', 'cancelado'], true)) {
                $this->fallar('No se puede reasignar un pedido cerrado.', $id);
            }
            $idEmpleado = (int) $this->entrada('id_empleado') ?: null;
            $pedidos->asignar($id, $idEmpleado);
        } catch (DomainException $e) {
            $this->fallar($e->getMessage(), $id);
        }

        $this->exito($idEmpleado ? 'Empleado asignado.' : 'Se quitó la asignación.', $id);
    }

    /**
     * Estados a los que el usuario actual puede llevar el pedido.
     * Empleado: nunca cancela; solo avanza pedidos que son suyos (o toma pendientes sin asignar).
     */
    private function estadosPermitidos(array $pedido): array
    {
        $siguientes = PedidoModel::TRANSICIONES[$pedido['estado']];
        if (Auth::esAdmin()) {
            return $siguientes;
        }

        $esSuyo = (int) $pedido['id_empleado'] === Auth::idPerfil();
        $libre = $pedido['estado'] === 'pendiente' && $pedido['id_empleado'] === null;
        if (!$esSuyo && !$libre) {
            return [];
        }
        return array_values(array_diff($siguientes, ['cancelado']));
    }

    private function exito(string $mensaje, int $id): never
    {
        if (esPeticionJson()) {
            $this->json(['ok' => true, 'mensaje' => $mensaje]);
        }
        flash('success', $mensaje);
        $this->volver('/admin/pedidos/' . $id);
    }

    private function fallar(string $mensaje, int $id): never
    {
        if (esPeticionJson()) {
            $this->json(['ok' => false, 'mensaje' => $mensaje], 422);
        }
        flash('danger', $mensaje);
        $this->volver('/admin/pedidos/' . $id);
    }
}
