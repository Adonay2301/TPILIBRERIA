<?php
/**
 * Tarjeta de libro (catálogo, novedades, recomendaciones).
 * Variables: $libro (fila de LibroModel::buscar)
 */
$disponible = (int) $libro['stock_actual'] > 0;
$urlLibro = url('/libro/' . (int) $libro['id_libro']);
?>
<article class="libro-card <?= $disponible ? '' : 'agotado' ?>">
    <a href="<?= $urlLibro ?>" class="libro-portada d-block">
        <?php require RUTA_VISTAS . '/partials/portada.php'; ?>
        <?php if (!empty($libro['es_nuevo']) && $disponible): ?>
            <span class="etiqueta-nuevo">NUEVO</span>
        <?php endif; ?>
    </a>
    <div class="libro-info">
        <span class="libro-categoria"><?= e($libro['categoria']) ?></span>
        <a href="<?= $urlLibro ?>" class="libro-titulo text-decoration-none"><?= e($libro['titulo']) ?></a>
        <p class="libro-autor text-truncate mb-3"><?= e($libro['autores'] ?? '') ?></p>

        <div class="mt-auto d-flex align-items-center justify-content-between gap-2">
            <span class="libro-precio"><?= moneda($libro['precio']) ?></span>
            <?php if ($disponible): ?>
                <button type="button" class="btn btn-pya-suave btn-sm" data-agregar-carrito="<?= (int) $libro['id_libro'] ?>">
                    <i class="bi bi-cart-plus me-1"></i>Agregar
                </button>
            <?php else: ?>
                <span class="insignia stock-agotado">Agotado</span>
            <?php endif; ?>
        </div>
    </div>
</article>
