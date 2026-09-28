<?php
/**
 * Detalle de un pedido en el panel, con historial y acciones según el rol.
 * Variables: $pedido, $detalles, $historial, $estados (permitidos), $puedeTomar, $empleados, $motivos, $esAdmin
 */
$rolesTexto = ['cliente' => 'Cliente', 'empleado' => 'Empleado', 'administrador' => 'Administrador'];
$accion = url('/admin/pedidos/' . (int) $pedido['id_pedido']);
?>
<a href="<?= url('/admin/pedidos') ?>" class="small text-decoration-none">← Volver a pedidos</a>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-2 mb-4">
    <div>
        <p class="etiqueta-seccion mb-1">Detalle de pedido</p>
        <h1 class="titulo-pagina mb-0 d-flex align-items-center gap-3">
            <?= e(codigoPedido($pedido['id_pedido'])) ?>
            <?php $estado = $pedido['estado']; require RUTA_VISTAS . '/partials/insignia_estado.php'; ?>
        </h1>
    </div>
    <button class="btn btn-pya-contorno btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <section class="tarjeta overflow-hidden mb-4">
            <div class="tarjeta-encabezado"><h2>Libros · <?= array_sum(array_column($detalles, 'cantidad')) ?> artículos</h2></div>
            <?php foreach ($detalles as $libro): ?>
                <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                    <span class="portada-mini" style="width:40px;height:55px"><?php require RUTA_VISTAS . '/partials/portada.php'; ?></span>
                    <div class="flex-grow-1 min-w-0">
                        <p class="small fw-semibold mb-0 text-truncate"><?= e($libro['titulo']) ?></p>
                        <p class="small texto-suave mb-0"><?= e($libro['autores'] ?? '') ?></p>
                        <p class="small mb-0" style="color:var(--color-teal-light)"><?= moneda($libro['precio_unitario']) ?> × <?= (int) $libro['cantidad'] ?></p>
                    </div>
                    <span class="small fw-semibold"><?= moneda($libro['subtotal']) ?></span>
                </div>
            <?php endforeach; ?>
            <div class="px-4 py-3 small" style="background:var(--color-cream)">
                <div class="d-flex justify-content-between mb-1"><span class="texto-suave">Subtotal</span><span><?= moneda($pedido['subtotal']) ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span class="texto-suave">Envío</span>
                    <span class="<?= (float) $pedido['costo_envio'] == 0 ? 'texto-verde fw-medium' : '' ?>"><?= (float) $pedido['costo_envio'] > 0 ? moneda($pedido['costo_envio']) : 'Gratis' ?></span></div>
                <div class="d-flex justify-content-between align-items-baseline border-top pt-2">
                    <span class="fw-semibold">Total</span><span class="font-serif fw-bold fs-5"><?= moneda($pedido['total']) ?></span>
                </div>
                <?php $detallePaypal = true; require RUTA_VISTAS . '/partials/pago_resumen.php'; ?>
            </div>
        </section>

        <section class="tarjeta p-4">
            <h2 class="etiqueta-seccion mb-4">Historial de cambios</h2>
            <ol class="linea-tiempo">
                <?php foreach ($historial as $h): ?>
                    <li>
                        <span class="hito <?= $h['estado_nuevo'] === 'cancelado' ? 'cancelado' : 'hecho' ?>"><i class="bi <?= $h['estado_nuevo'] === 'cancelado' ? 'bi-x-lg' : 'bi-check-lg' ?>"></i></span>
                        <div class="pt-1">
                            <p class="small fw-semibold mb-0"><?= e(estadoPedido($h['estado_nuevo'])['texto']) ?></p>
                            <p class="small texto-suave mb-0"><?= fechaHora($h['fecha_cambio']) ?> · <?= e($h['usuario'] ?: 'Sistema') ?><?= $h['rol'] ? ' (' . e($rolesTexto[$h['rol']]) . ')' : '' ?></p>
                            <?php if ($h['comentario']): ?><p class="small mb-0 fst-italic"><?= e($h['comentario']) ?></p><?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    </div>

    <div class="col-lg-5">
        <?php if ($pedido['empleado']): ?>
            <div class="tarjeta px-4 py-3 mb-3 small d-flex align-items-center gap-2" style="background:rgba(26,58,74,.04)">
                <i class="bi bi-person" style="color:var(--color-teal-light)"></i>
                Asignado a <strong><?= e($pedido['empleado']) ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($puedeTomar): ?>
            <form action="<?= $accion ?>/asignar" method="post" class="mb-3">
                <?= csrf_campo() ?>
                <button class="btn btn-pya w-100 py-2">Asignarme este pedido</button>
            </form>
        <?php endif; ?>

        <?php if ($estados && !$puedeTomar): ?>
            <!-- Cambio de estado: solo los estados permitidos para este rol -->
            <section class="tarjeta p-4 mb-3">
                <h2 class="tarjeta-titulo mb-3">Cambiar estado</h2>
                <form action="<?= $accion ?>/estado" method="post" data-confirmar="¿Confirmas el cambio de estado? El cliente verá la actualización.">
                    <?= csrf_campo() ?>
                    <div class="mb-3">
                        <label for="nuevoEstado" class="form-label">Nuevo estado</label>
                        <select class="form-select" id="nuevoEstado" name="estado" required
                                onchange="document.getElementById('campoMotivo').classList.toggle('d-none', this.value !== 'cancelado'); document.getElementById('campoEmpleado')?.classList.toggle('d-none', this.value !== 'en_preparacion')">
                            <?php foreach ($estados as $e): ?>
                                <option value="<?= e($e) ?>"><?= e(estadoPedido($e)['texto']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($esAdmin && in_array('en_preparacion', $estados, true)): ?>
                        <div class="mb-3 <?= $estados[0] === 'en_preparacion' ? '' : 'd-none' ?>" id="campoEmpleado">
                            <label for="empleadoPreparacion" class="form-label">Empleado responsable</label>
                            <select class="form-select" id="empleadoPreparacion" name="id_empleado">
                                <option value="">Sin asignar</option>
                                <?php foreach ($empleados as $emp): ?>
                                    <option value="<?= (int) $emp['id_empleado'] ?>"><?= e($emp['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3 <?= $estados[0] === 'cancelado' ? '' : 'd-none' ?>" id="campoMotivo">
                        <label for="motivo" class="form-label">Motivo de cancelación <span class="requerido">*</span></label>
                        <select class="form-select" id="motivo" name="motivo">
                            <option value="">Seleccionar motivo…</option>
                            <?php foreach ($motivos as $m): ?><option value="<?= e($m) ?>"><?= e($m) ?></option><?php endforeach; ?>
                        </select>
                        <?php if ($pedido['pago_metodo'] === 'paypal' && $pedido['pago_estado'] === 'completado'): ?>
                            <p class="small text-warning-emphasis mt-2 mb-0"><i class="bi bi-arrow-counterclockwise me-1"></i>Este pedido se pagó con PayPal: al cancelarlo se reembolsan <?= moneda($pedido['total']) ?> al cliente.</p>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="comentario" class="form-label">Nota interna</label>
                        <textarea class="form-control" id="comentario" name="comentario" rows="2" maxlength="200" placeholder="Observación para el equipo (opcional)"></textarea>
                    </div>
                    <button class="btn btn-pya w-100">Confirmar cambio</button>
                </form>
            </section>
        <?php endif; ?>

        <?php if ($esAdmin && !in_array($pedido['estado'], ['entregado', 'cancelado'], true)): ?>
            <section class="tarjeta p-4 mb-3">
                <h2 class="tarjeta-titulo mb-3">Responsable</h2>
                <form action="<?= $accion ?>/asignar" method="post" class="d-flex gap-2">
                    <?= csrf_campo() ?>
                    <select class="form-select" name="id_empleado" aria-label="Empleado">
                        <option value="">Sin asignar</option>
                        <?php foreach ($empleados as $emp): ?>
                            <option value="<?= (int) $emp['id_empleado'] ?>" <?= (int) $pedido['id_empleado'] === (int) $emp['id_empleado'] ? 'selected' : '' ?>><?= e($emp['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-pya-suave">Guardar</button>
                </form>
            </section>
        <?php endif; ?>

        <section class="tarjeta p-4 mb-3">
            <h2 class="etiqueta-seccion mb-3">Cliente</h2>
            <p class="fw-semibold small mb-1"><?= e($pedido['cliente']) ?></p>
            <p class="small texto-suave mb-0"><?= e($pedido['correo']) ?></p>
            <p class="small texto-suave mb-0"><?= e($pedido['telefono_contacto']) ?></p>
        </section>

        <section class="tarjeta p-4">
            <h2 class="etiqueta-seccion mb-3">Dirección de entrega</h2>
            <p class="small mb-1"><?= e($pedido['direccion_entrega']) ?></p>
            <p class="small texto-suave mb-1"><?= e($pedido['distrito']) ?>, <?= e($pedido['municipio']) ?>, <?= e($pedido['departamento']) ?></p>
            <?php if ($pedido['referencia_entrega']): ?><p class="small texto-suave fst-italic mb-1">Ref: <?= e($pedido['referencia_entrega']) ?></p><?php endif; ?>
            <?php if ($pedido['notas']): ?><p class="small mb-0"><i class="bi bi-chat-left-text me-1"></i><?= e($pedido['notas']) ?></p><?php endif; ?>
        </section>
    </div>
</div>
