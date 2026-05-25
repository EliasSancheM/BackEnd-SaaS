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
}
