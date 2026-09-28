<?php
/**
 * Gestión de empleados (un único rol, sin subroles).
 * Variables: $empleados, $stats, $q, $estado
 */
$datos = [
    ['etiqueta' => 'Total de empleados', 'valor' => (int) $stats['total']],
    ['etiqueta' => 'Activos', 'valor' => (int) $stats['activos'], 'color' => '#2e7d5a'],
    ['etiqueta' => 'Dados de baja', 'valor' => (int) $stats['inactivos'], 'color' => '#a8a29e'],
];
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="titulo-pagina mb-1">Gestión de Empleados</h1>
        <p class="small texto-suave mb-0">Alta, consulta y baja del personal con acceso al sistema</p>
    </div>
    <a href="<?= url('/admin/empleados/crear') ?>" class="btn btn-pya"><i class="bi bi-plus-lg me-1"></i>Nuevo empleado</a>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($datos as $dato): ?>
        <div class="col-4"><?php require RUTA_VISTAS . '/partials/dato.php'; ?></div>
    <?php endforeach; ?>
</div>

<form class="barra-filtros mb-4" method="get">
    <label class="campo-busqueda">
        <i class="bi bi-search"></i>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre, correo o DUI" aria-label="Buscar">
    </label>
    <select name="estado" class="form-select" onchange="this.form.submit()" aria-label="Estado">
        <option value="">Todos</option>
        <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activos</option>
        <option value="baja" <?= $estado === 'baja' ? 'selected' : '' ?>>Dados de baja</option>
    </select>
    <a href="<?= url('/admin/empleados') ?>" class="btn btn-light border btn-sm">Limpiar filtros</a>
</form>

<div class="tarjeta overflow-hidden">
    <div class="table-responsive">
        <table class="table tabla-pya">
            <thead><tr><th>Empleado</th><th>Contacto</th><th>Fecha de ingreso</th><th>Último acceso</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($empleados as $emp): $activo = $emp['estado'] === 'activo'; ?>
                <tr class="<?= $activo ? '' : 'fila-inactiva' ?>">
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar" style="width:36px;height:36px;background:<?= $activo ? 'var(--color-teal-light)' : '#a8a29e' ?>"><?= e(iniciales($emp['nombres'] . ' ' . $emp['apellidos'])) ?></span>
                            <div>
                                <p class="fw-semibold mb-0"><?= e($emp['nombres'] . ' ' . $emp['apellidos']) ?></p>
                                <p class="small font-monospace texto-suave mb-0"><?= e(codigoEmpleado($emp['id_empleado'])) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="small"><?= e($emp['correo']) ?><br><span class="texto-suave"><?= e($emp['telefono'] ?? '—') ?></span></td>
                    <td class="small"><?= fecha($emp['fecha_contratacion']) ?></td>
                    <td class="small <?= $emp['ultimo_acceso'] ? '' : 'texto-suave' ?>"><?= fecha($emp['ultimo_acceso']) ?></td>
                    <td>
                        <?php if ($activo): ?>
                            <span class="insignia estado-entregado"><span class="punto"></span>Activo</span>
                        <?php else: ?>
                            <span class="insignia estado-cancelado"><span class="punto"></span>Baja · <?= fecha($emp['fecha_baja']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-nowrap">
                        <a href="<?= url('/admin/empleados/' . (int) $emp['id_empleado']) ?>" class="btn-icono" title="Ver ficha"><i class="bi bi-eye"></i></a>
                        <a href="<?= url('/admin/empleados/editar/' . (int) $emp['id_empleado']) ?>" class="btn-icono" title="Editar"><i class="bi bi-pencil-square"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$empleados): ?>
                <tr><td colspan="6"><div class="estado-vacio py-5"><i class="bi bi-people"></i><p class="small mb-0">No se encontraron empleados con estos filtros</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
