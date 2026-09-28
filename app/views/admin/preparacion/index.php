<?php
/**
 * Preparación de pedidos.
 * Variables: $grupos (empleado => pedidos), $libros (detalles por pedido), $esAdmin
 */
?>
<h1 class="titulo-pagina mb-2">Preparación de pedidos</h1>
<p class="texto-suave small mb-4">
    <?= $esAdmin ? 'Pedidos asignados a empleados pendientes de preparación.' : 'Pedidos asignados a ti que requieren preparación antes del envío.' ?>
</p>

<?php if (!$grupos): ?>
    <div class="tarjeta estado-vacio">
        <i class="bi bi-box-seam"></i>
        <p class="fw-medium mb-0">No hay pedidos en preparación</p>
        <p class="small texto-suave mb-0">Los pedidos asignados aparecerán aquí</p>
    </div>
<?php endif; ?>

<?php foreach ($grupos as $empleado => $pedidos): ?>
    <section class="mb-4">
        <?php if ($esAdmin): ?>
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="avatar" style="background:rgba(26,58,74,.1);color:var(--color-teal)"><?= e(iniciales($empleado)) ?></span>
                <h2 class="font-serif h6 mb-0"><?= e($empleado) ?></h2>
                <span class="insignia insignia-neutra"><?= count($pedidos) ?> <?= count($pedidos) === 1 ? 'pedido' : 'pedidos' ?></span>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-column gap-2">
            <?php foreach ($pedidos as $p): ?>
                <?php $librosPedido = $libros[$p['id_pedido']] ?? []; ?>
                <div class="tarjeta d-flex flex-wrap flex-md-nowrap align-items-center gap-3 px-4 py-3">
                    <span class="rounded-circle flex-shrink-0" style="width:10px;height:10px;background:#ca8a04"></span>
                    <div class="flex-grow-1 min-w-0">
                        <p class="mb-0">
                            <a href="<?= url('/admin/pedidos/' . (int) $p['id_pedido']) ?>" class="font-monospace fw-semibold small text-decoration-none"><?= e(codigoPedido($p['id_pedido'])) ?></a>
                            <?php $estado = $p['estado']; require RUTA_VISTAS . '/partials/insignia_estado.php'; ?>
                        </p>
                        <p class="small texto-suave mb-0 text-truncate"><?= e($p['cliente']) ?> · <?= (int) $p['articulos'] ?> <?= (int) $p['articulos'] === 1 ? 'artículo' : 'artículos' ?> · <?= e($p['municipio']) ?></p>
                        <p class="small texto-suave mb-0 text-truncate"><?= e(implode(', ', array_map(fn ($l) => $l['cantidad'] . '× ' . $l['titulo'], $librosPedido))) ?></p>
                    </div>
                    <div class="pila-portadas d-none d-md-flex">
                        <?php foreach (array_slice($librosPedido, 0, 3) as $libro): ?>
                            <span class="portada-mini" style="width:28px;height:38px"><?php require RUTA_VISTAS . '/partials/portada.php'; ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($esAdmin || $p['id_empleado'] !== null): ?>
                        <form action="<?= url('/admin/preparacion/' . (int) $p['id_pedido'] . '/listo') ?>" method="post"
                              data-confirmar="¿El pedido <?= e(codigoPedido($p['id_pedido'])) ?> está listo? Pasará a «En tránsito».">
                            <?= csrf_campo() ?>
                            <button class="btn btn-pya btn-sm text-nowrap"><i class="bi bi-check-lg me-1"></i>Pedido preparado</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
