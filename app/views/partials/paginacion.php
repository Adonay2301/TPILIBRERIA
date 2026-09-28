<?php
/**
 * Paginación. Variables: $paginacion (resultado de paginar()), $etiqueta (ej. 'libros')
 */
if (empty($paginacion) || $paginacion['total'] === 0) {
    return;
}

$pag = $paginacion['pagina'];
$ultima = $paginacion['total_paginas'];
$desde = $paginacion['offset'] + 1;
$hasta = min($paginacion['offset'] + $paginacion['por_pagina'], $paginacion['total']);

// Números visibles: primera, última y dos alrededor de la actual
$numeros = array_unique(array_filter(
    [1, $pag - 1, $pag, $pag + 1, $ultima],
    fn ($n) => $n >= 1 && $n <= $ultima
));
sort($numeros);
?>
<div class="paginacion-pya">
    <p class="mb-0">
        Mostrando <?= $desde ?>–<?= $hasta ?> de
        <strong><?= (int) $paginacion['total'] ?> <?= e($etiqueta ?? 'registros') ?></strong>
    </p>

    <?php if ($ultima > 1): ?>
        <nav aria-label="Paginación">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $pag <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e(urlPagina($pag - 1)) ?>" aria-label="Anterior"><i class="bi bi-chevron-left"></i></a>
                </li>
                <?php $previo = 0; foreach ($numeros as $n): ?>
                    <?php if ($n - $previo > 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <li class="page-item <?= $n === $pag ? 'active' : '' ?>">
                        <a class="page-link" href="<?= e(urlPagina($n)) ?>"><?= $n ?></a>
                    </li>
                <?php $previo = $n; endforeach; ?>
                <li class="page-item <?= $pag >= $ultima ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e(urlPagina($pag + 1)) ?>" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
