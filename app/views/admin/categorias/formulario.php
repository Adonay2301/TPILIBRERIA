<?php
/**
 * Crear / editar categoría.
 * Variables: $categoria (null al crear)
 */
$editando = $categoria !== null;
$v = fn (string $campo) => old($campo, $editando ? (string) ($categoria[$campo] ?? '') : '');
?>
<a href="<?= url('/admin/categorias') ?>" class="small text-decoration-none">← Volver a categorías</a>
<h1 class="titulo-pagina mt-2 mb-4"><?= $editando ? 'Editar categoría' : 'Nueva categoría' ?></h1>

<form action="<?= url($editando ? '/admin/categorias/actualizar/' . (int) $categoria['id_categoria'] : '/admin/categorias/guardar') ?>" method="post"
      class="tarjeta p-4" style="max-width:560px" novalidate>
    <?= csrf_campo() ?>
    <div class="mb-3">
        <label for="nombre" class="form-label">Nombre <span class="requerido">*</span></label>
        <input type="text" class="form-control" id="nombre" name="nombre" maxlength="60" value="<?= e($v('nombre')) ?>" required>
    </div>
    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <input type="text" class="form-control" id="descripcion" name="descripcion" maxlength="255" placeholder="Breve descripción" value="<?= e($v('descripcion')) ?>">
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="<?= url('/admin/categorias') ?>" class="btn btn-light border">Cancelar</a>
        <button class="btn btn-pya">Guardar</button>
    </div>
</form>
