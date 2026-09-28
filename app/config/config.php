<?php
/**
 * Configuración general del sistema Punto y Aparte.
 */

define('APP_NOMBRE', 'Punto y Aparte');
define('APP_ETIQUETA', 'Librería · San Salvador');
define('MODO_DEBUG', true); // Poner en false al publicar: oculta el detalle de los errores

date_default_timezone_set('America/El_Salvador');

// Rutas del sistema de archivos
define('RUTA_APP', dirname(__DIR__));
define('RUTA_RAIZ', dirname(RUTA_APP));
define('RUTA_VISTAS', RUTA_APP . '/views');

// BASE_URL se calcula a partir de la ubicación de public/index.php, así el sistema
// funciona igual en http://localhost/punto-y-aparte o en la raíz de un dominio.
// Ejemplo: /punto-y-aparte/public/index.php  ->  BASE_URL = /punto-y-aparte
$directorioScript = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
define('BASE_URL', preg_replace('#/public$#', '', rtrim($directorioScript, '/')));
unset($directorioScript);

// Paginación por defecto
define('POR_PAGINA', 9);
define('POR_PAGINA_ADMIN', 10);

// Regla de envío. SOLO para mostrar textos y la vista previa del carrito:
// el monto real del pedido lo calculan los triggers de la base de datos.
define('ENVIO_COSTO', 4.00);
define('ENVIO_GRATIS_DESDE', 50.00);
