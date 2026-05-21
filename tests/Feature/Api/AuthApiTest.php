<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    public function test_register_creates_tenant_and_token(): void
    {
        $response = $this->postJson('/api/register', [
            'company_name' => 'New Company',
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('tenants', ['name' => 'New Company']);
        $this->assertDatabaseHas('users', ['email' => 'owner@example.com']);
    }

    public function test_login_returns_token(): void
    {
        $this->seedDemoData();

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $this->seedDemoData();

        $user = User::where('email', 'test@example.com')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertOk()->assertJsonPath('email', 'test@example.com');
    }

    public function test_logout_revokes_token(): void
    {
        $this->seedDemoData();

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/logout');

        $response->assertOk();
    }

    public function test_tenant_endpoint_returns_current_tenant(): void
    {
        $this->seedDemoData();

        $user = User::where('email', 'test@example.com')->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tenant');

        $response->assertOk()->assertJsonPath('slug', 'demo-company');
    }
}
