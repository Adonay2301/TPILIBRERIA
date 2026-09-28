<?php
/**
 * Registro de clientes. La dirección usa la división territorial 2024:
 * departamento -> municipio -> distrito (los selects se llenan con fetch).
 * Variables: $departamentos
 */
$tituloMarca = 'Crea tu cuenta de lector';
$textoMarca = 'Guarda tu dirección, compra en pocos pasos y sigue tus pedidos hasta tu puerta.';
?>
<div class="auth-contenedor">
    <?php require RUTA_VISTAS . '/auth/lado_marca.php'; ?>

    <div class="auth-lado-formulario">
        <a href="<?= url('/') ?>" class="d-md-none mb-4 text-decoration-none">
            <?php $marcaOscura = true; require RUTA_VISTAS . '/partials/marca.php'; ?>
        </a>

        <div class="auth-tarjeta" style="max-width:560px">
            <h2 class="titulo-pagina mb-1">Crear cuenta</h2>
            <p class="texto-suave small mb-4">Solo los clientes registrados pueden comprar.</p>

            <form action="<?= url('/registro') ?>" method="post" novalidate>
                <?= csrf_campo() ?>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="nombres" class="form-label">Nombres <span class="requerido">*</span></label>
                        <input type="text" class="form-control" id="nombres" name="nombres" maxlength="80" value="<?= e(old('nombres')) ?>" required>
                    </div>
                    <div class="col-sm-6">
                        <label for="apellidos" class="form-label">Apellidos <span class="requerido">*</span></label>
                        <input type="text" class="form-control" id="apellidos" name="apellidos" maxlength="80" value="<?= e(old('apellidos')) ?>" required>
                    </div>
                    <div class="col-sm-7">
                        <label for="correo" class="form-label">Correo electrónico <span class="requerido">*</span></label>
                        <input type="email" class="form-control" id="correo" name="correo" maxlength="150" value="<?= e(old('correo')) ?>" autocomplete="email" required>
                    </div>
                    <div class="col-sm-5">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <div class="input-group">
                            <span class="input-group-text">+503</span>
                            <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="7123-4567"
                                   value="<?= e(preg_replace('/^\+503 /', '', old('telefono'))) ?>">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label for="contrasena" class="form-label">Contraseña <span class="requerido">*</span></label>
                        <input type="password" class="form-control" id="contrasena" name="contrasena" minlength="8" autocomplete="new-password" required>
                        <div class="form-text">Mínimo 8 caracteres.</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="contrasena_confirmacion" class="form-label">Confirmar contraseña <span class="requerido">*</span></label>
                        <input type="password" class="form-control" id="contrasena_confirmacion" name="contrasena_confirmacion" autocomplete="new-password" required>
                    </div>

                    <div class="col-12"><p class="etiqueta-seccion mt-2 mb-0">Dirección de entrega</p></div>

                    <?php require RUTA_VISTAS . '/partials/campos_ubicacion.php'; ?>

                    <div class="col-12">
                        <label for="direccion" class="form-label">Dirección <span class="requerido">*</span></label>
                        <input type="text" class="form-control" id="direccion" name="direccion" maxlength="255"
                               placeholder="Colonia, calle, pasaje, número de casa" value="<?= e(old('direccion')) ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="referencia" class="form-label">Punto de referencia</label>
                        <input type="text" class="form-control" id="referencia" name="referencia" maxlength="255"
                               placeholder="Ej. Frente al parque, portón negro" value="<?= e(old('referencia')) ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-pya w-100 py-2 mt-4">Crear cuenta</button>
            </form>

            <p class="mt-4 mb-0 small text-center texto-suave">
                ¿Ya tienes cuenta? <a href="<?= url('/login') ?>" class="fw-semibold texto-naranja text-decoration-none">Iniciar sesión</a>
            </p>
        </div>
    </div>
</div>
