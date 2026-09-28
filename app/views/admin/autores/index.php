<?php
/**
 * Gestión de autores.
 * Variables: $autores, $nacionalidades, $q, $nacionalidad, $esAdmin
 */
$tituloSeccion = 'Gestión de autores';
$subtituloSeccion = 'Consulta de autores';
$urlNuevo = '/admin/autores/crear';
$textoNuevo = 'Nuevo autor';
require RUTA_VISTAS . '/partials/encabezado_catalogo.php';
?>
<form class="barra-filtros mb-4" method="get">
    <label class="campo-busqueda">
        <i class="bi bi-search"></i>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o nacionalidad" aria-label="Buscar">
    </label>
    <select name="nacionalidad" class="form-select" onchange="this.form.submit()" aria-label="Nacionalidad">
        <option value="">Nacionalidad</option>
        <?php foreach ($nacionalidades as $n): ?>
            <option value="<?= e($n) ?>" <?= $nacionalidad === $n ? 'selected' : '' ?>><?= e($n) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($q !== '' || $nacionalidad !== ''): ?><a href="<?= url('/admin/autores') ?>" class="btn btn-light border btn-sm">Limpiar</a><?php endif; ?>
</form>

<div class="tarjeta overflow-hidden">
    <div class="table-responsive">
        <table class="table tabla-pya">
            <thead><tr><th>Autor</th><th>Nacionalidad</th><th class="text-center">Libros publicados</th><?php if ($esAdmin): ?><th class="text-center">Acciones</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($autores as $a): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar" style="width:40px;height:40px;background:var(--color-cream-dark);color:var(--color-teal)"><?= e(iniciales($a['nombre_completo'])) ?></span>
                            <div class="min-w-0">
                                <p class="fw-semibold mb-0"><?= e($a['nombre_completo']) ?></p>
                                <?php if ($a['biografia']): ?><p class="small texto-suave mb-0 text-truncate" style="max-width:28rem"><?= e($a['biografia']) ?></p><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="texto-suave"><?= e($a['nacionalidad'] ?? '—') ?></td>
                    <td class="text-center">
                        <a href="<?= url('/catalogo?autor[]=' . (int) $a['id_autor']) ?>" class="font-serif fw-bold"><?= (int) $a['libros'] ?></a>
                    </td>
                    <?php if ($esAdmin): ?>
                        <td>
                            <?php
                            $urlEditar = '/admin/autores/editar/' . (int) $a['id_autor'];
                            $urlEliminar = '/admin/autores/eliminar/' . (int) $a['id_autor'];
                            $libros = $a['libros'];
                            $nombreRegistro = $a['nombre_completo'];
                            require RUTA_VISTAS . '/partials/acciones_catalogo.php';
                            ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$autores): ?><tr><td colspan="4"><div class="estado-vacio py-5"><i class="bi bi-search"></i><p class="small mb-0">No se encontraron autores</p></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="small texto-suave mt-3"><?= count($autores) ?> autores</p>
