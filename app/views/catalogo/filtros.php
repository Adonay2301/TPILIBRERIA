<?php
/**
 * Panel de filtros del catálogo (columna izquierda en escritorio, offcanvas en celular).
 * Variables: $filtros, $categorias, $autores, $editoriales, $orden, $vistaLista
 */
$autoresVisibles = 6;
$pf = $prefijoFiltro ?? 'd'; // prefijo para que los id no se repitan (escritorio y celular)
?>
<form action="<?= url('/catalogo') ?>" method="get" class="filtros-catalogo" data-autoenviar>
    <?php if ($filtros['q'] !== ''): ?><input type="hidden" name="q" value="<?= e($filtros['q']) ?>"><?php endif; ?>
    <input type="hidden" name="orden" value="<?= e($orden) ?>">
    <?php if ($vistaLista): ?><input type="hidden" name="vista" value="lista"><?php endif; ?>

    <section class="grupo-filtro">
        <h3 class="etiqueta-seccion text-teal">Categorías</h3>
        <?php foreach ($categorias as $c): ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="categoria[]" value="<?= (int) $c['id'] ?>" id="<?= $pf ?>cat<?= (int) $c['id'] ?>"
                       <?= in_array((int) $c['id'], $filtros['categorias'], true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="<?= $pf ?>cat<?= (int) $c['id'] ?>"><?= e($c['nombre']) ?> <span class="texto-suave">(<?= (int) $c['libros'] ?>)</span></label>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="grupo-filtro">
        <h3 class="etiqueta-seccion text-teal">Disponibilidad</h3>
        <?php foreach (['' => 'Todos', 'disponibles' => 'En stock', 'agotados' => 'Agotados'] as $valor => $texto): ?>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="disponibilidad" value="<?= e($valor) ?>" id="<?= $pf ?>disp<?= e($valor ?: 'todos') ?>"
                       <?= $filtros['disponibilidad'] === $valor ? 'checked' : '' ?>>
                <label class="form-check-label" for="<?= $pf ?>disp<?= e($valor ?: 'todos') ?>"><?= e($texto) ?></label>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="grupo-filtro">
        <h3 class="etiqueta-seccion text-teal">Rango de precio</h3>
        <div class="d-flex gap-2 align-items-center">
            <input type="number" class="form-control form-control-sm" name="precio_min" min="0" step="1" placeholder="$ Mín" value="<?= e($filtros['precio_min']) ?>" aria-label="Precio mínimo">
            <span class="texto-suave">–</span>
            <input type="number" class="form-control form-control-sm" name="precio_max" min="0" step="1" placeholder="$ Máx" value="<?= e($filtros['precio_max']) ?>" aria-label="Precio máximo">
        </div>
    </section>

    <section class="grupo-filtro">
        <h3 class="etiqueta-seccion text-teal">Autor</h3>
        <?php foreach ($autores as $i => $a): ?>
            <div class="form-check <?= $i >= $autoresVisibles ? 'collapse autores-extra-' . $pf : '' ?>">
                <input class="form-check-input" type="checkbox" name="autor[]" value="<?= (int) $a['id'] ?>" id="<?= $pf ?>aut<?= (int) $a['id'] ?>"
                       <?= in_array((int) $a['id'], $filtros['autores'], true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="<?= $pf ?>aut<?= (int) $a['id'] ?>"><?= e($a['nombre']) ?></label>
            </div>
        <?php endforeach; ?>
        <?php if (count($autores) > $autoresVisibles): ?>
            <button type="button" class="btn-enlace mt-1" data-bs-toggle="collapse" data-bs-target=".autores-extra-<?= $pf ?>">
                Ver todos (<?= count($autores) ?>)
            </button>
        <?php endif; ?>
    </section>

    <section class="grupo-filtro">
        <h3 class="etiqueta-seccion text-teal">Editorial</h3>
        <?php foreach ($editoriales as $ed): ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="editorial[]" value="<?= (int) $ed['id'] ?>" id="<?= $pf ?>edi<?= (int) $ed['id'] ?>"
                       <?= in_array((int) $ed['id'], $filtros['editoriales'], true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="<?= $pf ?>edi<?= (int) $ed['id'] ?>"><?= e($ed['nombre']) ?></label>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="grupo-filtro">
        <h3 class="etiqueta-seccion text-teal">Año de publicación</h3>
        <div class="d-flex gap-2 align-items-center">
            <input type="number" class="form-control form-control-sm" name="anio_desde" placeholder="Desde" value="<?= e($filtros['anio_desde']) ?>" aria-label="Año desde">
            <span class="texto-suave">–</span>
            <input type="number" class="form-control form-control-sm" name="anio_hasta" placeholder="Hasta" value="<?= e($filtros['anio_hasta']) ?>" aria-label="Año hasta">
        </div>
    </section>

    <button type="submit" class="btn btn-pya btn-sm w-100 mt-3">Aplicar filtros</button>
</form>
