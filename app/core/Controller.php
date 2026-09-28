<?php
/**
 * Clase base de los controladores: cargar vistas y modelos, redirigir,
 * responder JSON y validar el token CSRF.
 * Los controladores no tienen SQL ni HTML.
 */

abstract class Controller
{
    /**
     * Renderiza una vista dentro de un layout.
     * $vista: ruta relativa a app/views sin .php (ej. 'catalogo/index')
     * $layout: 'publico', 'admin' o 'simple'
     */
    protected function vista(string $vista, array $datos = [], string $layout = 'publico'): void
    {
        $archivo = RUTA_VISTAS . "/$vista.php";
        if (!is_file($archivo)) {
            throw new RuntimeException("La vista '$vista' no existe.");
        }

        $datos += $this->datosGlobales();
        extract($datos, EXTR_SKIP);

        // La vista se genera primero y el layout la envuelve en $contenido
        ob_start();
        require $archivo;
        $contenido = ob_get_clean();

        require RUTA_VISTAS . "/layouts/$layout.php";
    }

    /** Crea un modelo: $this->modelo('Libro') -> new LibroModel() */
    protected function modelo(string $nombre): Model
    {
        $clase = $nombre . 'Model';
        return new $clase();
    }

    protected function redirigir(string $ruta): never
    {
        header('Location: ' . url($ruta));
        exit;
    }

    /** Regresa a la página anterior (si es de este mismo sitio) o a la ruta indicada. */
    protected function volver(string $porDefecto = '/'): never
    {
        $anterior = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';

        if ($anterior !== '' && parse_url($anterior, PHP_URL_HOST) === explode(':', $host)[0]) {
            header('Location: ' . $anterior);
            exit;
        }
        $this->redirigir($porDefecto);
    }

    protected function json(array $datos, int $codigo = 200): never
    {
        responderJson($datos, $codigo);
    }

    /** Detiene la petición si el token CSRF no coincide. */
    protected function validarCsrf(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (csrf_valido($token)) {
            return;
        }

        if (esPeticionJson()) {
            $this->json(['ok' => false, 'mensaje' => 'La página expiró. Recárgala e inténtalo de nuevo.'], 419);
        }
        flash('danger', 'La página expiró. Vuelve a enviar el formulario.');
        $this->volver();
    }

    /** Valor de un campo POST, sin espacios al inicio ni al final. */
    protected function entrada(string $campo, string $defecto = ''): string
    {
        $valor = $_POST[$campo] ?? $defecto;
        return is_string($valor) ? trim($valor) : $defecto;
    }

    /** Valor de un parámetro GET. */
    protected function parametro(string $campo, string $defecto = ''): string
    {
        $valor = $_GET[$campo] ?? $defecto;
        return is_string($valor) ? trim($valor) : $defecto;
    }

    /** Lista de ids positivos de un parámetro GET múltiple: ?categoria[]=1&categoria[]=4 */
    protected function parametroEnteros(string $campo): array
    {
        $valores = $_GET[$campo] ?? [];
        $valores = is_array($valores) ? $valores : [$valores];
        return array_values(array_unique(array_filter(array_map('intval', $valores), fn ($v) => $v > 0)));
    }

    /** Fecha Y-m-d de un parámetro GET, o el valor por defecto si no es válida. */
    protected function parametroFecha(string $campo, string $defecto): string
    {
        $valor = $this->parametro($campo);
        return fechaValida($valor) ? $valor : $defecto;
    }

    /** Descarga un archivo CSV (Excel lo abre con tildes gracias al BOM UTF-8). */
    protected function descargarCsv(string $nombreArchivo, array $encabezados, array $filas): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, $encabezados);
        foreach ($filas as $fila) {
            fputcsv($salida, $fila);
        }
        fclose($salida);
        exit;
    }

    /** Guarda lo escrito en el formulario para volver a mostrarlo si hubo errores. */
    protected function recordarEntrada(): void
    {
        $datos = $_POST;
        unset($datos['_csrf'], $datos['contrasena'], $datos['contrasena_confirmacion']);
        $_SESSION['old'] = $datos;
    }

    /** Datos que usan todos los layouts (usuario en sesión y artículos en el carrito). */
    private function datosGlobales(): array
    {
        $usuario = Auth::usuario();
        $carritoCantidad = 0;

        if (Auth::esRol('cliente') && class_exists('CarritoModel')) {
            try {
                $carritoCantidad = (new CarritoModel())->contarArticulos(Auth::idPerfil());
            } catch (Throwable $e) {
                // Si la BD falla, la página de error igual debe poder mostrarse
                $carritoCantidad = 0;
            }
        }

        return [
            'usuarioActual'   => $usuario,
            'carritoCantidad' => $carritoCantidad,
        ];
    }
}
