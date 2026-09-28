<?php
/**
 * PLANTILLA de configuración de PayPal.
 * Copie este archivo como app/config/paypal.php (ya está en .gitignore).
 *
 * Modos:
 *   'simulado' -> no necesita cuenta ni internet. Una ventana propia imita PayPal;
 *                 los pedidos y pagos se guardan igual que con PayPal real.
 *   'sandbox'  -> API real de PayPal en modo pruebas (sin dinero real). Requiere
 *                 Client ID y Secret de una app creada en https://developer.paypal.com
 *                 (Apps & Credentials -> Sandbox -> Create App).
 *   'live'     -> dinero real.
 */

return [
    'modo'          => 'simulado',
    'client_id'     => '<CLIENT_ID_SANDBOX>',   // solo para 'sandbox' y 'live'
    'client_secret' => '<SECRET_SANDBOX>',      // solo para 'sandbox' y 'live'
    'moneda'        => 'USD',                   // El Salvador usa dólares
];
