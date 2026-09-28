<?php
/**
 * Layout de la tienda: catálogo, novedades, carrito y seguimiento.
 * Variables: $contenido (HTML de la vista) y las globales del Controller.
 */
?>
<!doctype html>
<html lang="es">
<head>
    <?php require RUTA_VISTAS . '/partials/head.php'; ?>
</head>
<body class="layout-publico" data-rol="<?= e($usuarioActual['rol'] ?? '') ?>">
    <?php require RUTA_VISTAS . '/partials/navbar.php'; ?>
    <?php require RUTA_VISTAS . '/partials/alertas.php'; ?>

    <main class="contenido-publico">
        <?= $contenido ?>
    </main>

    <?php require RUTA_VISTAS . '/partials/footer.php'; ?>
    <?php if (!$usuarioActual) require RUTA_VISTAS . '/partials/modal_login.php'; ?>
    <?php require RUTA_VISTAS . '/partials/scripts.php'; ?>
</body>
</html>
<?php limpiarOld(); ?>
