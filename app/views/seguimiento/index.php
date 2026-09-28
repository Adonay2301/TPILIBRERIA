<?php
/**
 * Historial de pedidos del cliente.
 * Variables: $pedidos, $libros (detalles agrupados por id_pedido)
 */
?>
<div class="container px-3 py-4 py-md-5" style="max-width:900px">
    <h1 class="titulo-pagina mb-4">Mis pedidos</h1>

    <?php if (!$pedidos): ?>
        <div class="tarjeta estado-vacio">
            <i class="bi bi-box-seam"></i>
            <p class="fs-5 fw-medium mb-0">Aún no tienes pedidos</p>
            <a href="<?= url('/catalogo') ?>" class="btn btn-pya mt-2">Explorar catálogo</a>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($pedidos as $pedido): ?>
                <?php $librosPedido = $libros[$pedido['id_pedido']] ?? []; require RUTA_VISTAS . '/partials/tarjeta_pedido.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
