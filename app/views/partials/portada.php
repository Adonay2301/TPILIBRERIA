<?php
/**
 * Portada de un libro. Si no tiene imagen (libros.portada es NULL) dibuja una portada genérica.
 * Variables: $libro (necesita titulo, portada, id_libro y opcionalmente autores)
 */
$urlPortada = portadaUrl($libro['portada'] ?? null);
?>
<?php if ($urlPortada): ?>
    <img src="<?= e($urlPortada) ?>" alt="Portada de <?= e($libro['titulo']) ?>" class="portada-img" loading="lazy">
<?php else: ?>
    <div class="portada-generica pg-color-<?= (int) ($libro['id_libro'] ?? 0) % 6 ?>" role="img" aria-label="<?= e($libro['titulo']) ?>">
        <span class="pg-titulo"><?= e($libro['titulo']) ?></span>
        <?php if (!empty($libro['autores'])): ?>
            <span class="pg-autor"><?= e($libro['autores']) ?></span>
        <?php endif; ?>
        <span class="pg-marca">;</span>
    </div>
<?php endif; ?>
