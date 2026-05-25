<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedDemoData();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->tenant = Tenant::where('slug', 'demo-company')->firstOrFail();
    }

    private function createUserWithRole(string $role): User
    {
        setPermissionsTeamId($this->tenant->id);

        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_viewer_can_view_clients(): void
    {
        $user = $this->createUserWithRole('viewer');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/clients');

        $response->assertOk();
    }

    public function test_viewer_cannot_create_client(): void
    {
        $user = $this->createUserWithRole('viewer');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/clients', [
            'name' => 'New Client',
            'email' => 'new@example.com',
        ]);

        $response->assertForbidden();
    }

    public function test_viewer_cannot_update_client(): void
    {
        $user = $this->createUserWithRole('viewer');
        Sanctum::actingAs($user);
        $client = Client::where('name', 'Acme SpA')->firstOrFail();

        $response = $this->putJson('/api/clients/'.$client->id, [
            'name' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    public function test_viewer_cannot_delete_client(): void
    {
        $user = $this->createUserWithRole('viewer');
        Sanctum::actingAs($user);
        $client = Client::where('name', 'Acme SpA')->firstOrFail();

        $response = $this->deleteJson('/api/clients/'.$client->id);

        $response->assertForbidden();
    }

    public function test_viewer_can_view_invoices(): void
    {
        $user = $this->createUserWithRole('viewer');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/invoices');

        $response->assertOk();
    }

    public function test_viewer_cannot_create_invoice(): void
    {
        $user = $this->createUserWithRole('viewer');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/invoices', [
            'client_id' => 1,
            'number' => 'F-001',
            'total' => 100,
        ]);

        $response->assertForbidden();
    }

    public function test_viewer_cannot_update_invoice(): void
    {
        $user = $this->createUserWithRole('viewer');
        Sanctum::actingAs($user);
        $invoice = Invoice::where('number', 'F-00000001')->firstOrFail();

        $response = $this->putJson('/api/invoices/'.$invoice->id, [
            'status' => 'paid',
        ]);

        $response->assertForbidden();
    }

    public function test_viewer_cannot_delete_invoice(): void
    {
        $user = $this->createUserWithRole('viewer');
        Sanctum::actingAs($user);
        $invoice = Invoice::where('number', 'F-00000001')->firstOrFail();

        $response = $this->deleteJson('/api/invoices/'.$invoice->id);

        $response->assertForbidden();
    }

    public function test_viewer_cannot_create_payment(): void
    {
        $user = $this->createUserWithRole('viewer');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/payments', [
            'invoice_id' => 1,
            'amount' => 100,
        ]);

        $response->assertForbidden();
    }

    public function test_viewer_cannot_run_checkout(): void
    {
        $user = $this->createUserWithRole('viewer');
        Sanctum::actingAs($user);
        $payment = Payment::query()->firstOrFail();

        $response = $this->postJson("/api/payments/{$payment->id}/checkout");

        $response->assertForbidden();
    }

    public function test_viewer_cannot_send_invoice(): void
    {
        $user = $this->createUserWithRole('viewer');
        Sanctum::actingAs($user);
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id, 'email' => 'client@test.cl']);
        $draft = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/invoices/{$draft->id}/send");

        $response->assertForbidden();
    }

    public function test_billing_can_create_invoice(): void
    {
        $user = $this->createUserWithRole('billing');
        Sanctum::actingAs($user);
        $client = Client::where('name', 'Acme SpA')->firstOrFail();

        $response = $this->postJson('/api/invoices', [
            'client_id' => $client->id,
            'number' => 'F-99999999',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'currency' => 'CLP',
            'subtotal' => 100,
            'tax_total' => 19,
            'total' => 119,
        ]);

        $response->assertCreated();
    }

    public function test_billing_cannot_delete_invoice(): void
    {
        $user = $this->createUserWithRole('billing');
        Sanctum::actingAs($user);
        $invoice = Invoice::where('number', 'F-00000001')->firstOrFail();

        $response = $this->deleteJson('/api/invoices/'.$invoice->id);

        $response->assertForbidden();
    }

    public function test_other_tenant_is_isolation(): void
    {
        $otherTenant = Tenant::factory()->create(['slug' => 'other-tenant']);
        setPermissionsTeamId($otherTenant->id);

        $permissions = ['clients.view', 'invoices.view'];
        foreach ($permissions as $perm) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web', 'tenant_id' => $otherTenant->id]);
        $role->syncPermissions($permissions);

        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherUser->assignRole('viewer');

        Sanctum::actingAs($otherUser);

        $response = $this->getJson('/api/clients');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_unauthenticated_cannot_access(): void
    {
        $response = $this->getJson('/api/clients');

        $response->assertUnauthorized();
    }
}
