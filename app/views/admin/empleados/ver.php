<?php
/**
 * Ficha del empleado: datos, métricas, actividad, pedidos y baja lógica.
 * Variables: $empleado, $metricas, $actividad, $pedidos
 */
$nombre = $empleado['nombres'] . ' ' . $empleado['apellidos'];
$activo = $empleado['estado'] === 'activo';
$motivos = ['venta' => 'Venta', 'compra' => 'Registró entrada', 'devolucion' => 'Devolución', 'inventario_inicial' => 'Inventario inicial', 'ajuste' => 'Ajuste'];
$info = [
    'DUI' => $empleado['dui'],
    'Teléfono' => $empleado['telefono'] ?? '—',
    'Correo' => $empleado['correo'],
    'Fecha de ingreso' => fecha($empleado['fecha_contratacion']),
    'Último acceso' => fechaHora($empleado['ultimo_acceso']),
];
if (!$activo) {
    $info['Fecha de baja'] = fecha($empleado['fecha_baja']);
    $info['Motivo de baja'] = $empleado['motivo_baja'] ?? '—';
}
$id = (int) $empleado['id_empleado'];
?>
<a href="<?= url('/admin/empleados') ?>" class="small text-decoration-none">← Volver a Empleados</a>

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mt-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <span class="avatar avatar-lg" style="width:64px;height:64px;background:<?= $activo ? 'var(--color-teal-light)' : '#a8a29e' ?>"><?= e(iniciales($nombre)) ?></span>
        <div>
            <h1 class="titulo-pagina mb-1"><?= e($nombre) ?></h1>
            <span class="small font-monospace texto-suave"><?= e(codigoEmpleado($id)) ?></span>
            <span class="insignia <?= $activo ? 'estado-entregado' : 'estado-cancelado' ?> ms-2"><?= $activo ? 'Activo' : 'Dado de baja' ?></span>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= url('/admin/empleados/editar/' . $id) ?>" class="btn btn-pya-contorno btn-sm"><i class="bi bi-pencil-square me-1"></i>Editar</a>
        <form action="<?= url('/admin/empleados/restablecer/' . $id) ?>" method="post" data-confirmar="¿Generar una contraseña temporal nueva? La actual dejará de funcionar.">
            <?= csrf_campo() ?>
            <button class="btn btn-pya-contorno btn-sm"><i class="bi bi-lock me-1"></i>Restablecer contraseña</button>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([['Pedidos preparados este mes', (int) $metricas['preparados_mes']], ['Entradas de inventario registradas', (int) $metricas['entradas_inventario']], ['Días desde el ingreso', diasDesde($empleado['fecha_contratacion'])]] as [$etq, $val]): ?>
        <div class="col-md-4">
            <div class="tarjeta-dato"><p class="valor mt-0 mb-1"><?= $val ?></p><p class="small texto-suave mb-0"><?= $etq ?></p></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <section class="tarjeta p-4 h-100">
            <h2 class="etiqueta-seccion mb-3">Información</h2>
            <dl class="mb-0 small">
                <?php foreach ($info as $etq => $val): ?>
                    <div class="d-flex justify-content-between gap-3 py-1">
                        <dt class="fw-normal texto-suave"><?= e($etq) ?></dt>
                        <dd class="fw-medium text-end mb-0 text-break"><?= e($val) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </section>
    </div>

    <div class="col-lg-7">
        <section class="tarjeta overflow-hidden h-100">
            <ul class="nav nav-tabs px-3 pt-2" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabActividad" type="button">Actividad reciente</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPedidos" type="button">Pedidos gestionados</button></li>
            </ul>
            <div class="tab-content p-4">
                <div class="tab-pane fade show active" id="tabActividad">
                    <?php if (!$actividad): ?>
                        <p class="small texto-suave mb-0">Sin actividad registrada.</p>
                    <?php else: ?>
                        <ol class="linea-tiempo">
                            <?php foreach ($actividad as $a): ?>
                                <li>
                                    <span class="hito hecho" style="<?= $a['tipo'] === 'inventario' ? 'background:var(--color-orange);border-color:var(--color-orange)' : '' ?>">
                                        <i class="bi <?= $a['tipo'] === 'inventario' ? 'bi-box-arrow-in-down' : 'bi-truck' ?>"></i>
                                    </span>
                                    <div class="pt-1">
                                        <p class="small mb-0">
                                            <?php if ($a['tipo'] === 'pedido'): ?>
                                                Marcó el pedido <strong><?= e(codigoPedido($a['id_pedido'])) ?></strong> como «<?= e(estadoPedido($a['detalle'])['texto']) ?>»
                                            <?php else: ?>
                                                <?= e($motivos[$a['detalle']] ?? 'Movimiento') ?>: <?= (int) $a['cantidad'] ?> unidades de «<?= e($a['titulo']) ?>»
                                            <?php endif; ?>
                                        </p>
                                        <p class="small texto-suave mb-0"><?= fechaHora($a['fecha']) ?></p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
                <div class="tab-pane fade" id="tabPedidos">
                    <?php if (!$pedidos): ?>
                        <p class="small texto-suave mb-0">No ha gestionado pedidos.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table tabla-pya mb-0">
                                <thead><tr><th>N° pedido</th><th>Cliente</th><th>Fecha</th><th>Estado</th></tr></thead>
                                <tbody>
                                <?php foreach ($pedidos as $p): ?>
                                    <tr>
                                        <td><a href="<?= url('/admin/pedidos/' . (int) $p['id_pedido']) ?>" class="font-monospace text-decoration-none"><?= e(codigoPedido($p['id_pedido'])) ?></a></td>
                                        <td><?= e($p['cliente']) ?></td>
                                        <td class="texto-suave"><?= fecha($p['fecha_pedido']) ?></td>
                                        <td><?php $estado = $p['estado']; require RUTA_VISTAS . '/partials/insignia_estado.php'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<?php if ($activo): ?>
    <section class="zona-riesgo mb-5">
        <h4>Zona de riesgo</h4>
        <p class="small mb-3" style="color:#991b1b">El empleado dejará de tener acceso al sistema, pero su historial de actividad se conserva.</p>
        <button class="btn btn-pya-peligro btn-sm" data-bs-toggle="modal" data-bs-target="#modalBaja">Dar de baja</button>
    </section>

    <div class="modal fade" id="modalBaja" tabindex="-1" aria-labelledby="modalBajaTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" action="<?= url('/admin/empleados/baja/' . $id) ?>" method="post">
                <?= csrf_campo() ?>
                <div class="modal-body p-4">
                    <div class="d-flex gap-3 mb-3">
                        <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;background:rgba(232,133,74,.14);color:var(--color-orange)"><i class="bi bi-exclamation-triangle fs-5"></i></span>
                        <div>
                            <h2 class="font-serif h5 mb-1" id="modalBajaTitulo">¿Dar de baja a <?= e($empleado['nombres']) ?>?</h2>
                            <p class="small texto-suave mb-0">Perderá el acceso de inmediato. Su historial y los pedidos que gestionó se conservan.</p>
                        </div>
                    </div>
                    <?php if ((int) $empleado['pedidos_pendientes'] > 0): ?>
                        <div class="alert alert-warning small py-2">
                            Tiene <?= (int) $empleado['pedidos_pendientes'] ?> pedido(s) en preparación. Quedarán sin asignar para reasignarlos.
                        </div>
                    <?php endif; ?>
                    <label for="motivoBaja" class="form-label">Motivo de la baja <span class="texto-suave fw-normal">(opcional)</span></label>
                    <textarea class="form-control" id="motivoBaja" name="motivo" rows="3" maxlength="255" placeholder="Ej. Renuncia voluntaria, fin de contrato…"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-pya-peligro">Dar de baja</button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <section class="tarjeta p-4 mb-5 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <p class="small mb-0">Este empleado está dado de baja desde el <?= fecha($empleado['fecha_baja']) ?>.</p>
        <form action="<?= url('/admin/empleados/reactivar/' . $id) ?>" method="post" data-confirmar="¿Reactivar a <?= e($nombre) ?>? Recuperará el acceso al sistema.">
            <?= csrf_campo() ?>
            <button class="btn btn-pya btn-sm">Reactivar empleado</button>
        </form>
    </section>
<?php endif; ?>
