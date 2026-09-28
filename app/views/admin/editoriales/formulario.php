<?php
/**
 * Crear / editar editorial.
 * Variables: $editorial (null al crear)
 */
$editando = $editorial !== null;
$v = fn (string $campo) => old($campo, $editando ? (string) ($editorial[$campo] ?? '') : '');
?>
<a href="<?= url('/admin/editoriales') ?>" class="small text-decoration-none">← Volver a editoriales</a>
<h1 class="titulo-pagina mt-2 mb-4"><?= $editando ? 'Editar editorial' : 'Nueva editorial' ?></h1>

<form action="<?= url($editando ? '/admin/editoriales/actualizar/' . (int) $editorial['id_editorial'] : '/admin/editoriales/guardar') ?>" method="post"
      class="tarjeta p-4" style="max-width:560px" novalidate>
    <?= csrf_campo() ?>
    <div class="mb-3">
        <label for="nombre" class="form-label">Nombre <span class="requerido">*</span></label>
        <input type="text" class="form-control" id="nombre" name="nombre" maxlength="100" value="<?= e($v('nombre')) ?>" required>
    </div>
    <div class="mb-3">
        <label for="pais" class="form-label">País</label>
        <input type="text" class="form-control" id="pais" name="pais" maxlength="60" placeholder="Ej. El Salvador" value="<?= e($v('pais')) ?>">
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="<?= url('/admin/editoriales') ?>" class="btn btn-light border">Cancelar</a>
        <button class="btn btn-pya">Guardar</button>
    </div>
</form>
