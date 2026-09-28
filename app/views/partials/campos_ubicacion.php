<?php
/**
 * Selects encadenados departamento -> municipio -> distrito.
 * Solo el distrito se guarda (id_distrito); municipio y departamento salen por relación.
 * Variables: $departamentos
 */
?>
<div class="col-sm-4">
    <label for="departamento" class="form-label">Departamento <span class="requerido">*</span></label>
    <select class="form-select" id="departamento" data-ubicacion-hijo="#municipio" data-ubicacion-url="/api/municipios/">
        <option value="">Seleccionar…</option>
        <?php foreach ($departamentos as $dep): ?>
            <option value="<?= (int) $dep['id'] ?>"><?= e($dep['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-sm-4">
    <label for="municipio" class="form-label">Municipio <span class="requerido">*</span></label>
    <select class="form-select" id="municipio" data-ubicacion-hijo="#distrito" data-ubicacion-url="/api/distritos/" disabled>
        <option value="">Seleccionar…</option>
    </select>
</div>
<div class="col-sm-4">
    <label for="distrito" class="form-label">Distrito <span class="requerido">*</span></label>
    <select class="form-select" id="distrito" name="id_distrito" disabled>
        <option value="">Seleccionar…</option>
    </select>
</div>
