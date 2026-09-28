<?php
/**
 * Editoriales.
 * Variables: $editoriales, $q, $esAdmin
 */
$tituloSeccion = 'Editoriales';
$subtituloSeccion = 'Catálogo de editoriales';
$urlNuevo = '/admin/editoriales/crear';
$textoNuevo = 'Nueva editorial';
require RUTA_VISTAS . '/partials/encabezado_catalogo.php';
?>
<form class="barra-filtros mb-4" method="get">
    <label class="campo-busqueda">
        <i class="bi bi-search"></i>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o país" aria-label="Buscar">
    </label>
</form>

<div class="tarjeta overflow-hidden">
    <div class="table-responsive">
        <table class="table tabla-pya">
            <thead><tr><th>Nombre</th><th>País</th><th class="text-center">Libros</th><?php if ($esAdmin): ?><th class="text-center">Acciones</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($editoriales as $ed): ?>
                <tr>
                    <td class="fw-semibold"><?= e($ed['nombre']) ?></td>
                    <td class="texto-suave"><?= e($ed['pais'] ?? '—') ?></td>
                    <td class="text-center"><span class="insignia insignia-neutra"><?= (int) $ed['libros'] ?> <?= (int) $ed['libros'] === 1 ? 'libro' : 'libros' ?></span></td>
                    <?php if ($esAdmin): ?>
                        <td>
                            <?php
                            $urlEditar = '/admin/editoriales/editar/' . (int) $ed['id_editorial'];
                            $urlEliminar = '/admin/editoriales/eliminar/' . (int) $ed['id_editorial'];
                            $libros = $ed['libros'];
                            $nombreRegistro = $ed['nombre'];
                            require RUTA_VISTAS . '/partials/acciones_catalogo.php';
                            ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$editoriales): ?><tr><td colspan="4" class="text-center texto-suave py-4">No se encontraron editoriales</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
