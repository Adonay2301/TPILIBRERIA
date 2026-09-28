<?php
/**
 * Barra superior de la tienda y del panel.
 * Variables: $usuarioActual, $carritoCantidad
 */
$esPersonal = $usuarioActual && in_array($usuarioActual['rol'], Auth::ROLES_PANEL, true);
$etiquetasRol = ['cliente' => 'Cliente', 'empleado' => 'Empleado', 'administrador' => 'Administrador'];
?>
<header class="navbar-pya fixed-top">
    <nav class="navbar navbar-expand-lg navbar-dark container-xl px-3 px-md-4">
        <a class="navbar-brand p-0" href="<?= url('/') ?>" aria-label="Inicio">
            <?php $marcaOscura = false; require RUTA_VISTAS . '/partials/marca.php'; ?>
        </a>

        <div class="d-flex align-items-center gap-3 order-lg-last ms-auto ms-lg-0">
            <?php if (!$esPersonal): ?>
                <!-- Carrito: visible para visitantes y clientes -->
                <a href="<?= url('/carrito') ?>" class="icono-carrito" aria-label="Mi carrito">
                    <i class="bi bi-cart3"></i>
                    <?php if ($carritoCantidad > 0): ?>
                        <span class="contador"><?= (int) $carritoCantidad ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <?php if ($usuarioActual): ?>
                <div class="dropdown">
                    <button class="btn usuario-menu dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar"><?= e(iniciales($usuarioActual['nombre'])) ?></span>
                        <span class="d-none d-md-flex flex-column text-start lh-sm">
                            <span class="usuario-nombre"><?= e(explode(' ', $usuarioActual['nombre'])[0]) ?></span>
                            <span class="usuario-rol"><?= e($etiquetasRol[$usuarioActual['rol']] ?? '') ?></span>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <?php if ($esPersonal): ?>
                            <li><a class="dropdown-item" href="<?= url('/admin') ?>"><i class="bi bi-grid me-2"></i>Panel</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="<?= url('/mis-pedidos') ?>"><i class="bi bi-box-seam me-2"></i>Mis pedidos</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="<?= url('/logout') ?>" method="post">
                                <?= csrf_campo() ?>
                                <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</button>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?= url('/login') ?>" class="btn btn-pya-contorno-claro btn-sm">Iniciar sesión</a>
            <?php endif; ?>

            <button class="navbar-toggler border-0 px-1" type="button" data-bs-toggle="collapse"
                    data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav mx-lg-auto my-2 my-lg-0">
                <li class="nav-item">
                    <a class="nav-link nav-link-pya <?= rutaActiva('/catalogo') || rutaActiva('/') || rutaActiva('/libro') ? 'active' : '' ?>" href="<?= url('/catalogo') ?>">Catálogo</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-pya <?= rutaActiva('/novedades') ? 'active' : '' ?>" href="<?= url('/novedades') ?>">Novedades</a>
                </li>
                <?php if ($esPersonal): ?>
                    <li class="nav-item">
                        <a class="nav-link nav-link-pya <?= rutaActiva('/admin') ? 'active' : '' ?>" href="<?= url('/admin') ?>">Administración</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link nav-link-pya <?= rutaActiva('/carrito') || rutaActiva('/seguimiento') || rutaActiva('/mis-pedidos') ? 'active' : '' ?>" href="<?= url('/carrito') ?>">Mi Carrito</a>
                    </li>
                <?php endif; ?>
            </ul>

            <form class="buscador me-lg-3 mb-3 mb-lg-0" action="<?= url('/catalogo') ?>" method="get" role="search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Buscar lectura..." aria-label="Buscar">
            </form>
        </div>
    </nav>
</header>
