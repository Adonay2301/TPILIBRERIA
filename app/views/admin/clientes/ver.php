<?php
/**
 * Ficha del cliente.
 * Variables: $cliente, $resumen, $direcciones, $pedidos, $librosTop
 */
$nombre = $cliente['nombres'] . ' ' . $cliente['apellidos'];
?>
<a href="<?= url('/admin/clientes') ?>" class="small text-decoration-none">← Volver a clientes</a>

<div class="tarjeta overflow-hidden mt-3 mb-4">
    <div class="d-flex flex-wrap align-items-center gap-3 p-4" style="background:var(--color-teal)">
        <span class="avatar avatar-lg"><?= e(iniciales($nombre)) ?></span>
        <div class="flex-grow-1 min-w-0">
            <h1 class="text-white h4 mb-1 font-serif"><?= e($nombre) ?></h1>
            <p class="small mb-0" style="color:rgba(255,255,255,.6)"><?= e($cliente['correo']) ?> · Registrado el <?= fecha($cliente['fecha_registro']) ?></p>
        </div>
        <?php if (!$cliente['activo']): ?><span class="insignia estado-cancelado">Cuenta desactivada</span><?php endif; ?>
    </div>
    <div class="row g-0 text-center text-sm-start">
        <?php foreach ([['Pedidos realizados', (int) $resumen['pedidos']], ['Total gastado', moneda($resumen['total_gastado'])], ['Ticket promedio', moneda($resumen['ticket_promedio'])]] as $i => [$etq, $val]): ?>
            <div class="col-4 p-3 p-md-4 <?= $i < 2 ? 'border-end' : '' ?>">
                <p class="etiqueta-seccion mb-1"><?= $etq ?></p>
                <p class="font-serif fw-bold fs-4 mb-0"><?= e($val) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <section class="tarjeta p-4 mb-4">
            <h2 class="etiqueta-seccion mb-3">Datos de contacto</h2>
            <p class="small mb-2"><i class="bi bi-telephone me-2" style="color:var(--color-teal-light)"></i><?= e($cliente['telefono'] ?? 'Sin teléfono') ?></p>
            <p class="small mb-2"><i class="bi bi-envelope me-2" style="color:var(--color-teal-light)"></i><?= e($cliente['correo']) ?></p>
            <p class="small mb-0"><i class="bi bi-clock-history me-2" style="color:var(--color-teal-light)"></i>Último acceso: <?= fechaHora($cliente['ultimo_acceso']) ?></p>
        </section>

        <section class="tarjeta p-4 mb-4">
            <h2 class="etiqueta-seccion mb-3">Direcciones</h2>
            <?php foreach ($direcciones as $d): ?>
                <div class="small mb-3">
                    <strong><?= e($d['alias']) ?></strong><?= $d['es_principal'] ? ' <span class="insignia insignia-neutra">Principal</span>' : '' ?><br>
                    <?= e($d['direccion']) ?><br>
                    <span class="texto-suave"><?= e($d['distrito']) ?>, <?= e($d['municipio']) ?>, <?= e($d['departamento']) ?></span>
                    <?php if ($d['referencia']): ?><br><span class="texto-suave fst-italic">Ref: <?= e($d['referencia']) ?></span><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (!$direcciones): ?><p class="small texto-suave mb-0">Sin direcciones registradas.</p><?php endif; ?>
        </section>

        <?php if ($librosTop): ?>
            <section class="tarjeta overflow-hidden mb-4">
                <div class="tarjeta-encabezado"><h2 class="etiqueta-seccion">Libros más comprados</h2></div>
                <?php foreach ($librosTop as $libro): ?>
                    <div class="d-flex align-items-center gap-3 px-4 py-2 border-bottom">
                        <span class="portada-mini" style="width:30px;height:42px"><?php require RUTA_VISTAS . '/partials/portada.php'; ?></span>
                        <p class="small fw-medium mb-0 flex-grow-1"><?= e($libro['titulo']) ?></p>
                        <span class="insignia insignia-neutra"><?= (int) $libro['veces'] ?>× comprado</span>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-2 justify-content-between">
            <a href="mailto:<?= e($cliente['correo']) ?>" class="btn btn-pya-contorno btn-sm"><i class="bi bi-envelope me-1"></i>Enviar correo</a>
            <form action="<?= url('/admin/clientes/' . (int) $cliente['id_cliente'] . '/estado') ?>" method="post"
                  data-confirmar="<?= $cliente['activo'] ? '¿Desactivar la cuenta? El cliente no podrá iniciar sesión ni comprar hasta que se reactive.' : '¿Reactivar la cuenta del cliente?' ?>">
                <?= csrf_campo() ?>
                <input type="hidden" name="activo" value="<?= $cliente['activo'] ? '0' : '1' ?>">
                <button class="btn btn-sm <?= $cliente['activo'] ? 'btn-outline-danger' : 'btn-pya' ?>"><?= $cliente['activo'] ? 'Desactivar cuenta' : 'Reactivar cuenta' ?></button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <section class="tarjeta overflow-hidden">
            <div class="tarjeta-encabezado" style="background:var(--color-cream)">
                <h2 class="etiqueta-seccion">Historial de pedidos</h2>
                <span class="small fw-semibold" style="color:var(--color-teal-light)"><?= count($pedidos) ?> pedidos</span>
            </div>
            <?php if (!$pedidos): ?>
                <div class="estado-vacio py-5"><i class="bi bi-bag"></i><p class="small texto-suave mb-0">Este cliente aún no ha realizado ninguna compra</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table tabla-pya">
                        <thead><tr><th>N° pedido</th><th>Fecha</th><th class="text-center">Arts.</th><th class="text-end">Total</th><th class="text-center">Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($pedidos as $p): ?>
                            <tr>
                                <td><a href="<?= url('/admin/pedidos/' . (int) $p['id_pedido']) ?>" class="font-monospace fw-semibold text-decoration-none"><?= e(codigoPedido($p['id_pedido'])) ?></a></td>
                                <td class="texto-suave"><?= fecha($p['fecha_pedido']) ?></td>
                                <td class="text-center"><?= (int) $p['articulos'] ?></td>
                                <td class="text-end fw-semibold"><?= moneda($p['total']) ?></td>
                                <td class="text-center"><?php $estado = $p['estado']; require RUTA_VISTAS . '/partials/insignia_estado.php'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
