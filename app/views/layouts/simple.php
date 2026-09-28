<?php
/**
 * Layout sin barra de navegación: login y registro (pantalla dividida del prototipo).
 * Variables: $contenido
 */
?>
<!doctype html>
<html lang="es">
<head>
    <?php require RUTA_VISTAS . '/partials/head.php'; ?>
</head>
<body class="layout-simple">
    <?php require RUTA_VISTAS . '/partials/alertas.php'; ?>
    <?= $contenido ?>
    <?php require RUTA_VISTAS . '/partials/scripts.php'; ?>
</body>
</html>
<?php limpiarOld(); ?>
