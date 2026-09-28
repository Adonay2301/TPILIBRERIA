<?php
/**
 * Crear / editar libro (solo administrador).
 * Variables: $libro (null al crear), $autoresLibro, $autores, $categorias, $editoriales
 */
$editando = $libro !== null;
$v = fn (string $campo, $defecto = '') => old($campo, $editando ? (string) ($libro[$campo] ?? '') : (string) $defecto);
$seleccionados = old('autores') !== '' ? array_map('intval', explode(',', old('autores'))) : $autoresLibro;
$accion = $editando ? url('/admin/libros/actualizar/' . (int) $libro['id_libro']) : url('/admin/libros/guardar');
?>
<a href="<?= url('/admin/libros') ?>" class="small text-decoration-none">← Volver al inventario</a>
<h1 class="titulo-pagina mt-2 mb-4"><?= $editando ? 'Editar libro' : 'Nuevo libro' ?></h1>

<form action="<?= $accion ?>" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_campo() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <section class="tarjeta p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="titulo" class="form-label">Título <span class="requerido">*</span></label>
                        <input type="text" class="form-control" id="titulo" name="titulo" maxlength="200" value="<?= e($v('titulo')) ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="autores" class="form-label">Autores <span class="requerido">*</span></label>
                        <select class="form-select" id="autores" name="autores[]" multiple size="5" required>
                            <?php foreach ($autores as $a): ?>
                                <option value="<?= (int) $a['id'] ?>" <?= in_array((int) $a['id'], $seleccionados, true) ? 'selected' : '' ?>><?= e($a['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Mantén presionada Ctrl para elegir varios. ¿No aparece? <a href="<?= url('/admin/autores/crear') ?>">Agrega el autor</a>.</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="id_categoria" class="form-label">Categoría <span class="requerido">*</span></label>
                        <select class="form-select" id="id_categoria" name="id_categoria" required>
                            <option value="">Seleccionar…</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= $v('id_categoria') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label for="id_editorial" class="form-label">Editorial <span class="requerido">*</span></label>
                        <select class="form-select" id="id_editorial" name="id_editorial" required>
                            <option value="">Seleccionar…</option>
                            <?php foreach ($editoriales as $ed): ?>
                                <option value="<?= (int) $ed['id'] ?>" <?= $v('id_editorial') === (string) $ed['id'] ? 'selected' : '' ?>><?= e($ed['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label for="isbn" class="form-label">ISBN-13 <span class="requerido">*</span></label>
                        <input type="text" class="form-control font-monospace" id="isbn" name="isbn" maxlength="17" placeholder="978XXXXXXXXXX" value="<?= e($v('isbn')) ?>" required>
                    </div>
                    <div class="col-sm-3">
                        <label for="anio_publicacion" class="form-label">Año</label>
                        <input type="number" class="form-control" id="anio_publicacion" name="anio_publicacion" value="<?= e($v('anio_publicacion')) ?>">
                    </div>
                    <div class="col-sm-3">
                        <label for="numero_paginas" class="form-label">Páginas</label>
                        <input type="number" class="form-control" id="numero_paginas" name="numero_paginas" min="1" value="<?= e($v('numero_paginas')) ?>">
                    </div>
                    <div class="col-12">
                        <label for="sinopsis" class="form-label">Sinopsis</label>
                        <textarea class="form-control" id="sinopsis" name="sinopsis" rows="4"><?= e($v('sinopsis')) ?></textarea>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-4">
            <section class="tarjeta p-4 mb-4">
                <label class="form-label">Portada</label>
                <div class="d-flex align-items-center gap-3">
                    <?php if ($editando): ?>
                        <span class="portada-mini" style="width:60px;height:82px"><?php require RUTA_VISTAS . '/partials/portada.php'; ?></span>
                    <?php endif; ?>
                    <input type="file" class="form-control" name="portada" accept="image/jpeg,image/png,image/webp" aria-label="Subir portada">
                </div>
                <div class="form-text">JPG, PNG o WEBP de hasta 4 MB.</div>
            </section>

            <section class="tarjeta p-4 mb-4">
                <div class="row g-3">
                    <div class="col-6">
                        <label for="precio" class="form-label">Precio (USD) <span class="requerido">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="precio" name="precio" min="0.01" step="0.01" value="<?= e($v('precio')) ?>" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <label for="stock_minimo" class="form-label">Stock mínimo</label>
                        <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" min="0" value="<?= e($v('stock_minimo', 5)) ?>">
                    </div>
                    <?php if ($editando): ?>
                        <div class="col-12 small texto-suave">
                            Existencias actuales: <strong><?= (int) $libro['stock_actual'] ?></strong>. Se modifican con «Registrar entrada» o con las ventas.
                        </div>
                    <?php else: ?>
                        <div class="col-12">
                            <label for="stock_inicial" class="form-label">Existencias iniciales</label>
                            <input type="number" class="form-control" id="stock_inicial" name="stock_inicial" min="0" value="<?= e(old('stock_inicial', '0')) ?>">
                        </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <label for="idioma" class="form-label">Idioma</label>
                        <input type="text" class="form-control" id="idioma" name="idioma" maxlength="30" value="<?= e($v('idioma', 'Español')) ?>">
                    </div>
                    <div class="col-12">
                        <input type="hidden" name="es_novedad" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="es_novedad" name="es_novedad" value="1" <?= $v('es_novedad', '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="es_novedad">Marcar como novedad</label>
                        </div>
                        <input type="hidden" name="activo" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= $v('activo', '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="activo">Visible en el catálogo</label>
                        </div>
                    </div>
                </div>
            </section>

            <div class="d-flex gap-2">
                <a href="<?= url('/admin/libros') ?>" class="btn btn-light border flex-fill">Cancelar</a>
                <button type="submit" class="btn btn-pya flex-fill"><?= $editando ? 'Guardar cambios' : 'Agregar libro' ?></button>
            </div>
        </div>
    </div>
</form>
