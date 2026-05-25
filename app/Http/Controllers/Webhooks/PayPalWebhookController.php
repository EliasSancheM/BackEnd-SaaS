<?php

namespace App\Http\Controllers\Webhooks;

use App\Models\Payment;
use App\Services\Payments\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PayPalWebhookController extends Controller
{
    public function __construct(
        private readonly PayPalService $paypal,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $eventType = $request->input('event_type');
        $resource = $request->input('resource', []);

        if ($request->query('action') === 'return') {
            $token = $request->input('token');

            if (! $token) {
                return response()->json(['message' => 'Missing token'], 400);
            }

            $payment = Payment::query()->where('paypal_order_id', $token)->first();

            if (! $payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            }

            try {
                $captured = $this->paypal->captureOrder($token);

                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'raw_payload' => array_merge((array) $payment->raw_payload, [
                        'capture' => $captured,
                    ]),
                ]);

                return response()->json(['message' => 'Payment captured']);
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Capture failed'], 500);
            }
        }

        if ($eventType === 'CHECKOUT.ORDER.APPROVED') {
            $orderId = $resource['id'] ?? null;

            if (! $orderId) {
                return response()->json(['message' => 'Missing order ID'], 400);
            }

            $payment = Payment::query()->where('paypal_order_id', $orderId)->first();

            if (! $payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            }

            try {
                $captured = $this->paypal->captureOrder($orderId);

                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'raw_payload' => array_merge((array) $payment->raw_payload, [
                        'capture' => $captured,
                    ]),
                ]);

                return response()->json(['message' => 'Payment captured']);
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Capture failed'], 500);
            }
        }

        return response()->json(['message' => 'Unhandled event type'], 200);
    }
}
