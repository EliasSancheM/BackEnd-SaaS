<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientApiTest extends TestCase
{
    public function test_index_returns_clients(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/clients');

        $response->assertOk()->assertJsonStructure(['data']);
    }

    public function test_store_creates_client(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/clients', [
            'name' => 'New Client',
            'rut' => '12345678-9',
            'email' => 'client@example.com',
            'phone' => '123456789',
            'address' => 'Street 1',
            'city' => 'Santiago',
            'notes' => 'Demo',
        ]);

        $response->assertCreated()->assertJsonPath('name', 'New Client');
        $this->assertDatabaseHas('clients', ['email' => 'client@example.com']);
    }

    public function test_update_changes_client(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $client = Client::where('name', 'Acme SpA')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/clients/'.$client->id, [
            'name' => 'Acme Updated',
        ]);

        $response->assertOk()->assertJsonPath('name', 'Acme Updated');
    }

    public function test_delete_rejects_client_with_invoices(): void
    {
        $this->seedDemoData();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $client = Client::where('name', 'Acme SpA')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/clients/'.$client->id);

        $response->assertStatus(409);
    }
}
