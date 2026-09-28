<?php
/**
 * Layout del panel (administrador y empleado): navbar + menú lateral + contenido.
 * Variables: $contenido y las globales del Controller.
 */
?>
<!doctype html>
<html lang="es">
<head>
    <?php require RUTA_VISTAS . '/partials/head.php'; ?>
</head>
<body class="layout-admin" data-rol="<?= e($usuarioActual['rol'] ?? '') ?>">
    <?php require RUTA_VISTAS . '/partials/navbar.php'; ?>
    <?php require RUTA_VISTAS . '/partials/alertas.php'; ?>

    <div class="panel-contenedor">
        <?php require RUTA_VISTAS . '/partials/sidebar.php'; ?>

        <div class="panel-principal">
            <!-- Botón del menú lateral en pantallas pequeñas -->
            <button class="btn btn-pya-suave btn-sm d-lg-none mb-3" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#sidebarPanel" aria-controls="sidebarPanel">
                <i class="bi bi-list me-1"></i>Menú
            </button>

            <?= $contenido ?>
        </div>
    </div>

    <?php require RUTA_VISTAS . '/partials/footer.php'; ?>
    <?php require RUTA_VISTAS . '/partials/scripts.php'; ?>
</body>
</html>
<?php limpiarOld(); ?>
