<?php
/**
 * Alta / edición de empleado. Todos los empleados tienen el mismo nivel de acceso.
 * Variables: $empleado (null al crear), $contrasenaSugerida (solo al crear)
 */
$editando = $empleado !== null;
$v = fn (string $campo, string $defecto = '') => old($campo, $editando ? (string) ($empleado[$campo] ?? '') : $defecto);
$accion = $editando ? '/admin/empleados/actualizar/' . (int) $empleado['id_empleado'] : '/admin/empleados/guardar';
?>
<a href="<?= url($editando ? '/admin/empleados/' . (int) $empleado['id_empleado'] : '/admin/empleados') ?>" class="small text-decoration-none">← Volver</a>
<h1 class="titulo-pagina mt-2 mb-4"><?= $editando ? 'Editar empleado' : 'Nuevo empleado' ?></h1>

<form action="<?= url($accion) ?>" method="post" class="tarjeta p-4" style="max-width:720px" novalidate>
    <?= csrf_campo() ?>

    <h2 class="etiqueta-seccion mb-3">Datos personales</h2>
    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <label for="nombres" class="form-label">Nombres <span class="requerido">*</span></label>
            <input type="text" class="form-control" id="nombres" name="nombres" maxlength="80" value="<?= e($v('nombres')) ?>" required>
        </div>
        <div class="col-sm-6">
            <label for="apellidos" class="form-label">Apellidos <span class="requerido">*</span></label>
            <input type="text" class="form-control" id="apellidos" name="apellidos" maxlength="80" value="<?= e($v('apellidos')) ?>" required>
        </div>
        <div class="col-sm-6">
            <label for="dui" class="form-label">DUI <span class="requerido">*</span></label>
            <input type="text" class="form-control font-monospace" id="dui" name="dui" maxlength="10" placeholder="01234567-8" pattern="\d{8}-\d" value="<?= e($v('dui')) ?>" required>
        </div>
        <div class="col-sm-6">
            <label for="telefono" class="form-label">Teléfono</label>
            <div class="input-group">
                <span class="input-group-text">+503</span>
                <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="7123-4567" value="<?= e(preg_replace('/^\+503 /', '', $v('telefono'))) ?>">
            </div>
        </div>
    </div>

    <h2 class="etiqueta-seccion mb-3">Acceso al sistema</h2>
    <div class="row g-3">
        <div class="col-sm-6">
            <label class="form-label">Código de empleado</label>
            <input type="text" class="form-control" value="<?= $editando ? e(codigoEmpleado($empleado['id_empleado'])) : 'Se asigna al guardar' ?>" readonly disabled>
        </div>
        <div class="col-sm-6">
            <label for="fecha_contratacion" class="form-label">Fecha de ingreso <span class="requerido">*</span></label>
            <input type="date" class="form-control" id="fecha_contratacion" name="fecha_contratacion" max="<?= date('Y-m-d') ?>" value="<?= e($v('fecha_contratacion', date('Y-m-d'))) ?>" required>
        </div>
        <div class="col-12">
            <label for="correo" class="form-label">Correo electrónico <span class="requerido">*</span></label>
            <input type="email" class="form-control" id="correo" name="correo" maxlength="150" placeholder="empleado@puntoyaparte.com.sv" value="<?= e($v('correo')) ?>" required>
        </div>
        <?php if (!$editando): ?>
            <div class="col-12">
                <label for="contrasena" class="form-label">Contraseña temporal <span class="requerido">*</span></label>
                <div class="input-group">
                    <input type="text" class="form-control font-monospace" id="contrasena" name="contrasena" minlength="8" value="<?= e($contrasenaSugerida) ?>" required>
                    <button type="button" class="btn btn-pya-suave" onclick="navigator.clipboard.writeText(document.getElementById('contrasena').value); PYA.aviso('Contraseña copiada.')">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <div class="form-text">Se generó una contraseña segura; puedes cambiarla. Compártela con el empleado por un medio seguro.</div>
            </div>
        <?php endif; ?>
    </div>

    <p class="small texto-suave rounded-3 p-3 mt-4 mb-0" style="background:var(--color-cream)">
        Todos los empleados comparten el mismo nivel de acceso: pedidos, preparación e inventario en solo lectura.
    </p>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="<?= url('/admin/empleados') ?>" class="btn btn-light border">Cancelar</a>
        <button class="btn btn-pya">Guardar empleado</button>
    </div>
</form>
