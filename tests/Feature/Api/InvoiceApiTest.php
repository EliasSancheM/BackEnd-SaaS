<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    public function test_index_returns_invoices(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/invoices');

        $response->assertOk()->assertJsonStructure(['data']);
    }

    public function test_store_creates_invoice(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $client = Client::where('name', 'Acme SpA')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/invoices', [
            'client_id' => $client->id,
            'number' => 'F-00000002',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'currency' => 'CLP',
            'subtotal' => 1000,
            'tax_total' => 190,
            'total' => 1190,
            'notes' => 'Test',
        ]);

        $response->assertCreated()->assertJsonPath('number', 'F-00000002');
        $this->assertDatabaseHas('invoices', ['number' => 'F-00000002']);
    }

    public function test_show_returns_invoice_with_relations(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $invoice = Invoice::where('number', 'F-00000001')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/invoices/'.$invoice->id);

        $response->assertOk()->assertJsonPath('number', 'F-00000001');
    }

    public function test_update_changes_invoice(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $invoice = Invoice::where('number', 'F-00000001')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/invoices/'.$invoice->id, [
            'status' => 'paid',
        ]);

        $response->assertOk()->assertJsonPath('status', 'paid');
    }

    public function test_invoice_items_index_returns_items(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/invoice-items');

        $response->assertOk()->assertJsonStructure(['data']);
    }

    public function test_store_creates_invoice_item(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $invoice = Invoice::where('number', 'F-00000001')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/invoice-items', [
            'invoice_id' => $invoice->id,
            'description' => 'Extra item',
            'quantity' => 1,
            'unit_price' => 5000,
            'total' => 5000,
            'sort_order' => 2,
        ]);

        $response->assertCreated()->assertJsonPath('description', 'Extra item');
    }

    public function test_update_changes_invoice_item(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $item = InvoiceItem::where('description', 'Servicio mensual')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/invoice-items/'.$item->id, [
            'description' => 'Servicio actualizado',
        ]);

        $response->assertOk()->assertJsonPath('description', 'Servicio actualizado');
    }

    public function test_delete_removes_invoice_item(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $item = InvoiceItem::where('description', 'Servicio mensual')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/invoice-items/'.$item->id);

        $response->assertNoContent();
    }

    public function test_send_changes_status_to_sent(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();

        Sanctum::actingAs($user);

        $client = Client::factory()->create(['tenant_id' => $user->tenant_id, 'email' => 'client@test.cl']);
        $draft = Invoice::factory()->create([
            'tenant_id' => $user->tenant_id,
            'client_id' => $client->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/invoices/{$draft->id}/send");

        $response->assertOk()->assertJsonPath('invoice.status', 'sent');
    }

    public function test_send_fails_for_non_draft(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $sent = Invoice::where('number', 'F-00000001')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/invoices/{$sent->id}/send");

        $response->assertStatus(422);
    }
}
