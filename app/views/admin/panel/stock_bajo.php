<?php
/**
 * Bloque "Libros con pocas existencias" (vista vw_stock_bajo). Lo usan los dos paneles.
 * Variables: $stockBajo
 */
?>
<section class="tarjeta overflow-hidden">
    <div class="tarjeta-encabezado" style="background:#fff5f5;border-color:#fecaca">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle text-danger"></i>
            <div>
                <h2 style="color:var(--color-rojo)">Libros con pocas existencias</h2>
                <p class="small mb-0" style="color:#b45454">Stock en el mínimo o por debajo</p>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table tabla-pya">
            <thead><tr><th>Libro</th><th class="text-center">Existencias</th><th class="text-center">Mínimo</th><th class="text-center">Estado</th><th class="text-end">Acción</th></tr></thead>
            <tbody>
            <?php foreach ($stockBajo as $libro): $st = estadoStock((int) $libro['stock_actual'], (int) $libro['stock_minimo']); ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <span class="portada-mini" style="width:30px;height:42px"><?php require RUTA_VISTAS . '/partials/portada.php'; ?></span>
                            <div class="min-w-0">
                                <p class="fw-semibold mb-0 text-truncate"><?= e($libro['titulo']) ?></p>
                                <p class="small texto-suave mb-0 text-truncate"><?= e($libro['autores'] ?? '') ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="text-center"><span class="insignia <?= $st['clase'] ?> fs-6"><?= (int) $libro['stock_actual'] ?></span></td>
                    <td class="text-center texto-suave"><?= (int) $libro['stock_minimo'] ?></td>
                    <td class="text-center"><span class="insignia <?= $st['clase'] ?>"><span class="punto"></span><?= e($st['texto']) ?></span></td>
                    <td class="text-end"><a href="<?= url('/admin/libros?q=' . urlencode($libro['isbn'])) ?>" class="btn-enlace text-decoration-none">Ir a inventario <i class="bi bi-chevron-right"></i></a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$stockBajo): ?><tr><td colspan="5" class="text-center texto-suave py-4">Todo el inventario está sobre el mínimo.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top" style="background:rgba(245,240,232,.4)">
        <span class="small texto-suave"><?= count($stockBajo) ?> títulos requieren reabastecimiento</span>
        <a href="<?= url('/admin/libros?disponibilidad=bajas') ?>" class="btn btn-pya btn-sm">Ir a inventario</a>
    </div>
</section>
