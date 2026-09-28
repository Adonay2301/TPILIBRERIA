<?php
/**
 * Botones Editar / Eliminar de Autores, Categorías y Editoriales.
 * Eliminar se desactiva si el registro tiene libros (la base de datos lo impediría).
 * Variables: $urlEditar, $urlEliminar, $libros, $nombreRegistro
 */
?>
<div class="d-flex justify-content-center gap-1">
    <a href="<?= url($urlEditar) ?>" class="btn-icono" title="Editar"><i class="bi bi-pencil-square"></i></a>
    <?php if ((int) $libros > 0): ?>
        <span class="btn-icono opacity-50" title="No se puede eliminar: tiene <?= (int) $libros ?> libro(s) asociado(s)"><i class="bi bi-trash3"></i></span>
    <?php else: ?>
        <form action="<?= url($urlEliminar) ?>" method="post" data-confirmar="¿Eliminar «<?= e($nombreRegistro) ?>»? Esta acción no se puede deshacer.">
            <?= csrf_campo() ?>
            <button class="btn-icono peligro" title="Eliminar"><i class="bi bi-trash3"></i></button>
        </form>
    <?php endif; ?>
</div>
