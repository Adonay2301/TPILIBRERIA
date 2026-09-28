<?php
/**
 * Libro en vista de lista del catálogo.
 * Variables: $libro
 */
$disponible = (int) $libro['stock_actual'] > 0;
$urlLibro = url('/libro/' . (int) $libro['id_libro']);
?>
<article class="tarjeta d-flex align-items-center gap-3 p-3 libro-fila">
    <a href="<?= $urlLibro ?>" class="portada-mini flex-shrink-0" style="width:60px;height:84px">
        <?php require RUTA_VISTAS . '/partials/portada.php'; ?>
    </a>
    <div class="flex-grow-1 min-w-0">
        <span class="libro-categoria"><?= e($libro['categoria']) ?></span>
        <a href="<?= $urlLibro ?>" class="libro-titulo d-block text-truncate text-decoration-none"><?= e($libro['titulo']) ?></a>
        <p class="libro-autor mb-0 text-truncate">
            <?= e($libro['autores'] ?? '') ?> · <?= e($libro['editorial']) ?><?= $libro['anio_publicacion'] ? ' · ' . (int) $libro['anio_publicacion'] : '' ?>
        </p>
    </div>
    <div class="d-flex flex-column flex-sm-row align-items-end align-items-sm-center gap-2 gap-sm-3 flex-shrink-0">
        <?php $st = estadoStock((int) $libro['stock_actual'], (int) $libro['stock_minimo']); ?>
        <span class="insignia <?= $st['clase'] ?> d-none d-md-inline-flex"><?= e($disponible ? 'Disponible' : 'Agotado') ?></span>
        <span class="libro-precio"><?= moneda($libro['precio']) ?></span>
        <?php if ($disponible): ?>
            <button type="button" class="btn btn-pya btn-sm" data-agregar-carrito="<?= (int) $libro['id_libro'] ?>">Agregar</button>
        <?php endif; ?>
    </div>
</article>
