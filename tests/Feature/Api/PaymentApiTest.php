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

    public function test_store_creates_payment(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $invoice = Invoice::where('number', 'F-00000001')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/payments', [
            'invoice_id' => $invoice->id,
            'provider' => 'manual',
            'amount' => 119000,
            'status' => 'pending',
        ]);

        $response->assertCreated()->assertJsonPath('provider', 'manual');
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
