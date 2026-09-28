<?php
/**
 * Insignia de estado de pedido. Variables: $estado (valor del ENUM pedidos.estado)
 */
$info = estadoPedido($estado);
?>
<span class="insignia <?= e($info['clase']) ?>"><span class="punto"></span><?= e($info['texto']) ?></span>
