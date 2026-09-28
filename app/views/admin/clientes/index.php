<?php
/**
 * Gestión de clientes.
 * Variables: $clientes, $filtros, $paginacion, $stats, $top, $departamentos, $actividades
 */
$datos = [
    ['etiqueta' => 'Clientes registrados', 'valor' => (int) $stats['registrados'], 'color' => '#1a3a4a'],
    ['etiqueta' => 'Nuevos este mes', 'valor' => (int) $stats['nuevos_mes'], 'color' => '#3b82f6',
     'variacion' => variacion((float) $stats['nuevos_mes'], (float) $stats['nuevos_mes_anterior'])],
    ['etiqueta' => 'Con pedidos activos', 'valor' => (int) $stats['con_activos'], 'color' => '#ca8a04', 'nota' => 'Pendientes, preparando o en ruta'],
    ['etiqueta' => 'Ticket promedio', 'valor' => moneda($stats['ticket_promedio']), 'color' => '#2e7d5a'],
];
?>
<h1 class="titulo-pagina mb-1">Gestión de Clientes</h1>
<p class="small texto-suave mb-4">Consulta de clientes registrados y su historial de compras</p>

<div class="row g-3 mb-4">
    <?php foreach ($datos as $dato): ?>
        <div class="col-6 col-xl-3"><?php require RUTA_VISTAS . '/partials/dato.php'; ?></div>
    <?php endforeach; ?>
</div>

<form class="barra-filtros mb-4" method="get">
    <label class="campo-busqueda">
        <i class="bi bi-search"></i>
        <input type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Buscar por nombre, correo o teléfono" aria-label="Buscar">
    </label>
    <select name="departamento" class="form-select" onchange="this.form.submit()" aria-label="Departamento">
        <option value="">Departamento</option>
        <?php foreach ($departamentos as $d): ?>
            <option value="<?= (int) $d['id'] ?>" <?= $filtros['departamento'] === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="actividad" class="form-select" onchange="this.form.submit()" aria-label="Actividad">
        <option value="">Actividad</option>
        <?php foreach ($actividades as $clave => $texto): ?>
            <option value="<?= e($clave) ?>" <?= $filtros['actividad'] === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
        <?php endforeach; ?>
    </select>
    <a href="<?= url('/admin/clientes') ?>" class="btn btn-light border btn-sm">Limpiar filtros</a>
</form>

<div class="tarjeta overflow-hidden">
    <div class="table-responsive">
        <table class="table tabla-pya">
            <thead><tr><th>Cliente</th><th>Contacto</th><th>Ubicación</th><th class="text-center">Pedidos</th><th class="text-end">Total gastado</th><th class="text-center">Último pedido</th><th class="text-center">Ficha</th></tr></thead>
            <tbody>
            <?php foreach ($clientes as $c): $nombre = $c['nombres'] . ' ' . $c['apellidos']; ?>
                <tr class="<?= $c['activo'] ? '' : 'fila-inactiva' ?>">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar" style="background:var(--color-teal-light)"><?= e(iniciales($nombre)) ?></span>
                            <div class="min-w-0">
                                <p class="fw-semibold mb-0 text-truncate"><?= e($nombre) ?><?= $c['activo'] ? '' : ' <span class="insignia estado-cancelado">Desactivada</span>' ?></p>
                                <p class="small texto-suave mb-0">Desde <?= fecha($c['fecha_registro']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="small"><?= e($c['correo']) ?><br><span class="texto-suave"><?= e($c['telefono'] ?? '—') ?></span></td>
                    <td class="small"><?= e($c['municipio'] ?? '—') ?><br><span class="texto-suave"><?= e($c['departamento'] ?? '') ?></span></td>
                    <td class="text-center">
                        <span class="font-serif fw-bold"><?= (int) $c['pedidos'] ?></span>
                        <?php if ((int) $c['activos'] > 0): ?><span class="d-inline-block rounded-circle ms-1" style="width:8px;height:8px;background:var(--color-orange)" title="Tiene pedidos activos"></span><?php endif; ?>
                    </td>
                    <td class="text-end fw-semibold text-nowrap">
                        <?php if (in_array((int) $c['id_cliente'], array_map('intval', $top), true) && (float) $c['total_gastado'] > 0): ?>
                            <i class="bi bi-star-fill me-1" style="color:var(--color-orange)" title="Entre los clientes que más compran"></i>
                        <?php endif; ?>
                        <?= moneda($c['total_gastado']) ?>
                    </td>
                    <td class="text-center small <?= $c['ultimo_pedido'] && diasDesde($c['ultimo_pedido']) > 90 ? 'texto-suave' : '' ?>"><?= fecha($c['ultimo_pedido']) ?></td>
                    <td class="text-center"><a href="<?= url('/admin/clientes/' . (int) $c['id_cliente']) ?>" class="btn-icono" title="Ver ficha"><i class="bi bi-eye"></i></a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$clientes): ?><tr><td colspan="7"><div class="estado-vacio py-5"><i class="bi bi-search"></i><p class="small mb-0">No se encontraron clientes</p></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $etiqueta = 'clientes'; require RUTA_VISTAS . '/partials/paginacion.php'; ?>
