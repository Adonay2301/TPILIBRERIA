<?php
/**
 * Novedades: libro destacado + últimas incorporaciones.
 * Variables: $destacado, $libros, $paginacion, $totalNuevos, $categorias, $idCategoria, $orden, $ordenes
 */
?>
<?php if ($destacado): ?>
    <section class="hero-pya">
        <div class="container-xl px-3 px-md-4 py-5">
            <div class="row g-5 align-items-center">
                <div class="col-md-7 col-lg-8">
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                        <span class="insignia" style="background:rgba(232,133,74,.2);color:var(--color-orange-light);letter-spacing:.2em;text-transform:uppercase;font-size:.62rem">Destacado</span>
                        <span class="small" style="color:rgba(255,255,255,.5)"><i class="bi bi-calendar3 me-1"></i>Ingresó <?= fecha($destacado['fecha_ingreso']) ?></span>
                    </div>
                    <p class="etiqueta-seccion mb-2" style="color:var(--color-orange-light)"><?= e($destacado['categoria']) ?></p>
                    <h1 class="text-white fw-semibold mb-3" style="font-size:clamp(2rem,5vw,3rem)"><?= e($destacado['titulo']) ?></h1>
                    <p class="fs-5 mb-4" style="color:rgba(255,255,255,.7)"><?= e($destacado['autores'] ?? '') ?></p>
                    <?php if ($destacado['sinopsis']): ?>
                        <p class="mb-4" style="color:rgba(255,255,255,.6);max-width:34rem"><?= e($destacado['sinopsis']) ?></p>
                    <?php endif; ?>
                    <div class="d-flex flex-wrap gap-4 mb-4">
                        <div><span class="etiqueta-seccion d-block" style="color:rgba(255,255,255,.35)">Editorial</span><span style="color:rgba(255,255,255,.75)"><?= e($destacado['editorial']) ?></span></div>
                        <?php if ($destacado['numero_paginas']): ?>
                            <div><span class="etiqueta-seccion d-block" style="color:rgba(255,255,255,.35)">Páginas</span><span style="color:rgba(255,255,255,.75)"><?= (int) $destacado['numero_paginas'] ?></span></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="font-serif fw-bold text-white" style="font-size:2rem"><?= moneda($destacado['precio']) ?></span>
                        <?php if ((int) $destacado['stock_actual'] > 0): ?>
                            <button type="button" class="btn btn-pya-naranja px-4 py-2" data-agregar-carrito="<?= (int) $destacado['id_libro'] ?>">
                                Agregar al carrito <i class="bi bi-chevron-right ms-1"></i>
                            </button>
                        <?php endif; ?>
                        <a href="<?= url('/libro/' . (int) $destacado['id_libro']) ?>" class="btn btn-pya-contorno-claro px-4 py-2">Ver detalle</a>
                    </div>
                </div>
                <div class="col-md-5 col-lg-4 d-flex justify-content-center justify-content-md-end">
                    <div class="hero-portada">
                        <?php $libro = $destacado; require RUTA_VISTAS . '/partials/portada.php'; ?>
                    </div>
                </div>
            </div>
        </div>
        <svg viewBox="0 0 1440 40" class="hero-ola" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0,20 C360,50 1080,-10 1440,20 L1440,40 L0,40 Z" fill="var(--color-cream)"/>
        </svg>
    </section>
<?php endif; ?>

<!-- Filtro por categoría y orden -->
<section class="border-bottom">
    <div class="container-xl px-3 px-md-4 py-4 d-flex flex-wrap align-items-center gap-2">
        <span class="etiqueta-seccion me-2">Filtrar:</span>
        <a href="<?= e(urlSin('categoria')) ?>" class="chip-categoria <?= $idCategoria === 0 ? 'active' : '' ?>">Todos</a>
        <?php foreach ($categorias as $c): ?>
            <a href="<?= e(urlCon('categoria', (string) $c['id'])) ?>" class="chip-categoria <?= $idCategoria === (int) $c['id'] ? 'active' : '' ?>"><?= e($c['nombre']) ?></a>
        <?php endforeach; ?>
        <div class="ms-auto d-flex align-items-center gap-2 small texto-suave">
            <span>Ordenar:</span>
            <select class="form-select form-select-sm" style="width:auto" data-navegar aria-label="Ordenar">
                <?php foreach ($ordenes as $clave => $texto): ?>
                    <option value="<?= e(urlCon('orden', $clave)) ?>" <?= $orden === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</section>

<section class="container-xl px-3 px-md-4 py-5">
    <div class="d-flex align-items-baseline justify-content-between mb-4">
        <div>
            <h2 class="titulo-pagina mb-1">Últimas incorporaciones</h2>
            <p class="texto-suave small mb-0"><?= (int) $totalNuevos ?> <?= $totalNuevos === 1 ? 'título nuevo' : 'títulos nuevos' ?> en los últimos 30 días</p>
        </div>
        <a href="<?= url('/catalogo') ?>" class="btn-enlace text-decoration-none d-none d-md-inline">Ver catálogo completo <i class="bi bi-chevron-right"></i></a>
    </div>

    <?php if ($libros): ?>
        <div class="row row-cols-2 row-cols-md-4 g-3 g-md-4">
            <?php foreach ($libros as $libro): ?>
                <div class="col"><?php require RUTA_VISTAS . '/partials/libro_card.php'; ?></div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="tarjeta estado-vacio"><i class="bi bi-book"></i><p class="mb-0">No hay títulos en esta categoría.</p></div>
    <?php endif; ?>

    <?php $etiqueta = 'títulos'; require RUTA_VISTAS . '/partials/paginacion.php'; ?>
</section>
