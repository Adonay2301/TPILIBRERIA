<?php
/**
 * Mensajes flash (éxito, error, aviso) como alertas de Bootstrap flotantes.
 * Se cierran solas después de unos segundos (public/assets/js/app.js).
 */
$mensajes = flashes();
$iconos = [
    'success' => 'bi-check-circle-fill',
    'danger'  => 'bi-exclamation-octagon-fill',
    'warning' => 'bi-exclamation-triangle-fill',
    'info'    => 'bi-info-circle-fill',
];
?>
<?php if ($mensajes): ?>
    <div class="alertas-flotantes" aria-live="polite">
        <?php foreach ($mensajes as $m): ?>
            <div class="alert alert-<?= e($m['tipo']) ?> alert-dismissible fade show shadow" role="alert" data-autocerrar>
                <i class="bi <?= e($iconos[$m['tipo']] ?? 'bi-info-circle-fill') ?> me-2"></i><?= e($m['mensaje']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
