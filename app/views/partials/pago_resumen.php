<?php
/**
 * Fila de método y estado del pago, debajo del total del pedido.
 * Variables: $pedido (con pago_metodo, pago_estado, paypal_*), $detallePaypal (bool, solo panel)
 */
$infoPago = estadoPago($pedido['pago_estado'] ?? null);
?>
<div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
    <span class="texto-suave">
        <i class="bi <?= ($pedido['pago_metodo'] ?? '') === 'paypal' ? 'bi-paypal' : 'bi-cash-coin' ?> me-1"></i><?= e(metodoPago($pedido['pago_metodo'] ?? null)) ?>
    </span>
    <span class="insignia <?= e($infoPago['clase']) ?>"><span class="punto"></span><?= e($infoPago['texto']) ?></span>
</div>
<?php if (!empty($detallePaypal) && ($pedido['pago_metodo'] ?? '') === 'paypal'): ?>
    <p class="texto-suave mb-0 mt-2" style="font-size:.75rem">
        Orden PayPal <span class="font-monospace"><?= e($pedido['paypal_orden_id']) ?></span>
        <?php if ($pedido['paypal_captura_id']): ?> · Captura <span class="font-monospace"><?= e($pedido['paypal_captura_id']) ?></span><?php endif; ?>
        <?php if ($pedido['paypal_correo']): ?><br>Pagado por <?= e($pedido['paypal_correo']) ?><?php endif; ?>
    </p>
<?php endif; ?>
