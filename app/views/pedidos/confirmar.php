<?php
/**
 * Confirmación de compra: dirección de entrega, teléfono y resumen.
 * Los montos definitivos los calculan los triggers al crear el pedido.
 * Contra entrega envía el formulario; PayPal lo envía por fetch desde js/paypal.js.
 * Variables: $items, $resumen, $direcciones, $departamentos, $cliente, $paypal, $paypalSimulado
 */
$seleccionada = old('id_direccion', $direcciones ? (string) $direcciones[0]['id_direccion'] : 'nueva');
?>
<div class="container-xl px-3 px-md-4 py-4 py-md-5">
    <a href="<?= url('/carrito') ?>" class="small text-decoration-none">← Volver al carrito</a>
    <h1 class="titulo-pagina mt-2 mb-4">Confirmar pedido</h1>

    <form action="<?= url('/pedido/crear') ?>" method="post" id="formCompra" novalidate>
        <?= csrf_campo() ?>
        <div class="row g-4">
            <div class="col-lg-7">
                <section class="tarjeta p-4 mb-4">
                    <h2 class="tarjeta-titulo mb-3">Dirección de entrega</h2>

                    <?php foreach ($direcciones as $dir): ?>
                        <label class="opcion-direccion">
                            <input class="form-check-input" type="radio" name="id_direccion" value="<?= (int) $dir['id_direccion'] ?>"
                                   <?= $seleccionada === (string) $dir['id_direccion'] ? 'checked' : '' ?> data-alternar-nueva>
                            <span>
                                <strong class="small"><?= e($dir['alias']) ?></strong><?= $dir['es_principal'] ? ' <span class="insignia insignia-neutra ms-1">Principal</span>' : '' ?><br>
                                <span class="small"><?= e($dir['direccion']) ?></span><br>
                                <span class="small texto-suave"><?= e($dir['distrito']) ?>, <?= e($dir['municipio']) ?>, <?= e($dir['departamento']) ?></span>
                                <?php if ($dir['referencia']): ?><br><span class="small texto-suave fst-italic">Ref: <?= e($dir['referencia']) ?></span><?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>

                    <label class="opcion-direccion">
                        <input class="form-check-input" type="radio" name="id_direccion" value="nueva" <?= $seleccionada === 'nueva' ? 'checked' : '' ?> data-alternar-nueva>
                        <span class="small fw-semibold">Usar otra dirección</span>
                    </label>

                    <div id="direccionNueva" class="row g-3 mt-1 <?= $seleccionada === 'nueva' ? '' : 'd-none' ?>">
                        <?php require RUTA_VISTAS . '/partials/campos_ubicacion.php'; ?>
                        <div class="col-12">
                            <label for="direccion" class="form-label">Dirección <span class="requerido">*</span></label>
                            <input type="text" class="form-control" id="direccion" name="direccion" maxlength="255" value="<?= e(old('direccion')) ?>" placeholder="Colonia, calle, pasaje, número de casa">
                        </div>
                        <div class="col-sm-8">
                            <label for="referencia" class="form-label">Punto de referencia</label>
                            <input type="text" class="form-control" id="referencia" name="referencia" maxlength="255" value="<?= e(old('referencia')) ?>">
                        </div>
                        <div class="col-sm-4">
                            <label for="alias" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="alias" name="alias" maxlength="40" placeholder="Casa, Trabajo…" value="<?= e(old('alias')) ?>">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="guardarDireccion" name="guardar_direccion" value="1" checked>
                                <label class="form-check-label small" for="guardarDireccion">Guardar en mis direcciones</label>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="tarjeta p-4">
                    <h2 class="tarjeta-titulo mb-3">Contacto</h2>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="telefono" class="form-label">Teléfono <span class="requerido">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">+503</span>
                                <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="7123-4567" required
                                       value="<?= e(preg_replace('/^\+503 /', '', old('telefono', (string) ($cliente['telefono'] ?? '')))) ?>">
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="notas" class="form-label">Notas para la entrega</label>
                            <textarea class="form-control" id="notas" name="notas" rows="2" maxlength="255" placeholder="Ej. Llamar antes de llegar"><?= e(old('notas')) ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="tarjeta p-4 mt-4">
                    <h2 class="tarjeta-titulo mb-3">Método de pago</h2>
                    <label class="opcion-direccion">
                        <input class="form-check-input" type="radio" name="metodo_pago" value="contra_entrega" checked data-metodo-pago>
                        <span>
                            <strong class="small"><i class="bi bi-cash-coin me-1"></i>Contra entrega</strong><br>
                            <span class="small texto-suave">Pagas en efectivo al recibir tus libros.</span>
                        </span>
                    </label>
                    <label class="opcion-direccion <?= $paypal ? '' : 'opacity-50' ?>">
                        <input class="form-check-input" type="radio" name="metodo_pago" value="paypal" data-metodo-pago <?= $paypal ? '' : 'disabled' ?>>
                        <span>
                            <strong class="small"><i class="bi bi-paypal me-1"></i>PayPal</strong><br>
                            <span class="small texto-suave">
                                <?= $paypal ? 'Paga ahora con tu cuenta PayPal o con tarjeta.' : 'No disponible por el momento.' ?>
                            </span>
                        </span>
                    </label>
                </section>
            </div>

            <div class="col-lg-5">
                <aside class="tarjeta p-4 position-sticky" style="top:calc(var(--alto-navbar) + 1rem)">
                    <h2 class="tarjeta-titulo mb-3">Tu pedido</h2>
                    <?php foreach ($items as $libro): ?>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="portada-mini"><?php require RUTA_VISTAS . '/partials/portada.php'; ?></span>
                            <div class="flex-grow-1 min-w-0">
                                <p class="small fw-semibold mb-0 text-truncate"><?= e($libro['titulo']) ?></p>
                                <p class="small texto-suave mb-0"><?= moneda($libro['precio']) ?> × <?= (int) $libro['cantidad'] ?></p>
                            </div>
                            <span class="small fw-semibold"><?= moneda($libro['subtotal']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="border-top pt-3 small">
                        <div class="d-flex justify-content-between mb-2"><span class="texto-suave">Subtotal</span><span><?= moneda($resumen['subtotal']) ?></span></div>
                        <div class="d-flex justify-content-between mb-2"><span class="texto-suave">Envío</span>
                            <span class="<?= $resumen['envio'] == 0 ? 'texto-verde fw-medium' : '' ?>"><?= $resumen['envio'] > 0 ? moneda($resumen['envio']) : 'Gratis' ?></span></div>
                        <div class="d-flex justify-content-between align-items-baseline border-top pt-3 mt-2">
                            <span class="fw-semibold">Total</span><span class="font-serif fw-bold fs-4"><?= moneda($resumen['total']) ?></span>
                        </div>
                    </div>
                    <div data-pago="contra_entrega">
                        <p class="small texto-suave mt-3">Pagas al recibir el pedido. Te avisaremos cada cambio de estado.</p>
                        <button type="submit" class="btn btn-pya w-100 py-3">Confirmar pedido</button>
                    </div>
                    <?php if ($paypal): ?>
                        <div data-pago="paypal" class="d-none">
                            <p class="small texto-suave mt-3">Se cobrará <?= moneda($resumen['total']) ?> en tu cuenta PayPal. Tu pedido se registra en cuanto se confirme el pago.</p>
                            <div id="paypalBotones" data-modo="<?= $paypalSimulado ? 'simulado' : 'real' ?>">
                                <?php if ($paypalSimulado): ?>
                                    <button type="button" class="btn-paypal-sim" id="btnPaypalSim" aria-label="Pagar con PayPal">
                                        <span class="logo-paypal"><b>Pay</b><b>Pal</b></span>
                                    </button>
                                    <p class="text-center texto-suave mt-2 mb-0" style="font-size:.72rem"><i class="bi bi-cone-striped me-1"></i>PayPal en modo de prueba: no se cobra dinero real.</p>
                                <?php endif; ?>
                            </div>
                            <p id="paypalProcesando" class="small texto-suave text-center d-none mb-0 mt-2">
                                <span class="spinner-border spinner-border-sm me-1"></span> Confirmando el pago…
                            </p>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
    </form>
</div>

<?php if ($paypal && $paypalSimulado): ?>
    <!-- Ventana que imita el checkout de PayPal (modo simulado) -->
    <div class="modal fade" id="modalPaypalSim" tabindex="-1" aria-labelledby="tituloPaypalSim" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
            <div class="modal-content ventana-paypal-sim">
                <div class="modal-header border-0 pb-0">
                    <span class="logo-paypal fs-4"><b>Pay</b><b>Pal</b></span>
                    <span class="ms-auto me-2 small texto-suave"><i class="bi bi-cart3 me-1"></i><span data-sim-monto><?= moneda($resumen['total']) ?></span> USD</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cancelar"></button>
                </div>
                <form class="modal-body pt-3" id="formPaypalSim" novalidate>
                    <h2 class="fs-5 fw-semibold mb-1" id="tituloPaypalSim">Paga con PayPal</h2>
                    <p class="small texto-suave mb-3">Compra en <?= e(APP_NOMBRE) ?> · Orden <span class="font-monospace" data-sim-orden></span></p>

                    <div class="mb-2">
                        <label for="simCorreo" class="form-label small">Correo electrónico</label>
                        <input type="email" class="form-control" id="simCorreo" name="correo" value="comprador@personal.example.com" required>
                    </div>
                    <div class="mb-3">
                        <label for="simContrasena" class="form-label small">Contraseña</label>
                        <input type="password" class="form-control" id="simContrasena" name="contrasena" value="prueba123" required>
                    </div>

                    <div class="border rounded-3 p-2 mb-3 small d-flex align-items-center gap-2">
                        <i class="bi bi-credit-card-2-front fs-5 text-primary"></i>
                        <span>Visa terminada en 4242<br><span class="texto-suave">Medio de pago predeterminado</span></span>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="simRechazar" name="rechazar" value="1">
                        <label class="form-check-label small texto-suave" for="simRechazar">Simular que el banco rechaza la tarjeta</label>
                    </div>

                    <button type="submit" class="btn btn-paypal-pagar w-100 py-2 fw-semibold">Pagar ahora</button>
                    <button type="button" class="btn btn-link w-100 small mt-1" data-bs-dismiss="modal">Cancelar y volver a <?= e(APP_NOMBRE) ?></button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
