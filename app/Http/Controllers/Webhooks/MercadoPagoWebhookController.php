<?php

namespace App\Http\Controllers\Webhooks;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->all();

        $topic = $data['type'] ?? $request->query('topic');
        $resourceId = $data['data']['id'] ?? $request->query('id');

        if (! $topic || ! $resourceId) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        if (! $this->signatureIsValid($request, (string) $resourceId)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        if ($topic !== 'payment') {
            return response()->json(['message' => 'Unhandled topic'], 200);
        }

        $client = new PaymentClient;
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

        try {
            $mpPayment = $client->get($resourceId);

            $payment = Payment::query()
                ->where('provider_payment_id', $mpPayment->id)
                ->orWhere('raw_payload->preference_id', $mpPayment->preference_id ?? null)
                ->first();

            if (! $payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            }

            $status = match ($mpPayment->status) {
                'approved' => 'paid',
                'rejected', 'refunded', 'charged_back' => 'failed',
                'cancelled' => 'cancelled',
                default => 'pending',
            };

            $updateData = ['status' => $status, 'raw_payload' => $payment->raw_payload];

            if ($status === 'paid') {
                $updateData['paid_at'] = now();
            }

            $payment->update($updateData);

            return response()->json(['message' => 'OK']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }

    /**
     * Verify the `x-signature` header sent by MercadoPago.
     *
     * The signed manifest is `id:<data.id>;request-id:<x-request-id>;ts:<ts>;`
     * hashed with HMAC-SHA256 using the webhook secret.
     *
     * @see https://www.mercadopago.com/developers/en/docs/your-integrations/notifications/webhooks
     */
    private function signatureIsValid(Request $request, string $resourceId): bool
    {
        $secret = config('services.mercadopago.webhook_secret');

        if (! $secret) {
            return false;
        }

        $signature = $request->header('x-signature', '');
        $requestId = $request->header('x-request-id', '');

        // Parse `ts=<unix>,v1=<hash>` into its parts.
        $parts = [];
        foreach (explode(',', $signature) as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[trim($key)] = trim($value);
            }
        }

        $ts = $parts['ts'] ?? null;
        $hash = $parts['v1'] ?? null;

        if (! $ts || ! $hash) {
            return false;
        }

        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', strtolower($resourceId), $requestId, $ts);
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $hash);
    }
}
