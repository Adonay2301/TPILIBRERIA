<?php
/**
 * Reportes de ventas y títulos más vendidos.
 * Variables: $desde, $hasta, $resumen, $serie, $libros
 */
$datos = [
    ['etiqueta' => 'Ventas totales', 'valor' => moneda($resumen['ventas']), 'icono' => 'bi-currency-dollar', 'color' => '#1a3a4a', 'nota' => 'En el período'],
    ['etiqueta' => 'N.º de pedidos', 'valor' => (int) $resumen['pedidos'], 'icono' => 'bi-box-seam', 'color' => '#d4621a', 'nota' => 'Sin contar cancelados'],
    ['etiqueta' => 'Ticket promedio', 'valor' => moneda($resumen['ticket_promedio']), 'icono' => 'bi-tag', 'color' => '#7c3aed', 'nota' => 'Por pedido'],
    ['etiqueta' => 'Libros vendidos', 'valor' => (int) $resumen['libros'], 'icono' => 'bi-book', 'color' => '#2e7d5a', 'nota' => 'Unidades'],
];
$maxUnidades = max(1, ...array_map('intval', array_column($libros, 'unidades') ?: [1]));
$exportar = fn (string $tipo) => url('/admin/reportes') . '?' . http_build_query(['desde' => $desde, 'hasta' => $hasta, 'exportar' => $tipo]);
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="titulo-pagina mb-1">Reportes</h1>
        <p class="small texto-suave mb-0">Análisis de ventas, títulos destacados y existencias · Punto y Aparte</p>
    </div>
    <form class="tarjeta d-flex flex-wrap align-items-center gap-2 px-3 py-2 small texto-suave" method="get">
        <i class="bi bi-calendar3 texto-naranja"></i><span class="fw-semibold">Período:</span>
        <label for="desde">Desde</label>
        <input type="date" class="form-control form-control-sm w-auto" id="desde" name="desde" value="<?= e($desde) ?>">
        <label for="hasta">Hasta</label>
        <input type="date" class="form-control form-control-sm w-auto" id="hasta" name="hasta" value="<?= e($hasta) ?>">
        <button class="btn btn-pya btn-sm">Aplicar</button>
    </form>
</div>

<section class="tarjeta overflow-hidden mb-4">
    <div class="tarjeta-encabezado">
        <div><h2>Ventas por período</h2><p class="small texto-suave mb-0">Del <?= fecha($desde) ?> al <?= fecha($hasta) ?></p></div>
        <a href="<?= e($exportar('ventas')) ?>" class="btn btn-pya-contorno btn-sm"><i class="bi bi-download me-1"></i>Exportar</a>
    </div>
    <div class="p-4">
        <div class="row g-3 mb-4">
            <?php foreach ($datos as $dato): ?>
                <div class="col-6 col-xl-3"><?php require RUTA_VISTAS . '/partials/dato.php'; ?></div>
            <?php endforeach; ?>
        </div>
        <div class="rounded-3 p-4" style="background:var(--color-cream);border:1px solid var(--color-cream-dark)">
            <div class="d-flex justify-content-between mb-3">
                <h3 class="tarjeta-titulo" style="font-size:.9rem">Ventas por día (USD)</h3>
                <span class="small fw-semibold">Total: <?= moneda($resumen['ventas']) ?></span>
            </div>
            <?php require RUTA_VISTAS . '/partials/grafico_ventas.php'; ?>
        </div>
    </div>
</section>

<section class="tarjeta overflow-hidden mb-4">
    <div class="tarjeta-encabezado">
        <div><h2>Libros más vendidos</h2><p class="small texto-suave mb-0">Títulos con mayor demanda del <?= fecha($desde) ?> al <?= fecha($hasta) ?></p></div>
        <a href="<?= e($exportar('libros')) ?>" class="btn btn-pya-contorno btn-sm"><i class="bi bi-download me-1"></i>Exportar</a>
    </div>
    <div class="table-responsive">
        <table class="table tabla-pya">
            <thead><tr><th style="width:3rem">#</th><th>Libro</th><th class="text-end" style="width:10rem">Unidades</th><th class="text-end">Ingresos</th></tr></thead>
            <tbody>
            <?php foreach ($libros as $i => $libro): ?>
                <tr>
                    <td class="font-serif fw-bold fs-6" style="color:<?= $i < 3 ? 'var(--color-orange)' : 'var(--color-gray-warm)' ?>"><?= $i + 1 ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <span class="portada-mini" style="width:32px;height:44px"><?php require RUTA_VISTAS . '/partials/portada.php'; ?></span>
                            <div class="min-w-0">
                                <p class="fw-semibold mb-0 text-truncate"><?= e($libro['titulo']) ?></p>
                                <p class="small texto-suave mb-0 text-truncate"><?= e($libro['autores'] ?? '') ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="text-end">
                        <span class="fw-semibold"><?= (int) $libro['unidades'] ?> uds.</span>
                        <div class="barra-progreso mt-1"><span style="width:<?= round($libro['unidades'] / $maxUnidades * 100) ?>%;<?= $i < 3 ? 'background:var(--color-orange)' : '' ?>"></span></div>
                    </td>
                    <td class="text-end font-serif fw-semibold"><?= moneda($libro['ingresos']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$libros): ?><tr><td colspan="4" class="text-center texto-suave py-4">Sin ventas en este período.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
