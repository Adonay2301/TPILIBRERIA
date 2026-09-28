<?php
/**
 * Tarjeta de indicador (KPI).
 * Variables: $dato = ['etiqueta', 'valor', 'color' (punto), 'icono', 'nota', 'variacion' (float|null)]
 */
?>
<div class="tarjeta-dato">
    <div class="d-flex align-items-start justify-content-between gap-2">
        <span class="etiqueta-seccion">
            <?php if (!empty($dato['color'])): ?><span class="punto-color" style="background:<?= e($dato['color']) ?>"></span><?php endif; ?>
            <?= e($dato['etiqueta']) ?>
        </span>
        <?php if (!empty($dato['icono'])): ?>
            <span class="icono-dato" style="background:<?= e($dato['color'] ?? '#1a3a4a') ?>18;color:<?= e($dato['color'] ?? '#1a3a4a') ?>"><i class="bi <?= e($dato['icono']) ?>"></i></span>
        <?php endif; ?>
    </div>
    <p class="valor"><?= e($dato['valor']) ?></p>
    <?php if (array_key_exists('variacion', $dato) && $dato['variacion'] !== null): ?>
        <p class="mb-0 mt-2">
            <span class="<?= $dato['variacion'] >= 0 ? 'variacion-positiva' : 'variacion-negativa' ?>">
                <?= $dato['variacion'] >= 0 ? '▲' : '▼' ?> <?= e(abs($dato['variacion'])) ?>%
            </span>
            <span class="small texto-suave">vs. período anterior</span>
        </p>
    <?php elseif (!empty($dato['nota'])): ?>
        <p class="small texto-suave mb-0 mt-2"><?= e($dato['nota']) ?></p>
    <?php endif; ?>
</div>
