<?php
/**
 * Inicio de sesión (pantalla dividida del prototipo).
 * El rol no se elige: lo detecta el servidor al validar el correo.
 */
$tituloMarca = 'Tu refugio literario te espera';
$textoMarca = 'Conéctate para explorar el catálogo, seguir tus pedidos y descubrir ediciones seleccionadas con mimo.';
?>
<div class="auth-contenedor">
    <?php require RUTA_VISTAS . '/auth/lado_marca.php'; ?>

    <div class="auth-lado-formulario">
        <a href="<?= url('/') ?>" class="d-md-none mb-5 text-decoration-none">
            <?php $marcaOscura = true; require RUTA_VISTAS . '/partials/marca.php'; ?>
        </a>

        <div class="auth-tarjeta">
            <h2 class="titulo-pagina mb-1">Iniciar sesión</h2>
            <p class="texto-suave small mb-4">Bienvenido de nuevo a Punto y Aparte</p>

            <form action="<?= url('/login') ?>" method="post" novalidate>
                <?= csrf_campo() ?>

                <div class="mb-3">
                    <label for="correo" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" id="correo" name="correo" value="<?= e(old('correo')) ?>"
                           placeholder="tu@correo.com" autocomplete="email" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="contrasena" class="form-label">Contraseña</label>
                    <div class="position-relative">
                        <input type="password" class="form-control pe-5" id="contrasena" name="contrasena"
                               placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" class="btn btn-icono position-absolute top-50 end-0 translate-middle-y me-1"
                                data-toggle-password="#contrasena" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-end mb-4">
                    <button type="button" class="btn-enlace" data-bs-toggle="collapse" data-bs-target="#ayudaContrasena">
                        ¿Olvidaste tu contraseña?
                    </button>
                </div>
                <div class="collapse mb-3" id="ayudaContrasena">
                    <div class="alert alert-info small mb-0">
                        Escríbenos a <strong>hola@puntoyaparte.sv</strong> o llama al <strong>+503 2222-3344</strong>
                        y te ayudaremos a restablecerla.
                    </div>
                </div>

                <button type="submit" class="btn btn-pya w-100 py-2">Iniciar sesión</button>
            </form>

            <p class="mt-4 mb-0 small text-center texto-suave">
                ¿No tienes cuenta? <a href="<?= url('/registro') ?>" class="fw-semibold texto-naranja text-decoration-none">Crear cuenta</a>
            </p>
        </div>

        <a href="<?= url('/catalogo') ?>" class="small texto-suave mt-4 text-decoration-none">← Seguir explorando el catálogo</a>
    </div>
</div>
