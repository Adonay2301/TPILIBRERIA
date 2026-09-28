<?php
/**
 * Gestión de pedidos (administrador y empleado).
 * Variables: $pedidos, $filtros, $paginacion, $contadores, $esAdmin, $miIdEmpleado
 */
$datos = [
    ['etiqueta' => 'Pedidos hoy', 'valor' => (int) $contadores['hoy'], 'nota' => date('d/m/Y')],
    ['etiqueta' => 'Preparando', 'valor' => (int) $contadores['preparando'], 'color' => '#ca8a04'],
    ['etiqueta' => 'En tránsito', 'valor' => (int) $contadores['enviados'], 'color' => '#d4621a'],
    ['etiqueta' => 'Entregados este mes', 'valor' => (int) $contadores['entregados_mes'], 'color' => '#2e7d5a'],
];
?>
<h1 class="titulo-pagina mb-4">Gestión de Pedidos</h1>

<div class="row g-3 mb-4">
    <?php foreach ($datos as $dato): ?>
        <div class="col-6 col-xl-3"><?php require RUTA_VISTAS . '/partials/dato.php'; ?></div>
    <?php endforeach; ?>
</div>

<form class="barra-filtros mb-4" method="get">
    <label class="campo-busqueda">
        <i class="bi bi-search"></i>
        <input type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Buscar por N° de pedido o cliente" aria-label="Buscar">
    </label>
    <select name="estado" class="form-select" aria-label="Estado" onchange="this.form.submit()">
        <option value="">Todos los estados</option>
        <?php foreach (array_keys(PedidoModel::TRANSICIONES) as $estado): ?>
            <option value="<?= e($estado) ?>" <?= $filtros['estado'] === $estado ? 'selected' : '' ?>><?= e(estadoPedido($estado)['texto']) ?></option>
        <?php endforeach; ?>
    </select>
    <span class="small texto-suave">Desde</span>
    <input type="date" name="desde" class="form-control" value="<?= e($filtros['desde']) ?>" aria-label="Desde">
    <span class="small texto-suave">Hasta</span>
    <input type="date" name="hasta" class="form-control" value="<?= e($filtros['hasta']) ?>" aria-label="Hasta">
    <button class="btn btn-pya btn-sm">Filtrar</button>
    <a href="<?= url('/admin/pedidos') ?>" class="btn btn-light border btn-sm">Limpiar filtros</a>
</form>

<div class="tarjeta overflow-hidden">
    <div class="table-responsive">
        <table class="table tabla-pya">
            <thead>
                <tr><th>N° de pedido</th><th>Cliente</th><th>Fecha</th><th class="text-center">Arts.</th><th class="text-end">Total</th><th class="text-center">Estado</th><th>Responsable</th><th class="text-center">Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($pedidos as $p): ?>
                <?php $libre = $p['estado'] === 'pendiente' && $p['id_empleado'] === null; ?>
                <tr>
                    <td class="font-monospace fw-semibold"><?= e(codigoPedido($p['id_pedido'])) ?></td>
                    <td style="max-width:14rem">
                        <p class="fw-medium mb-0 text-truncate"><?= e($p['cliente']) ?></p>
                        <p class="small texto-suave mb-0 text-truncate"><?= e($p['correo']) ?></p>
                    </td>
                    <td class="texto-suave"><?= fecha($p['fecha_pedido']) ?></td>
                    <td class="text-center"><?= (int) $p['articulos'] ?></td>
                    <td class="text-end fw-semibold"><?= moneda($p['total']) ?></td>
                    <td class="text-center"><?php $estado = $p['estado']; require RUTA_VISTAS . '/partials/insignia_estado.php'; ?></td>
                    <td class="small"><?= $p['empleado'] ? e($p['empleado']) : '<span class="texto-suave">—</span>' ?></td>
                    <td class="text-center text-nowrap">
                        <?php if (!$esAdmin && $libre): ?>
                            <form action="<?= url('/admin/pedidos/' . (int) $p['id_pedido'] . '/asignar') ?>" method="post" class="d-inline">
                                <?= csrf_campo() ?>
                                <button class="btn btn-pya btn-sm">Asignarme</button>
                            </form>
                        <?php endif; ?>
                        <a href="<?= url('/admin/pedidos/' . (int) $p['id_pedido']) ?>" class="btn-icono" title="Ver detalle"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$pedidos): ?>
                <tr><td colspan="8"><div class="estado-vacio py-5"><i class="bi bi-search"></i><p class="small mb-0">No se encontraron pedidos</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $etiqueta = 'pedidos'; require RUTA_VISTAS . '/partials/paginacion.php'; ?>
