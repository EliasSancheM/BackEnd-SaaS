<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use MercadoPago\Client\PaymentMethod\PaymentMethodClient;
use MercadoPago\MercadoPagoConfig;

echo '=== MercadoPago Sandbox Connection Test ==='.PHP_EOL.PHP_EOL;

echo 'Public Key: '.substr(config('services.mercadopago.public_key'), 0, 25).'...'.PHP_EOL;
echo 'Access Token: '.substr(config('services.mercadopago.access_token'), 0, 25).'...'.PHP_EOL;
echo 'Test User: '.env('MERCADOPAGO_TEST_USER').PHP_EOL.PHP_EOL;

try {
    MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

    $client = new PaymentMethodClient;
    $methods = $client->list();

    echo 'Payment Methods disponibles: '.count($methods->data).PHP_EOL;
    echo 'SUCCESS - Conexion exitosa con MercadoPago Sandbox!'.PHP_EOL;
} catch (Throwable $e) {
    echo 'Error: '.$e->getMessage().PHP_EOL;
}
