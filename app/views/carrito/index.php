<?php
/**
 * Carrito con sus tres estados:
 *  1. con productos       -> tabla + resumen
 *  2. vacío sin pedidos   -> mensaje + explorar catálogo
 *  3. vacío con pedido    -> mensaje + tarjeta del pedido más reciente
 * Variables: $items, $resumen, [$ultimoPedido, $librosUltimo, $sugerencias]
 */
?>
<div class="container px-3 py-4 py-md-5" style="max-width:900px">
    <h1 class="titulo-pagina mb-4">Mi Carrito de Compra</h1>

    <?php if ($items): ?>
        <!-- 1. Carrito con productos -->
        <div id="carritoConProductos">
            <div class="tarjeta overflow-hidden mb-3">
                <div class="d-none d-sm-grid carrito-grid etiqueta-seccion px-4 py-3 border-bottom">
                    <span>Libro</span><span class="text-center">Cantidad</span><span class="text-end">Total</span><span></span>
                </div>
                <?php foreach ($items as $libro): ?>
                    <div class="carrito-grid carrito-linea px-3 px-sm-4 py-3" data-linea="<?= (int) $libro['id_libro'] ?>">
                        <div class="d-flex align-items-center gap-3 min-w-0">
                            <a href="<?= url('/libro/' . (int) $libro['id_libro']) ?>" class="portada-mini" style="width:52px;height:72px">
                                <?php require RUTA_VISTAS . '/partials/portada.php'; ?>
                            </a>
                            <div class="min-w-0">
                                <p class="fw-semibold small mb-0 text-truncate"><?= e($libro['titulo']) ?></p>
                                <p class="small texto-suave mb-1 text-truncate"><?= e($libro['autores'] ?? '') ?></p>
                                <p class="small mb-0" style="color:var(--color-teal-light)"><?= moneda($libro['precio']) ?> c/u</p>
                                <?php if ((int) $libro['stock_actual'] < (int) $libro['cantidad']): ?>
                                    <p class="small text-danger mb-0">Solo quedan <?= (int) $libro['stock_actual'] ?> unidades</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-sm-center">
                            <div class="input-group input-group-sm selector-cantidad">
                                <button class="btn btn-pya-suave" type="button" data-carrito-cambiar="-1" aria-label="Menos">−</button>
                                <input type="number" class="form-control text-center" value="<?= (int) $libro['cantidad'] ?>" min="1"
                                       max="<?= max(1, (int) $libro['stock_actual']) ?>" data-carrito-cantidad aria-label="Cantidad">
                                <button class="btn btn-pya-suave" type="button" data-carrito-cambiar="1" aria-label="Más">+</button>
                            </div>
                        </div>
                        <p class="fw-semibold small text-sm-end mb-0" data-linea-subtotal><?= moneda($libro['subtotal']) ?></p>
                        <form action="<?= url('/carrito/eliminar') ?>" method="post" class="text-end" data-carrito-eliminar>
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id_libro" value="<?= (int) $libro['id_libro'] ?>">
                            <button type="submit" class="btn-icono peligro" title="Quitar del carrito" aria-label="Quitar del carrito"><i class="bi bi-trash3"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-end mb-4">
                <a href="<?= url('/catalogo') ?>" class="btn btn-pya-contorno btn-sm"><i class="bi bi-plus-lg me-1"></i>Agregar más productos</a>
            </div>

            <div class="tarjeta p-4 mb-4">
                <h2 class="tarjeta-titulo mb-3">Resumen de Compra</h2>
                <div class="d-flex justify-content-between small mb-2">
                    <span class="texto-suave">Subtotal</span><span class="fw-medium" data-resumen="subtotal"><?= moneda($resumen['subtotal']) ?></span>
                </div>
                <div class="d-flex justify-content-between small mb-3">
                    <span class="texto-suave">Gastos de envío</span>
                    <span class="fw-medium <?= $resumen['envio'] == 0 ? 'texto-verde' : '' ?>" data-resumen="envio"><?= $resumen['envio'] > 0 ? moneda($resumen['envio']) : 'Gratis' ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-baseline border-top pt-3">
                    <span class="small fw-semibold">Total del Pedido</span>
                    <span class="font-serif fw-bold fs-3" data-resumen="total"><?= moneda($resumen['total']) ?></span>
                </div>
                <p class="small texto-suave mt-3 mb-0">
                    Envío fijo de <?= moneda(ENVIO_COSTO) ?>, gratis en compras mayores a <?= moneda(ENVIO_GRATIS_DESDE) ?>. No se cobra IVA.
                </p>
            </div>

            <a href="<?= url('/pedido/confirmar') ?>" class="btn btn-pya w-100 py-3 mb-3">Proceder al pago</a>
            <div class="text-center mb-4">
                <a href="<?= url('/catalogo') ?>" class="small text-decoration-none">← Seguir comprando</a>
            </div>
        </div>

    <?php elseif (!empty($ultimoPedido)): ?>
        <!-- 3. Vacío, con un pedido reciente -->
        <div class="tarjeta estado-vacio mb-4">
            <i class="bi bi-cart3" style="color:var(--color-orange)"></i>
            <h2 class="font-serif h4 mb-0">Tu carrito está vacío</h2>
            <p class="texto-suave small mb-2" style="max-width:24rem">Ya no tienes libros pendientes de compra. Mientras tanto, puedes revisar tu pedido más reciente.</p>
            <a href="<?= url('/catalogo') ?>" class="btn btn-pya px-5">Explorar catálogo</a>
        </div>

        <p class="etiqueta-seccion mb-3">Tu pedido más reciente</p>
        <?php $pedido = $ultimoPedido; $librosPedido = $librosUltimo; require RUTA_VISTAS . '/partials/tarjeta_pedido.php'; ?>
        <div class="text-center mt-4 mb-5">
            <a href="<?= url('/mis-pedidos') ?>" class="small fw-medium text-decoration-none">Ver historial completo de pedidos →</a>
        </div>

    <?php else: ?>
        <!-- 2. Vacío, sin pedidos -->
        <div class="tarjeta estado-vacio mb-5">
            <i class="bi bi-book"></i>
            <p class="fs-5 fw-medium mb-0">Tu carrito está vacío</p>
            <p class="texto-suave small mb-2" style="max-width:20rem">Aún no has añadido ningún libro. Explora nuestro catálogo y encuentra tu próxima lectura.</p>
            <a href="<?= url('/catalogo') ?>" class="btn btn-pya px-4">Explorar catálogo</a>
        </div>
    <?php endif; ?>

    <?php if (!$items && !empty($sugerencias)): ?>
        <section class="mb-4">
            <div class="d-flex align-items-baseline justify-content-between border-bottom pb-3 mb-4">
                <h2 class="font-serif h4 mb-0">Quizá te interese</h2>
                <a href="<?= url('/catalogo') ?>" class="btn-enlace text-decoration-none">Ver catálogo completo →</a>
            </div>
            <div class="row row-cols-2 row-cols-md-4 g-3">
                <?php foreach ($sugerencias as $libro): ?>
                    <div class="col"><?php require RUTA_VISTAS . '/partials/libro_card.php'; ?></div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
