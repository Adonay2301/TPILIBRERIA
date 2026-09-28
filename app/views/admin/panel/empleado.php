<?php
/**
 * Panel del empleado.
 * Variables: $conteo, $disponibles, $enCurso, $stockBajo, $usuarioActual
 */
$hora = (int) date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$contadores = [
    ['Preparando', $conteo['en_preparacion'], '#ca8a04'],
    ['En camino',  $conteo['enviado'],        '#d4621a'],
    ['Entregados', $conteo['entregado'],      '#2e7d5a'],
];
?>
<div class="mb-4">
    <h1 class="titulo-pagina mb-1"><?= $saludo ?>, <?= e(explode(' ', $usuarioActual['nombre'])[0]) ?></h1>
    <p class="texto-suave small mb-0">Hoy es <strong class="text-body"><?= date('d/m/Y') ?></strong> <span class="insignia insignia-neutra ms-2">Empleado</span></p>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($contadores as [$texto, $valor, $color]): ?>
        <div class="col-4">
            <div class="tarjeta-dato">
                <span class="etiqueta-seccion"><span class="punto-color" style="background:<?= $color ?>"></span><?= $texto ?></span>
                <p class="valor" style="font-size:2.3rem"><?= (int) $valor ?></p>
                <p class="small texto-suave mb-0 mt-1">Mis pedidos</p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($disponibles): ?>
    <section class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="font-serif h5 mb-0">Pedidos disponibles</h2>
            <span class="insignia estado-pendiente"><?= count($disponibles) ?> sin asignar</span>
        </div>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($disponibles as $p): ?>
                <div class="tarjeta d-flex align-items-center gap-3 px-4 py-3" style="border-color:rgba(59,130,246,.25);background:rgba(59,130,246,.03)">
                    <div class="flex-grow-1 min-w-0">
                        <p class="mb-0"><span class="font-monospace fw-semibold small"><?= e(codigoPedido($p['id_pedido'])) ?></span>
                            <?php $estado = $p['estado']; require RUTA_VISTAS . '/partials/insignia_estado.php'; ?></p>
                        <p class="small texto-suave mb-0 text-truncate"><?= e($p['cliente']) ?> · <?= (int) $p['articulos'] ?> artículos · <?= fecha($p['fecha_pedido']) ?></p>
                    </div>
                    <form action="<?= url('/admin/pedidos/' . (int) $p['id_pedido'] . '/asignar') ?>" method="post">
                        <?= csrf_campo() ?>
                        <button class="btn btn-pya btn-sm">Asignarme</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="mb-4">
    <h2 class="font-serif h5 mb-3">Mis pedidos en seguimiento</h2>
    <?php if (!$enCurso): ?>
        <div class="tarjeta estado-vacio py-5">
            <i class="bi bi-box-seam"></i>
            <p class="small texto-suave mb-0">No tienes pedidos en curso. Asígnate uno de la lista de disponibles.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($enCurso as $p): ?>
                <a href="<?= url('/admin/pedidos/' . (int) $p['id_pedido']) ?>" class="tarjeta d-flex align-items-center gap-3 px-4 py-3 text-decoration-none">
                    <div class="flex-grow-1 min-w-0">
                        <p class="mb-0"><span class="font-monospace fw-semibold small"><?= e(codigoPedido($p['id_pedido'])) ?></span>
                            <?php $estado = $p['estado']; require RUTA_VISTAS . '/partials/insignia_estado.php'; ?></p>
                        <p class="small texto-suave mb-0 text-truncate"><?= e($p['cliente']) ?> · <?= (int) $p['articulos'] ?> artículos · <?= e($p['municipio']) ?></p>
                    </div>
                    <i class="bi bi-chevron-right texto-suave"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require RUTA_VISTAS . '/admin/panel/stock_bajo.php'; ?>
