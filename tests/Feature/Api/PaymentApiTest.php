<?php

namespace Tests\Feature\Api;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    public function test_index_returns_payments(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/payments');

        $response->assertOk()->assertJsonStructure(['data']);
    }

    public function test_store_creates_mercadopago_payment(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $invoice = Invoice::where('number', 'F-00000001')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/payments', [
            'invoice_id' => $invoice->id,
            'provider' => 'mercadopago',
            'provider_payment_id' => 'MP12345',
            'amount' => 119000,
            'status' => 'pending',
        ]);

        $response->assertCreated()->assertJsonPath('provider', 'mercadopago');
    }

    public function test_store_creates_paypal_payment(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $invoice = Invoice::where('number', 'F-00000001')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/payments', [
            'invoice_id' => $invoice->id,
            'provider' => 'paypal',
            'provider_payment_id' => 'PP-ORDER-12345',
            'paypal_order_id' => 'PAY-ORDER-ABC-123',
            'paypal_payer_id' => 'PAYERID-999',
            'amount' => 25000,
            'status' => 'completed',
        ]);

        $response->assertCreated()
            ->assertJsonPath('provider', 'paypal')
            ->assertJsonPath('paypal_order_id', 'PAY-ORDER-ABC-123')
            ->assertJsonPath('paypal_payer_id', 'PAYERID-999');
    }

    public function test_store_validates_provider(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $invoice = Invoice::where('number', 'F-00000001')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/payments', [
            'invoice_id' => $invoice->id,
            'provider' => 'invalid_provider',
            'amount' => 1000,
        ]);

        $response->assertStatus(422);
    }

    public function test_update_changes_payment(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/payments/'.$payment->id, [
            'status' => 'paid',
        ]);

        $response->assertOk()->assertJsonPath('status', 'paid');
    }
}
