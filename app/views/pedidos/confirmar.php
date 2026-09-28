<?php
/**
 * Confirmación de compra: dirección de entrega, teléfono y resumen.
 * Los montos definitivos los calculan los triggers al crear el pedido.
 * Variables: $items, $resumen, $direcciones, $departamentos, $cliente
 */
$seleccionada = old('id_direccion', $direcciones ? (string) $direcciones[0]['id_direccion'] : 'nueva');
?>
<div class="container-xl px-3 px-md-4 py-4 py-md-5">
    <a href="<?= url('/carrito') ?>" class="small text-decoration-none">← Volver al carrito</a>
    <h1 class="titulo-pagina mt-2 mb-4">Confirmar pedido</h1>

    <form action="<?= url('/pedido/crear') ?>" method="post" novalidate>
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
                    <p class="small texto-suave mt-3">El pago se realiza contra entrega. Te avisaremos cada cambio de estado.</p>
                    <button type="submit" class="btn btn-pya w-100 py-3">Confirmar pedido</button>
                </aside>
            </div>
        </div>
    </form>
</div>
