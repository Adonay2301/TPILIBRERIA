<?php
/**
 * <head> común a todos los layouts.
 * Variables: $titulo (opcional)
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="base-url" content="<?= e(BASE_URL) ?>">
<title><?= isset($titulo) ? e($titulo) . ' · ' : '' ?><?= e(APP_NOMBRE) ?></title>

<!-- Bootstrap 5 + Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Tipografías del prototipo: Lora (títulos) e Inter (texto) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap">

<link rel="stylesheet" href="<?= asset('css/estilos.css') ?>">
