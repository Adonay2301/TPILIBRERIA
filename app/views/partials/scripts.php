<?php
/**
 * Scripts comunes. Una vista puede pedir scripts propios definiendo
 * $scripts = ['js/carrito.js'] (rutas relativas a public/assets).
 */
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php foreach ($scripts ?? [] as $script): ?>
    <script src="<?= asset($script) ?>"></script>
<?php endforeach; ?>
