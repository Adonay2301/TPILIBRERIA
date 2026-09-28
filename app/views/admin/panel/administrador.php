<?php
/**
 * Panel del administrador.
 * Variables: $desde, $hasta, $actual, $anterior, $nuevos, $nuevosAnt, $serie, $recientes,
 *            $masVendidos, $porEmpleado, $stockBajo
 */
$datos = [
    ['etiqueta' => 'Ventas del período', 'valor' => moneda($actual['ventas']), 'icono' => 'bi-currency-dollar', 'color' => '#1a3a4a',
     'variacion' => variacion((float) $actual['ventas'], (float) $anterior['ventas'])],
    ['etiqueta' => 'Pedidos', 'valor' => (int) $actual['pedidos'], 'icono' => 'bi-box-seam', 'color' => '#d4621a',
     'variacion' => variacion((float) $actual['pedidos'], (float) $anterior['pedidos'])],
    ['etiqueta' => 'Libros vendidos', 'valor' => (int) $actual['libros'], 'icono' => 'bi-book', 'color' => '#7c3aed',
     'variacion' => variacion((float) $actual['libros'], (float) $anterior['libros'])],
    ['etiqueta' => 'Clientes nuevos', 'valor' => $nuevos, 'icono' => 'bi-person-plus', 'color' => '#2e7d5a',
     'variacion' => variacion((float) $nuevos, (float) $nuevosAnt)],
];
$totalSerie = array_sum(array_column($serie, 'valor'));
$totales = ['total' => 0, 'preparando' => 0, 'enviados' => 0, 'entregados' => 0];
foreach ($porEmpleado as $fila) foreach ($totales as $k => $_) $totales[$k] += (int) $fila[$k];
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <h1 class="titulo-pagina mb-0">Panel Administrativo</h1>
    <form class="d-flex flex-wrap align-items-center gap-2 small texto-suave" method="get">
        <span class="fw-semibold">Período:</span>
        <label for="desde">Desde</label>
        <input type="date" class="form-control form-control-sm w-auto" id="desde" name="desde" value="<?= e($desde) ?>">
        <label for="hasta">Hasta</label>
        <input type="date" class="form-control form-control-sm w-auto" id="hasta" name="hasta" value="<?= e($hasta) ?>">
        <button class="btn btn-pya btn-sm">Aplicar</button>
    </form>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($datos as $dato): ?>
        <div class="col-6 col-xl-3"><?php require RUTA_VISTAS . '/partials/dato.php'; ?></div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <section class="tarjeta overflow-hidden h-100">
            <div class="tarjeta-encabezado">
                <h2>Pedidos recientes</h2>
                <a href="<?= url('/admin/pedidos') ?>" class="btn-enlace text-decoration-none">Ver todos →</a>
            </div>
            <div class="table-responsive">
                <table class="table tabla-pya">
                    <thead><tr><th>N° pedido</th><th>Cliente</th><th>Fecha</th><th class="text-end">Total</th><th class="text-center">Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($recientes as $p): ?>
                        <tr>
                            <td><a href="<?= url('/admin/pedidos/' . (int) $p['id_pedido']) ?>" class="font-monospace fw-semibold text-decoration-none"><?= e(codigoPedido($p['id_pedido'])) ?></a></td>
                            <td class="text-truncate" style="max-width:10rem"><?= e($p['cliente']) ?></td>
                            <td class="texto-suave"><?= fecha($p['fecha_pedido']) ?></td>
                            <td class="text-end fw-semibold"><?= moneda($p['total']) ?></td>
                            <td class="text-center"><?php $estado = $p['estado']; require RUTA_VISTAS . '/partials/insignia_estado.php'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recientes): ?><tr><td colspan="5" class="text-center texto-suave py-4">Aún no hay pedidos.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-xl-5">
        <section class="tarjeta overflow-hidden h-100">
            <div class="tarjeta-encabezado"><h2>Más vendidos del período</h2></div>
            <?php foreach ($masVendidos as $i => $libro): ?>
                <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
                    <span class="font-serif fw-bold text-center" style="width:1.25rem;color:<?= $i < 3 ? 'var(--color-orange)' : 'var(--color-gray-warm)' ?>"><?= $i + 1 ?></span>
                    <span class="portada-mini" style="width:32px;height:44px"><?php require RUTA_VISTAS . '/partials/portada.php'; ?></span>
                    <div class="flex-grow-1 min-w-0">
                        <p class="small fw-semibold mb-0 text-truncate"><?= e($libro['titulo']) ?></p>
                        <p class="small texto-suave mb-0 text-truncate"><?= e($libro['autores'] ?? '') ?></p>
                    </div>
                    <span class="small fw-semibold" style="color:var(--color-teal-light)"><?= (int) $libro['unidades'] ?> uds.</span>
                </div>
            <?php endforeach; ?>
            <?php if (!$masVendidos): ?><p class="small texto-suave text-center py-4 mb-0">Sin ventas en este período.</p><?php endif; ?>
        </section>
    </div>
</div>

<section class="tarjeta p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="tarjeta-titulo">Ventas — últimos 30 días</h2>
        <span class="small fw-semibold">Total: <?= moneda($totalSerie) ?></span>
    </div>
    <?php require RUTA_VISTAS . '/partials/grafico_ventas.php'; ?>
</section>

<section class="tarjeta overflow-hidden mb-4">
    <div class="tarjeta-encabezado flex-wrap" style="background:var(--color-cream)">
        <div>
            <h2>Seguimiento por empleado</h2>
            <p class="small texto-suave mb-0">Pedidos gestionados por cada miembro del equipo</p>
        </div>
        <div class="d-flex gap-2">
            <span class="insignia estado-preparacion"><?= $totales['preparando'] ?> prep.</span>
            <span class="insignia estado-enviado"><?= $totales['enviados'] ?> en ruta</span>
            <span class="insignia estado-entregado"><?= $totales['entregados'] ?> entregados</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table tabla-pya">
            <thead><tr><th>Empleado</th><th class="text-center">Total</th><th class="text-center">Preparando</th><th class="text-center">En tránsito</th><th class="text-center">Entregados</th></tr></thead>
            <tbody>
            <?php foreach ($porEmpleado as $fila): ?>
                <tr class="<?= $fila['estado_empleado'] === 'baja' ? 'fila-inactiva' : '' ?>">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar" style="background:rgba(26,58,74,.1);color:var(--color-teal)"><?= e(iniciales($fila['empleado'])) ?></span>
                            <span class="fw-medium"><?= e($fila['empleado']) ?></span>
                            <?php if ($fila['estado_empleado'] === 'baja'): ?><span class="insignia estado-cancelado">De baja</span><?php endif; ?>
                        </div>
                    </td>
                    <td class="text-center font-serif fw-bold"><?= (int) $fila['total'] ?></td>
                    <td class="text-center"><?= (int) $fila['preparando'] ?: '—' ?></td>
                    <td class="text-center"><?= (int) $fila['enviados'] ?: '—' ?></td>
                    <td class="text-center"><?= (int) $fila['entregados'] ?: '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="fw-semibold small">
                    <td class="ps-3">Total global</td>
                    <td class="text-center"><?= $totales['total'] ?></td>
                    <td class="text-center"><?= $totales['preparando'] ?></td>
                    <td class="text-center"><?= $totales['enviados'] ?></td>
                    <td class="text-center"><?= $totales['entregados'] ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>

<?php require RUTA_VISTAS . '/admin/panel/stock_bajo.php'; ?>
