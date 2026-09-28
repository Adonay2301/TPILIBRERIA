<?php
/**
 * Crear / editar autor.
 * Variables: $autor (null al crear)
 */
$editando = $autor !== null;
$v = fn (string $campo) => old($campo, $editando ? (string) ($autor[$campo] ?? '') : '');
?>
<a href="<?= url('/admin/autores') ?>" class="small text-decoration-none">← Volver a autores</a>
<h1 class="titulo-pagina mt-2 mb-4"><?= $editando ? 'Editar autor' : 'Nuevo autor' ?></h1>

<form action="<?= url($editando ? '/admin/autores/actualizar/' . (int) $autor['id_autor'] : '/admin/autores/guardar') ?>" method="post"
      class="tarjeta p-4" style="max-width:640px" novalidate>
    <?= csrf_campo() ?>
    <div class="row g-3">
        <div class="col-sm-6">
            <label for="nombres" class="form-label">Nombres <span class="requerido">*</span></label>
            <input type="text" class="form-control" id="nombres" name="nombres" maxlength="80" value="<?= e($v('nombres')) ?>" required>
        </div>
        <div class="col-sm-6">
            <label for="apellidos" class="form-label">Apellidos o seudónimo <span class="requerido">*</span></label>
            <input type="text" class="form-control" id="apellidos" name="apellidos" maxlength="80" value="<?= e($v('apellidos')) ?>" required>
        </div>
        <div class="col-sm-6">
            <label for="nacionalidad" class="form-label">Nacionalidad</label>
            <input type="text" class="form-control" id="nacionalidad" name="nacionalidad" maxlength="60" placeholder="Ej. Salvadoreña" value="<?= e($v('nacionalidad')) ?>">
        </div>
        <div class="col-12">
            <label for="biografia" class="form-label">Biografía</label>
            <textarea class="form-control" id="biografia" name="biografia" rows="5" placeholder="Breve reseña biográfica del autor…"><?= e($v('biografia')) ?></textarea>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="<?= url('/admin/autores') ?>" class="btn btn-light border">Cancelar</a>
        <button class="btn btn-pya"><?= $editando ? 'Guardar cambios' : 'Agregar autor' ?></button>
    </div>
</form>
