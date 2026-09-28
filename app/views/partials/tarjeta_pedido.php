<?php
/**
 * Tarjeta resumen de un pedido del cliente (carrito vacío y "Mis pedidos").
 * Variables: $pedido (fila de PedidoModel), $librosPedido (detalles con portada)
 */
$visibles = array_slice($librosPedido, 0, 3);
$restantes = count($librosPedido) - count($visibles);
?>
<a href="<?= url('/seguimiento/' . (int) $pedido['id_pedido']) ?>" class="tarjeta tarjeta-pedido d-block p-4 text-decoration-none">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <span class="font-serif fw-semibold"><?= e(codigoPedido($pedido['id_pedido'])) ?></span>
        <?php $estado = $pedido['estado']; require RUTA_VISTAS . '/partials/insignia_estado.php'; ?>
    </div>

    <div class="row g-3 pb-3 mb-3 border-bottom">
        <div class="col-4">
            <p class="etiqueta-seccion mb-1">Fecha</p>
            <p class="small fw-medium mb-0"><?= fecha($pedido['fecha_pedido']) ?></p>
        </div>
        <div class="col-4">
            <p class="etiqueta-seccion mb-1">Artículos</p>
            <p class="small fw-medium mb-0"><?= (int) $pedido['articulos'] ?> <?= (int) $pedido['articulos'] === 1 ? 'libro' : 'libros' ?></p>
        </div>
        <div class="col-4">
            <p class="etiqueta-seccion mb-1">Total</p>
            <p class="small fw-semibold font-serif mb-0"><?= moneda($pedido['total']) ?></p>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between gap-3">
        <div class="pila-portadas">
            <?php foreach ($visibles as $libro): ?>
                <span class="portada-mini"><?php require RUTA_VISTAS . '/partials/portada.php'; ?></span>
            <?php endforeach; ?>
            <?php if ($restantes > 0): ?><span class="portada-mini portada-mas">+<?= $restantes ?></span><?php endif; ?>
        </div>
        <span class="small fw-medium texto-naranja text-nowrap">Ver seguimiento <i class="bi bi-chevron-right"></i></span>
    </div>
</a>
