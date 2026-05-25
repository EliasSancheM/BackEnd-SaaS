<?php

use GuzzleHttp\Client;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$client = new Client(['timeout' => 15]);

echo '=== PayPal Sandbox Connection Test ==='.PHP_EOL.PHP_EOL;

echo 'Client ID: '.substr(config('services.paypal.client_id'), 0, 20).'...'.PHP_EOL;
echo 'Mode: '.config('services.paypal.mode').PHP_EOL;
echo 'Test Email: '.env('PAYPAL_TEST_EMAIL').PHP_EOL.PHP_EOL;

try {
    $response = $client->post('https://api-m.sandbox.paypal.com/v1/oauth2/token', [
        'auth' => [config('services.paypal.client_id'), config('services.paypal.secret')],
        'form_params' => ['grant_type' => 'client_credentials'],
    ]);

    $data = json_decode($response->getBody(), true);

    echo 'Access Token: '.substr($data['access_token'], 0, 50).'...'.PHP_EOL;
    echo 'Token Type: '.$data['token_type'].PHP_EOL;
    echo 'Expires In: '.$data['expires_in'].'s'.PHP_EOL;
    echo PHP_EOL.'SUCCESS - Conexion exitosa con PayPal Sandbox!'.PHP_EOL;
} catch (Throwable $e) {
    echo 'Error: '.$e->getMessage().PHP_EOL;
}
