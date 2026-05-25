<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Preference;

class MercadoPagoService
{
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
    }

    public function createCheckout(Invoice $invoice): Preference
    {
        $client = new PreferenceClient;

        $items = $invoice->items->map(fn ($item) => [
            'id' => (string) $item->id,
            'title' => $item->description,
            'description' => $item->description,
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'currency_id' => $invoice->currency,
        ])->toArray();

        $request = [
            'items' => $items,
            'external_reference' => (string) $invoice->id,
            'notification_url' => route('webhooks.mercadopago'),
            'back_urls' => [
                'success' => config('app.url').'/payments/success',
                'failure' => config('app.url').'/payments/failure',
                'pending' => config('app.url').'/payments/pending',
            ],
            'auto_return' => 'approved',
            'statement_descriptor' => config('app.name'),
        ];

        try {
            return $client->create($request);
        } catch (MPApiException $e) {
            $body = $e->getApiResponse()->getBody();

            throw new \RuntimeException(
                'MercadoPago error: '.($body['message'] ?? $e->getMessage()),
                $e->getCode()
            );
        }
    }
}
