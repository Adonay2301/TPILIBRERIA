<?php
/**
 * Front controller: única entrada del sistema.
 * Todas las peticiones llegan aquí gracias a los .htaccess.
 */

declare(strict_types=1);

// Con el servidor integrado de PHP (php -S) los archivos estáticos se sirven directo
if (PHP_SAPI === 'cli-server') {
    $archivo = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($archivo)) {
        return false;
    }
}

require dirname(__DIR__) . '/app/config/config.php';
require RUTA_APP . '/helpers/helpers.php';

error_reporting(E_ALL);
ini_set('display_errors', MODO_DEBUG ? '1' : '0');

// Carga automática de clases: núcleo, controladores y modelos
spl_autoload_register(function (string $clase): void {
    foreach (['core', 'controllers', 'models'] as $carpeta) {
        $ruta = RUTA_APP . "/$carpeta/$clase.php";
        if (is_file($ruta)) {
            require $ruta;
            return;
        }
    }
});

Auth::iniciar();

// Los triggers de pedidos registran quién hizo cada cambio con @id_usuario_actual
Database::fijarUsuario(Auth::id());

try {
    $router = new Router(require RUTA_APP . '/config/rutas.php');
    $router->despachar($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (HttpError $e) {
    (new ErrorController())->mostrar($e->getCode());
} catch (Throwable $e) {
    error_log((string) $e);
    (new ErrorController())->mostrar(500, MODO_DEBUG ? $e : null);
}
