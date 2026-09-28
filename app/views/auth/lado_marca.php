<?php
/**
 * Mitad izquierda (teal) de las pantallas de login y registro.
 * Variables: $tituloMarca, $textoMarca
 */
?>
<div class="auth-lado-marca">
    <div class="position-absolute top-0 start-0 p-4 p-lg-5" style="z-index:1">
        <a href="<?= url('/') ?>" class="text-decoration-none">
            <?php $marcaOscura = false; require RUTA_VISTAS . '/partials/marca.php'; ?>
        </a>
    </div>
    <div class="flex-grow-1 d-flex flex-column justify-content-center px-5 position-relative" style="z-index:1">
        <h1 class="text-white fw-semibold mb-4" style="font-size:3rem; max-width:460px; line-height:1.15"><?= e($tituloMarca) ?></h1>
        <p class="mb-0" style="color:rgba(255,255,255,.58); max-width:380px"><?= e($textoMarca) ?></p>
    </div>
    <div class="auth-punto" aria-hidden="true">;</div>
</div>
