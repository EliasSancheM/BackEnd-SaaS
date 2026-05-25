<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\MercadoPagoService;
use App\Services\Payments\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly MercadoPagoService $mercadopago,
        private readonly PayPalService $paypal,
    ) {}

    public function __invoke(Request $request, Payment $payment): JsonResponse
    {
        $invoice = $payment->invoice()->with('items')->firstOrFail();

        if ($payment->status !== 'pending') {
            return response()->json(['message' => 'Payment is not pending'], 422);
        }

        return match ($payment->provider) {
            'mercadopago' => $this->mercadopagoCheckout($payment, $invoice),
            'paypal' => $this->paypalCheckout($payment, $invoice),
            default => throw new RuntimeException('Unsupported payment provider'),
        };
    }

    private function mercadopagoCheckout(Payment $payment, mixed $invoice): JsonResponse
    {
        $preference = $this->mercadopago->createCheckout($invoice);

        $payment->updateQuietly([
            'provider_payment_id' => $preference->id,
            'raw_payload' => ['preference_id' => $preference->id],
        ]);

        return response()->json([
            'checkout_url' => $preference->init_point,
            'preference_id' => $preference->id,
        ]);
    }

    private function paypalCheckout(Payment $payment, mixed $invoice): JsonResponse
    {
        $order = $this->paypal->createOrder($invoice);

        $payment->updateQuietly([
            'paypal_order_id' => $order['id'],
            'provider_payment_id' => $order['id'],
            'raw_payload' => ['order_status' => $order['status']],
        ]);

        return response()->json([
            'checkout_url' => $order['approval_url'],
            'order_id' => $order['id'],
        ]);
    }
}
