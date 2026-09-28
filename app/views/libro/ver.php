<?php
/**
 * Detalle público del libro.
 * Variables: $libro, $relacionados
 */
$stock = (int) $libro['stock_actual'];
$estado = estadoStock($stock, (int) $libro['stock_minimo']);
?>
<div class="container-xl px-3 px-md-4 py-4 py-md-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="<?= url('/catalogo') ?>">Catálogo</a></li>
            <li class="breadcrumb-item"><a href="<?= url('/catalogo?categoria[]=' . (int) $libro['id_categoria']) ?>"><?= e($libro['categoria']) ?></a></li>
            <li class="breadcrumb-item active text-truncate" aria-current="page"><?= e($libro['titulo']) ?></li>
        </ol>
    </nav>

    <div class="row g-4 g-lg-5">
        <div class="col-md-5 col-lg-4">
            <div class="detalle-portada position-relative">
                <?php require RUTA_VISTAS . '/partials/portada.php'; ?>
                <?php if (!empty($libro['es_nuevo'])): ?><span class="etiqueta-nuevo position-absolute top-0 start-0 m-3">NUEVO</span><?php endif; ?>
            </div>
        </div>

        <div class="col-md-7 col-lg-8">
            <span class="libro-categoria"><?= e($libro['categoria']) ?></span>
            <h1 class="titulo-pagina mt-1 mb-2" style="font-size:2.2rem"><?= e($libro['titulo']) ?></h1>
            <p class="fs-5 texto-suave mb-4"><?= e($libro['autores'] ?? '') ?></p>

            <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                <span class="font-serif fw-bold" style="font-size:2rem"><?= moneda($libro['precio']) ?></span>
                <span class="insignia <?= $estado['clase'] ?>"><span class="punto"></span><?= e($estado['texto']) ?></span>
            </div>

            <?php if ($stock > 0): ?>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <div class="input-group" style="width:8.5rem">
                        <button class="btn btn-pya-suave" type="button" data-cantidad-menos="#cantidadLibro" aria-label="Menos">−</button>
                        <input type="number" class="form-control text-center" id="cantidadLibro" value="1" min="1" max="<?= $stock ?>" aria-label="Cantidad">
                        <button class="btn btn-pya-suave" type="button" data-cantidad-mas="#cantidadLibro" aria-label="Más">+</button>
                    </div>
                    <button type="button" class="btn btn-pya-naranja px-4" data-agregar-carrito="<?= (int) $libro['id_libro'] ?>" data-cantidad-desde="#cantidadLibro">
                        <i class="bi bi-cart-plus me-2"></i>Agregar al carrito
                    </button>
                </div>
                <p class="small texto-suave mb-4">
                    <?= $stock <= (int) $libro['stock_minimo'] ? "¡Solo quedan $stock unidades!" : 'Envío gratis en compras mayores a ' . moneda(ENVIO_GRATIS_DESDE) . '.' ?>
                </p>
            <?php else: ?>
                <div class="alert alert-secondary small">Este título está agotado por el momento.</div>
            <?php endif; ?>

            <?php if ($libro['sinopsis']): ?>
                <h2 class="h6 etiqueta-seccion mt-4">Sinopsis</h2>
                <p class="lh-lg"><?= nl2br(e($libro['sinopsis'])) ?></p>
            <?php endif; ?>

            <dl class="row small tarjeta p-3 mx-0 mt-4 ficha-tecnica">
                <dt class="col-5 col-sm-3">Editorial</dt><dd class="col-7 col-sm-3"><?= e($libro['editorial']) ?></dd>
                <dt class="col-5 col-sm-3">Año</dt><dd class="col-7 col-sm-3"><?= e($libro['anio_publicacion'] ?? '—') ?></dd>
                <dt class="col-5 col-sm-3">Páginas</dt><dd class="col-7 col-sm-3"><?= e($libro['numero_paginas'] ?? '—') ?></dd>
                <dt class="col-5 col-sm-3">Idioma</dt><dd class="col-7 col-sm-3"><?= e($libro['idioma']) ?></dd>
                <dt class="col-5 col-sm-3">ISBN</dt><dd class="col-7 col-sm-9 font-monospace mb-0"><?= e($libro['isbn']) ?></dd>
            </dl>
        </div>
    </div>

    <?php if ($relacionados): ?>
        <section class="mt-5">
            <div class="d-flex align-items-baseline justify-content-between border-bottom pb-3 mb-4">
                <h2 class="font-serif h4 mb-0">También en <?= e($libro['categoria']) ?></h2>
                <a href="<?= url('/catalogo?categoria[]=' . (int) $libro['id_categoria']) ?>" class="btn-enlace text-decoration-none">Ver todos →</a>
            </div>
            <div class="row row-cols-2 row-cols-md-4 g-3 g-md-4">
                <?php foreach ($relacionados as $libro): ?>
                    <div class="col"><?php require RUTA_VISTAS . '/partials/libro_card.php'; ?></div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
