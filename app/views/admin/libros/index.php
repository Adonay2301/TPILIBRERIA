<?php
/**
 * Control de inventario. El empleado lo ve en solo lectura.
 * Variables: $libros, $filtros, $visibilidad, $paginacion, $stats, $categorias, $editoriales, $esAdmin
 */
$datos = [
    ['etiqueta' => 'Títulos en catálogo', 'valor' => (int) $stats['titulos'], 'color' => '#1a3a4a'],
    ['etiqueta' => 'Unidades totales', 'valor' => number_format((int) $stats['unidades']), 'color' => '#3b82f6'],
    ['etiqueta' => 'Existencias bajas', 'valor' => (int) $stats['bajas'], 'color' => '#ca8a04'],
    ['etiqueta' => 'Agotados', 'valor' => (int) $stats['agotados'], 'color' => '#dc2626'],
];
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="titulo-pagina mb-1">Control de Inventario</h1>
        <?php if (!$esAdmin): ?>
            <p class="small texto-suave mb-0">Consulta de existencias · Solo lectura. Contacta al administrador para modificar el inventario.</p>
        <?php endif; ?>
    </div>
    <?php if ($esAdmin): ?>
        <a href="<?= url('/admin/libros/crear') ?>" class="btn btn-pya"><i class="bi bi-plus-lg me-1"></i>Nuevo libro</a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($datos as $dato): ?>
        <div class="col-6 col-xl-3"><?php require RUTA_VISTAS . '/partials/dato.php'; ?></div>
    <?php endforeach; ?>
</div>

<?php if ((int) $stats['agotados'] > 0 && $filtros['disponibilidad'] !== 'agotados'): ?>
    <div class="d-flex align-items-center gap-3 rounded-3 px-4 py-3 mb-4" style="background:#fff5f5;border:1px solid #fecaca">
        <i class="bi bi-exclamation-triangle text-danger"></i>
        <p class="small mb-0 flex-grow-1" style="color:#7f1d1d">
            <strong><?= (int) $stats['agotados'] ?> <?= (int) $stats['agotados'] === 1 ? 'título está agotado' : 'títulos están agotados' ?></strong> y siguen visibles en el catálogo.
        </p>
        <a href="<?= url('/admin/libros?disponibilidad=agotados') ?>" class="small fw-semibold text-danger">Ver títulos</a>
    </div>
<?php endif; ?>

<form class="barra-filtros mb-4" method="get">
    <label class="campo-busqueda">
        <i class="bi bi-search"></i>
        <input type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Buscar por título, autor o ISBN" aria-label="Buscar">
    </label>
    <select name="categoria" class="form-select" onchange="this.form.submit()" aria-label="Categoría">
        <option value="">Categoría</option>
        <?php foreach ($categorias as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $filtros['categorias'], true) ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="editorial" class="form-select" onchange="this.form.submit()" aria-label="Editorial">
        <option value="">Editorial</option>
        <?php foreach ($editoriales as $ed): ?>
            <option value="<?= (int) $ed['id'] ?>" <?= in_array((int) $ed['id'], $filtros['editoriales'], true) ? 'selected' : '' ?>><?= e($ed['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="disponibilidad" class="form-select" onchange="this.form.submit()" aria-label="Disponibilidad">
        <option value="">Disponibilidad</option>
        <option value="disponibles" <?= $filtros['disponibilidad'] === 'disponibles' ? 'selected' : '' ?>>Disponible</option>
        <option value="bajas" <?= $filtros['disponibilidad'] === 'bajas' ? 'selected' : '' ?>>Existencias bajas</option>
        <option value="agotados" <?= $filtros['disponibilidad'] === 'agotados' ? 'selected' : '' ?>>Agotado</option>
    </select>
    <?php if ($esAdmin): ?>
        <select name="visibilidad" class="form-select" onchange="this.form.submit()" aria-label="Visibilidad">
            <option value="">Solo visibles</option>
            <option value="todos" <?= $visibilidad === 'todos' ? 'selected' : '' ?>>Incluir ocultos</option>
        </select>
    <?php endif; ?>
    <a href="<?= url('/admin/libros') ?>" class="btn btn-light border btn-sm">Limpiar</a>
</form>

<div class="tarjeta overflow-hidden">
    <div class="table-responsive">
        <table class="table tabla-pya">
            <thead>
                <tr><th>Libro</th><th>Categoría</th><th>Editorial</th><th class="text-end">Precio</th><th class="text-center">Existencias</th><th class="text-center">Estado</th>
                    <?php if ($esAdmin): ?><th class="text-center">Acciones</th><?php endif; ?></tr>
            </thead>
            <tbody>
            <?php foreach ($libros as $libro): $st = estadoStock((int) $libro['stock_actual'], (int) $libro['stock_minimo']); ?>
                <tr class="<?= $libro['activo'] ? '' : 'fila-inactiva' ?>">
                    <td style="min-width:16rem">
                        <div class="d-flex align-items-center gap-3">
                            <span class="portada-mini"><?php require RUTA_VISTAS . '/partials/portada.php'; ?></span>
                            <div class="min-w-0">
                                <p class="fw-semibold mb-0 text-truncate"><?= e($libro['titulo']) ?><?= $libro['activo'] ? '' : ' <span class="insignia estado-cancelado ms-1">Oculto</span>' ?></p>
                                <p class="small texto-suave mb-0 text-truncate"><?= e($libro['autores'] ?? '') ?></p>
                                <p class="small font-monospace texto-suave mb-0" style="opacity:.7"><?= e($libro['isbn']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="texto-suave"><?= e($libro['categoria']) ?></td>
                    <td class="texto-suave"><?= e($libro['editorial']) ?></td>
                    <td class="text-end fw-semibold"><?= moneda($libro['precio']) ?></td>
                    <td class="text-center font-serif fw-bold"><?= (int) $libro['stock_actual'] ?> <span class="small texto-suave fw-normal">/ mín. <?= (int) $libro['stock_minimo'] ?></span></td>
                    <td class="text-center"><span class="insignia <?= $st['clase'] ?>"><span class="punto"></span><?= e($st['texto']) ?></span></td>
                    <?php if ($esAdmin): ?>
                        <td class="text-center text-nowrap">
                            <a href="<?= url('/admin/libros/editar/' . (int) $libro['id_libro']) ?>" class="btn-icono" title="Editar"><i class="bi bi-pencil-square"></i></a>
                            <button type="button" class="btn-icono" title="Registrar entrada" data-bs-toggle="modal" data-bs-target="#modalEntrada"
                                    data-modal-datos='<?= e(json_encode(['accion' => url('/admin/libros/entrada/' . (int) $libro['id_libro']), 'titulo' => $libro['titulo'], 'stock' => 'Existencias actuales: ' . (int) $libro['stock_actual'] . ' uds.'], JSON_UNESCAPED_UNICODE)) ?>'>
                                <i class="bi bi-box-arrow-in-down"></i>
                            </button>
                            <form action="<?= url('/admin/libros/ocultar/' . (int) $libro['id_libro']) ?>" method="post" class="d-inline"
                                  data-confirmar="<?= $libro['activo'] ? '¿Ocultar este libro del catálogo? Su historial de ventas se conserva.' : '¿Volver a mostrar este libro en el catálogo?' ?>">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="activo" value="<?= $libro['activo'] ? '0' : '1' ?>">
                                <button class="btn-icono <?= $libro['activo'] ? 'peligro' : '' ?>" title="<?= $libro['activo'] ? 'Ocultar del catálogo' : 'Mostrar en el catálogo' ?>">
                                    <i class="bi <?= $libro['activo'] ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                </button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$libros): ?>
                <tr><td colspan="7"><div class="estado-vacio py-5"><i class="bi bi-search"></i><p class="small mb-0">No se encontraron libros</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $etiqueta = 'libros'; require RUTA_VISTAS . '/partials/paginacion.php'; ?>

<?php if ($esAdmin): ?>
    <!-- Registrar entrada de inventario -->
    <div class="modal fade" id="modalEntrada" tabindex="-1" aria-labelledby="modalEntradaTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="post" data-campo="accion">
                <?= csrf_campo() ?>
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modalEntradaTitulo">Registrar entrada</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="rounded-3 p-3 mb-3" style="background:var(--color-cream)">
                        <p class="fw-semibold small mb-0" data-campo="titulo"></p>
                        <p class="small texto-suave mb-0" data-campo="stock"></p>
                    </div>
                    <div class="mb-3">
                        <label for="cantidadEntrada" class="form-label">Unidades a ingresar <span class="requerido">*</span></label>
                        <input type="number" class="form-control" id="cantidadEntrada" name="cantidad" min="1" max="10000" value="1" required>
                    </div>
                    <div>
                        <label for="observacionEntrada" class="form-label">Referencia o nota</label>
                        <input type="text" class="form-control" id="observacionEntrada" name="observacion" maxlength="255" placeholder="N° de factura, proveedor, etc.">
                    </div>
                </div>
                <div class="modal-footer" style="background:var(--color-cream)">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-pya">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
