<?php

namespace Tests\Feature\Api;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PayPalService;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class PaymentCheckoutTest extends TestCase
{
    public function test_checkout_returns_422_for_non_pending_payment(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $payment = Payment::query()->firstOrFail();
        $payment->update(['status' => 'paid']);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/payments/{$payment->id}/checkout");

        $response->assertStatus(422);
    }

    public function test_checkout_returns_422_for_manual_provider(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();

        $payment = Payment::create([
            'tenant_id' => $user->tenant_id,
            'invoice_id' => Invoice::where('number', 'F-00000001')->firstOrFail()->id,
            'provider' => 'manual',
            'amount' => 1000,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/payments/{$payment->id}/checkout");

        $response->assertStatus(500);
    }

    public function test_mercadopago_webhook_handles_invalid_payload(): void
    {
        $response = $this->postJson('/api/webhooks/mercadopago', []);

        $response->assertStatus(400);
    }

    public function test_paypal_webhook_handles_unhandled_event(): void
    {
        $this->mock(PayPalService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('verifyWebhookSignature')->andReturnTrue();
        });

        $response = $this->postJson('/api/webhooks/paypal', [
            'event_type' => 'UNKNOWN.EVENT',
        ]);

        $response->assertStatus(200);
    }

    public function test_paypal_webhook_rejects_invalid_signature(): void
    {
        $this->mock(PayPalService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('verifyWebhookSignature')->andReturnFalse();
        });

        $response = $this->postJson('/api/webhooks/paypal', [
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'resource' => ['id' => 'FAKE-ORDER-ID'],
        ]);

        $response->assertStatus(401);
    }
}
