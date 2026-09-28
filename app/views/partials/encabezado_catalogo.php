<?php
/**
 * Encabezado común de Autores, Categorías y Editoriales.
 * Variables: $tituloSeccion, $subtituloSeccion, $urlNuevo, $textoNuevo, $esAdmin
 */
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="titulo-pagina mb-1"><?= e($tituloSeccion) ?></h1>
        <?php if (!$esAdmin): ?>
            <p class="small texto-suave mb-0"><?= e($subtituloSeccion) ?> · Solo lectura. Contacta al administrador para modificar.</p>
        <?php endif; ?>
    </div>
    <?php if ($esAdmin): ?>
        <a href="<?= url($urlNuevo) ?>" class="btn btn-pya"><i class="bi bi-plus-lg me-1"></i><?= e($textoNuevo) ?></a>
    <?php endif; ?>
</div>
