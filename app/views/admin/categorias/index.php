<?php
/**
 * Categorías.
 * Variables: $categorias, $q, $esAdmin
 */
$tituloSeccion = 'Categorías';
$subtituloSeccion = 'Clasificación de libros';
$urlNuevo = '/admin/categorias/crear';
$textoNuevo = 'Nueva categoría';
require RUTA_VISTAS . '/partials/encabezado_catalogo.php';
?>
<form class="barra-filtros mb-4" method="get">
    <label class="campo-busqueda">
        <i class="bi bi-search"></i>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar categoría" aria-label="Buscar">
    </label>
</form>

<div class="tarjeta overflow-hidden">
    <div class="table-responsive">
        <table class="table tabla-pya">
            <thead><tr><th>Nombre</th><th>Descripción</th><th class="text-center">Libros</th><?php if ($esAdmin): ?><th class="text-center">Acciones</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($categorias as $c): ?>
                <tr>
                    <td class="fw-semibold"><?= e($c['nombre']) ?></td>
                    <td class="texto-suave"><?= e($c['descripcion'] ?? '—') ?></td>
                    <td class="text-center"><span class="insignia insignia-neutra"><?= (int) $c['libros'] ?> <?= (int) $c['libros'] === 1 ? 'libro' : 'libros' ?></span></td>
                    <?php if ($esAdmin): ?>
                        <td>
                            <?php
                            $urlEditar = '/admin/categorias/editar/' . (int) $c['id_categoria'];
                            $urlEliminar = '/admin/categorias/eliminar/' . (int) $c['id_categoria'];
                            $libros = $c['libros'];
                            $nombreRegistro = $c['nombre'];
                            require RUTA_VISTAS . '/partials/acciones_catalogo.php';
                            ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$categorias): ?><tr><td colspan="4" class="text-center texto-suave py-4">No se encontraron categorías</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
