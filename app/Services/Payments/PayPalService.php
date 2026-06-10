<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use GuzzleHttp\Client;

class PayPalService
{
    private readonly Client $client;

    private string $accessToken;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->baseUri(),
            'timeout' => 15,
        ]);
    }

    private function baseUri(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com/'
            : 'https://api-m.sandbox.paypal.com/';
    }

    private function getAccessToken(): string
    {
        if (isset($this->accessToken)) {
            return $this->accessToken;
        }

        $response = $this->client->post('/v1/oauth2/token', [
            'auth' => [
                config('services.paypal.client_id'),
                config('services.paypal.secret'),
            ],
            'form_params' => ['grant_type' => 'client_credentials'],
        ]);

        $data = json_decode($response->getBody(), true);

        return $this->accessToken = $data['access_token'];
    }

    public function createOrder(Invoice $invoice): array
    {
        $token = $this->getAccessToken();

        $items = $invoice->items->map(fn ($item) => [
            'name' => $item->description,
            'description' => $item->description,
            'quantity' => (string) $item->quantity,
            'unit_amount' => [
                'currency_code' => $invoice->currency,
                'value' => number_format((float) $item->unit_price, 2, '.', ''),
            ],
        ])->toArray();

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => (string) $invoice->id,
                    'description' => 'Factura '.$invoice->number,
                    'items' => $items,
                    'amount' => [
                        'currency_code' => $invoice->currency,
                        'value' => number_format((float) $invoice->total, 2, '.', ''),
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => $invoice->currency,
                                'value' => number_format((float) $invoice->subtotal, 2, '.', ''),
                            ],
                            'tax_total' => [
                                'currency_code' => $invoice->currency,
                                'value' => number_format((float) $invoice->tax_total, 2, '.', ''),
                            ],
                        ],
                    ],
                ],
            ],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'landing_page' => 'LOGIN',
                        'user_action' => 'PAY_NOW',
                        'return_url' => route('paypal.return'),
                        'cancel_url' => route('paypal.cancel'),
                    ],
                ],
            ],
        ];

        $response = $this->client->post('/v2/checkout/orders', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $order = json_decode($response->getBody(), true);

        $approvalLink = collect($order['links'] ?? [])
            ->firstWhere('rel', 'payer-action');

        return [
            'id' => $order['id'],
            'status' => $order['status'],
            'approval_url' => $approvalLink['href'] ?? null,
        ];
    }

    public function captureOrder(string $paypalOrderId): array
    {
        $token = $this->getAccessToken();

        $response = $this->client->post("/v2/checkout/orders/{$paypalOrderId}/capture", [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function verifyWebhookSignature(array $headers, string $body): bool
    {
        $token = $this->getAccessToken();

        $webhookId = config('services.paypal.webhook_id');

        if (! $webhookId) {
            return false;
        }

        $payload = [
            'auth_algo' => $headers['PAYPAL-AUTH-ALGO'] ?? '',
            'cert_url' => $headers['PAYPAL-CERT-URL'] ?? '',
            'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'] ?? '',
            'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
            'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
            'webhook_id' => $webhookId,
            'webhook_event' => json_decode($body, true),
        ];

        $response = $this->client->post('/v1/notifications/verify-webhook-signature', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $result = json_decode($response->getBody(), true);

        return ($result['verification_status'] ?? '') === 'SUCCESS';
    }
}
