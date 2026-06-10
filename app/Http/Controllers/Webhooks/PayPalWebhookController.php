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
        // Browser redirect after the buyer approves the order (not a signed webhook).
        // The capture call below authenticates against PayPal with our own
        // credentials, so the order id alone cannot be used to forge a payment.
        if ($request->query('action') === 'return') {
            $token = $request->input('token');

            if (! $token) {
                return response()->json(['message' => 'Missing token'], 400);
            }

            return $this->capture($token);
        }

        // Server-to-server webhook: must carry a valid PayPal signature.
        if (! $this->paypal->verifyWebhookSignature($this->normalizeHeaders($request), $request->getContent())) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        if ($request->input('event_type') === 'CHECKOUT.ORDER.APPROVED') {
            $orderId = $request->input('resource.id');

            if (! $orderId) {
                return response()->json(['message' => 'Missing order ID'], 400);
            }

            return $this->capture($orderId);
        }

        return response()->json(['message' => 'Unhandled event type'], 200);
    }

    private function capture(string $orderId): JsonResponse
    {
        $payment = Payment::query()->where('paypal_order_id', $orderId)->first();

        if (! $payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Idempotency: ignore orders that were already captured.
        if ($payment->status === 'paid') {
            return response()->json(['message' => 'Payment already captured']);
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

    /**
     * Flatten request headers to the single-value, upper-case form that
     * PayPalService::verifyWebhookSignature expects.
     *
     * @return array<string, string>
     */
    private function normalizeHeaders(Request $request): array
    {
        $headers = [];

        foreach ($request->headers->all() as $key => $values) {
            $headers[strtoupper($key)] = is_array($values) ? ($values[0] ?? '') : $values;
        }

        return $headers;
    }
}
