<?php
/**
 * Catálogo público.
 * Variables: $libros, $filtros, $paginacion, $orden, $ordenes, $vistaLista, $totalCatalogo,
 *            $categorias, $autores, $editoriales
 */

// Chips de filtros activos: [texto, url para quitarlo]
$nombres = fn (array $lista) => array_column($lista, 'nombre', 'id');
$chips = [];
if ($filtros['q'] !== '') $chips[] = ['«' . $filtros['q'] . '»', urlSin('q')];
foreach ($filtros['categorias'] as $id) $chips[] = [$nombres($categorias)[$id] ?? 'Categoría', urlSin('categoria', $id)];
foreach ($filtros['autores'] as $id) $chips[] = [$nombres($autores)[$id] ?? 'Autor', urlSin('autor', $id)];
foreach ($filtros['editoriales'] as $id) $chips[] = [$nombres($editoriales)[$id] ?? 'Editorial', urlSin('editorial', $id)];
if ($filtros['disponibilidad'] !== '') $chips[] = [$filtros['disponibilidad'] === 'disponibles' ? 'En stock' : 'Agotados', urlSin('disponibilidad')];
if ($filtros['precio_min'] !== '') $chips[] = ['Desde ' . moneda($filtros['precio_min']), urlSin('precio_min')];
if ($filtros['precio_max'] !== '') $chips[] = ['Hasta ' . moneda($filtros['precio_max']), urlSin('precio_max')];
if ($filtros['anio_desde'] !== '') $chips[] = ['Desde ' . $filtros['anio_desde'], urlSin('anio_desde')];
if ($filtros['anio_hasta'] !== '') $chips[] = ['Hasta ' . $filtros['anio_hasta'], urlSin('anio_hasta')];
?>
<!-- Franja de búsqueda bajo el navbar -->
<div class="franja-busqueda">
    <div class="container-xl px-3 px-md-4 py-3 d-flex align-items-center gap-3">
        <form action="<?= url('/catalogo') ?>" method="get" class="buscador flex-grow-1" style="max-width:36rem" role="search">
            <i class="bi bi-search"></i>
            <input type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Buscar por título, autor o ISBN..." aria-label="Buscar en el catálogo">
        </form>
        <p class="small mb-0 d-none d-md-block" style="color:rgba(255,255,255,.45)"><?= (int) $totalCatalogo ?> títulos disponibles en nuestro catálogo</p>
    </div>
</div>

<div class="container-xl px-3 px-md-4 py-4 py-md-5">
    <div class="d-flex align-items-baseline justify-content-between mb-4">
        <div>
            <h1 class="titulo-pagina mb-1">Catálogo</h1>
            <p class="texto-suave small mb-0">Explora nuestra colección completa de títulos</p>
        </div>
        <a href="<?= url('/catalogo') ?>" class="btn-enlace text-decoration-none" style="letter-spacing:.1em">LIMPIAR</a>
    </div>

    <div class="row g-4">
        <!-- Filtros: columna en escritorio -->
        <aside class="col-lg-3 d-none d-lg-block">
            <?php $prefijoFiltro = 'd'; require RUTA_VISTAS . '/catalogo/filtros.php'; ?>
        </aside>

        <div class="col-lg-9">
            <?php if ($chips): ?>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <?php foreach ($chips as [$texto, $urlQuitar]): ?>
                        <a href="<?= e($urlQuitar) ?>" class="chip-filtro"><?= e($texto) ?> <i class="bi bi-x"></i></a>
                    <?php endforeach; ?>
                    <a href="<?= url('/catalogo') ?>" class="btn-enlace text-decoration-none">LIMPIAR TODO</a>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <p class="small fw-medium mb-0">
                    Mostrando <strong><?= (int) $paginacion['total'] ?></strong> <?= $paginacion['total'] === 1 ? 'libro' : 'libros' ?>
                </p>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-pya-suave btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#filtrosMovil">
                        <i class="bi bi-funnel me-1"></i>Filtros
                    </button>
                    <select class="form-select form-select-sm" style="width:auto" aria-label="Ordenar" data-navegar>
                        <?php foreach ($ordenes as $clave => $texto): ?>
                            <option value="<?= e(urlCon('orden', $clave)) ?>" <?= $orden === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Tipo de vista">
                        <a href="<?= e(urlSin('vista')) ?>" class="btn <?= $vistaLista ? 'btn-light border' : 'btn-pya' ?>" title="Cuadrícula"><i class="bi bi-grid-3x3-gap"></i></a>
                        <a href="<?= e(urlCon('vista', 'lista')) ?>" class="btn <?= $vistaLista ? 'btn-pya' : 'btn-light border' ?>" title="Lista"><i class="bi bi-list-ul"></i></a>
                    </div>
                </div>
            </div>

            <?php if (!$libros): ?>
                <div class="tarjeta estado-vacio">
                    <i class="bi bi-book"></i>
                    <h3 class="h5 font-serif mb-0">No encontramos libros con estos filtros</h3>
                    <p class="texto-suave small mb-2" style="max-width:20rem">Prueba ajustando el rango de precio o quitando alguna categoría.</p>
                    <a href="<?= url('/catalogo') ?>" class="btn btn-pya">Limpiar filtros</a>
                </div>
            <?php elseif ($vistaLista): ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($libros as $libro) require RUTA_VISTAS . '/partials/libro_fila.php'; ?>
                </div>
            <?php else: ?>
                <div class="row row-cols-2 row-cols-md-3 g-3 g-md-4">
                    <?php foreach ($libros as $libro): ?>
                        <div class="col"><?php require RUTA_VISTAS . '/partials/libro_card.php'; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php $etiqueta = 'libros'; require RUTA_VISTAS . '/partials/paginacion.php'; ?>
        </div>
    </div>
</div>

<!-- Filtros: panel lateral en celular -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="filtrosMovil" aria-labelledby="filtrosMovilTitulo">
    <div class="offcanvas-header border-bottom">
        <h2 class="offcanvas-title h5 font-serif" id="filtrosMovilTitulo">Filtros</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <?php $prefijoFiltro = 'm'; require RUTA_VISTAS . '/catalogo/filtros.php'; ?>
    </div>
</div>
