<?php
/**
 * Scripts comunes. Una vista puede pedir scripts propios definiendo
 * $scripts = ['js/carrito.js'] (rutas relativas a public/assets)
 * o URLs completas de terceros (https://…), como el SDK de PayPal.
 */
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php foreach ($scripts ?? [] as $script): ?>
    <script src="<?= e(str_starts_with($script, 'https://') ? $script : asset($script)) ?>"></script>
<?php endforeach; ?>
