<?php
/**
 * Seguimiento de un pedido del cliente.
 * Variables: $pedido, $detalles, $pasos, $cancelacion
 */
?>
<div class="container px-3 py-4 py-md-5" style="max-width:900px">
    <a href="<?= url('/mis-pedidos') ?>" class="small text-decoration-none">← Mis pedidos</a>
    <h1 class="titulo-pagina mt-2 mb-4">Seguimiento de Pedido</h1>

    <section class="tarjeta p-4 mb-4">
        <div class="d-flex flex-wrap gap-4 mb-4">
            <div><p class="etiqueta-seccion mb-1">Número de pedido</p><p class="fw-semibold small mb-0"><?= e(codigoPedido($pedido['id_pedido'])) ?></p></div>
            <div><p class="etiqueta-seccion mb-1">Fecha de pedido</p><p class="fw-semibold small mb-0"><?= fecha($pedido['fecha_pedido']) ?></p></div>
            <div><p class="etiqueta-seccion mb-1">Estado</p><?php $estado = $pedido['estado']; require RUTA_VISTAS . '/partials/insignia_estado.php'; ?></div>
        </div>

        <?php if ($cancelacion): ?>
            <div class="alert alert-secondary small mb-0">
                <i class="bi bi-x-circle me-1"></i>Este pedido fue cancelado el <?= fechaHora($cancelacion['fecha']) ?>.
                <?php if ($cancelacion['motivo']): ?><br>Motivo: <?= e($cancelacion['motivo']) ?><?php endif; ?>
            </div>
        <?php else: ?>
            <ol class="linea-tiempo">
                <?php foreach ($pasos as $paso): ?>
                    <li>
                        <span class="hito <?= $paso['hecho'] ? 'hecho' : '' ?>"><?= $paso['hecho'] ? '<i class="bi bi-check-lg"></i>' : '' ?></span>
                        <div class="pt-1">
                            <p class="small fw-medium mb-0 <?= $paso['hecho'] ? '' : 'texto-suave' ?>"><?= e($paso['texto']) ?></p>
                            <p class="small texto-suave mb-0"><?= $paso['fecha'] ? fechaHora($paso['fecha']) : '—' ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>

    <section class="tarjeta overflow-hidden mb-4">
        <div class="tarjeta-encabezado">
            <h2>Libros en este pedido</h2>
            <span class="small texto-suave"><?= array_sum(array_column($detalles, 'cantidad')) ?> artículos</span>
        </div>
        <?php foreach ($detalles as $libro): ?>
            <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                <span class="portada-mini" style="width:48px;height:66px"><?php require RUTA_VISTAS . '/partials/portada.php'; ?></span>
                <div class="flex-grow-1 min-w-0">
                    <p class="small fw-semibold mb-0 text-truncate"><?= e($libro['titulo']) ?></p>
                    <p class="small texto-suave mb-1"><?= e($libro['autores'] ?? '') ?></p>
                    <p class="small mb-0" style="color:var(--color-teal-light)"><?= moneda($libro['precio_unitario']) ?> c/u · cant. <?= (int) $libro['cantidad'] ?></p>
                </div>
                <span class="small fw-semibold"><?= moneda($libro['subtotal']) ?></span>
            </div>
        <?php endforeach; ?>
        <div class="px-4 py-3 small" style="background:var(--color-cream)">
            <div class="d-flex justify-content-between mb-1"><span class="texto-suave">Subtotal</span><span><?= moneda($pedido['subtotal']) ?></span></div>
            <div class="d-flex justify-content-between mb-2"><span class="texto-suave">Envío</span>
                <span class="<?= (float) $pedido['costo_envio'] == 0 ? 'texto-verde fw-medium' : '' ?>"><?= (float) $pedido['costo_envio'] > 0 ? moneda($pedido['costo_envio']) : 'Gratis' ?></span></div>
            <div class="d-flex justify-content-between align-items-baseline border-top pt-2">
                <span class="fw-semibold">Total del pedido</span><span class="font-serif fw-bold fs-5"><?= moneda($pedido['total']) ?></span>
            </div>
        </div>
    </section>

    <section class="tarjeta p-4 mb-5">
        <h2 class="tarjeta-titulo mb-2" style="font-size:.9rem">Dirección de entrega</h2>
        <p class="small texto-suave mb-0">
            <?= e($pedido['direccion_entrega']) ?><br>
            <?= e($pedido['distrito']) ?>, <?= e($pedido['municipio']) ?>, <?= e($pedido['departamento']) ?>
            <?php if ($pedido['referencia_entrega']): ?><br><span class="fst-italic">Ref: <?= e($pedido['referencia_entrega']) ?></span><?php endif; ?>
            <br>Tel. <?= e($pedido['telefono_contacto']) ?>
        </p>
    </section>
</div>
