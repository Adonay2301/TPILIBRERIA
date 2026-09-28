<?php
/**
 * Menú lateral del panel. Cambia según el rol, igual que en el prototipo.
 * (Ocultar opciones es solo visual: los permisos reales se validan en cada controlador.)
 * Variables: $usuarioActual
 */
$esAdmin = ($usuarioActual['rol'] ?? '') === 'administrador';

$inventario = [
    ['texto' => 'Libros',      'ruta' => '/admin/libros'],
    ['texto' => 'Autores',     'ruta' => '/admin/autores'],
    ['texto' => 'Categorías',  'ruta' => '/admin/categorias'],
    ['texto' => 'Editoriales', 'ruta' => '/admin/editoriales'],
];

$menu = [
    ['texto' => 'Panel',       'icono' => 'bi-grid',         'ruta' => '/admin', 'exacta' => true],
    ['texto' => 'Pedidos',     'icono' => 'bi-box-seam',     'ruta' => '/admin/pedidos'],
    ['texto' => 'Preparación', 'icono' => 'bi-clock',        'ruta' => '/admin/preparacion'],
    ['texto' => 'Inventario',  'icono' => 'bi-archive',      'hijos' => $inventario],
];
if ($esAdmin) {
    $menu[] = ['texto' => 'Clientes',  'icono' => 'bi-people',       'ruta' => '/admin/clientes'];
    $menu[] = ['texto' => 'Reportes',  'icono' => 'bi-bar-chart',    'ruta' => '/admin/reportes'];
    $menu[] = ['texto' => 'Empleados', 'icono' => 'bi-person-vcard', 'ruta' => '/admin/empleados'];
}

$inventarioAbierto = (bool) array_filter($inventario, fn ($h) => rutaActiva($h['ruta']));
?>
<aside class="sidebar-pya offcanvas-lg offcanvas-start" tabindex="-1" id="sidebarPanel" aria-labelledby="sidebarTitulo">
    <div class="sidebar-usuario">
        <span class="avatar"><?= e(iniciales($usuarioActual['nombre'] ?? '')) ?></span>
        <div class="min-w-0">
            <p class="sidebar-nombre text-truncate" id="sidebarTitulo"><?= e(explode(' ', $usuarioActual['nombre'] ?? '')[0]) ?></p>
            <span class="insignia-rol"><?= $esAdmin ? 'Administrador' : 'Empleado' ?></span>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto d-lg-none" data-bs-dismiss="offcanvas"
                data-bs-target="#sidebarPanel" aria-label="Cerrar menú"></button>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($menu as $item): ?>
            <?php if (isset($item['hijos'])): ?>
                <button class="sidebar-enlace <?= $inventarioAbierto ? 'active' : 'collapsed' ?>" type="button"
                        data-bs-toggle="collapse" data-bs-target="#menuInventario" aria-expanded="<?= $inventarioAbierto ? 'true' : 'false' ?>">
                    <i class="bi <?= e($item['icono']) ?>"></i>
                    <span class="flex-grow-1 text-start"><?= e($item['texto']) ?></span>
                    <i class="bi bi-chevron-right flecha"></i>
                </button>
                <div class="collapse <?= $inventarioAbierto ? 'show' : '' ?>" id="menuInventario">
                    <div class="sidebar-sub">
                        <?php foreach ($item['hijos'] as $hijo): ?>
                            <a href="<?= url($hijo['ruta']) ?>" class="sidebar-subenlace <?= rutaActiva($hijo['ruta']) ? 'active' : '' ?>">
                                <span class="punto"></span><?= e($hijo['texto']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= url($item['ruta']) ?>"
                   class="sidebar-enlace <?= rutaActiva($item['ruta'], !empty($item['exacta'])) ? 'active' : '' ?>">
                    <i class="bi <?= e($item['icono']) ?>"></i><?= e($item['texto']) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>
