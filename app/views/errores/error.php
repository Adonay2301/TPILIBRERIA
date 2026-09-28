<?php
/**
 * Página de error (403, 404, 500).
 * Variables: $codigo, $mensaje, $detalle (excepción, solo en MODO_DEBUG)
 */
?>
<section class="container-xl px-3 px-md-4 py-5">
    <div class="tarjeta text-center py-5 px-4 mx-auto" style="max-width: 560px;">
        <p class="error-codigo"><?= (int) $codigo ?></p>
        <h1 class="titulo-pagina mb-2">
            <?= $codigo === 404 ? 'Página no encontrada' : ($codigo === 403 ? 'Acceso restringido' : 'Algo salió mal') ?>
        </h1>
        <p class="texto-suave mb-4"><?= e($mensaje) ?></p>
        <a href="<?= url('/') ?>" class="btn btn-pya">Volver al catálogo</a>
    </div>

    <?php if (!empty($detalle)): ?>
        <pre class="error-detalle mt-4"><?= e($detalle) ?></pre>
    <?php endif; ?>
</section>
