<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantApiTest extends TestCase
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

        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_login_serializes_user_roles(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.roles.0.name', 'owner');
    }

    public function test_get_tenant_returns_current_tenant(): void
    {
        Sanctum::actingAs($this->createUserWithRole('owner'));

        $response = $this->getJson('/api/tenant');

        $response->assertOk()->assertJsonPath('name', 'Demo Company');
    }

    public function test_admin_can_update_company_settings(): void
    {
        Sanctum::actingAs($this->createUserWithRole('owner'));

        $response = $this->putJson('/api/tenant', [
            'settings' => [
                'companyName' => 'Nueva Razón Social SpA',
                'vatRate' => 19,
                'invoicePrefix' => 'FAC',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('settings.companyName', 'Nueva Razón Social SpA')
            ->assertJsonPath('settings.invoicePrefix', 'FAC')
            ->assertJsonPath('name', 'Nueva Razón Social SpA'); // name se sincroniza

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'name' => 'Nueva Razón Social SpA',
        ]);
    }

    public function test_settings_update_merges_partial_changes(): void
    {
        Sanctum::actingAs($this->createUserWithRole('owner'));

        $this->putJson('/api/tenant', ['settings' => ['giro' => 'Servicios TI', 'city' => 'Santiago']])->assertOk();
        $response = $this->putJson('/api/tenant', ['settings' => ['city' => 'Valparaíso']]);

        $response->assertOk()
            ->assertJsonPath('settings.giro', 'Servicios TI')      // se conserva
            ->assertJsonPath('settings.city', 'Valparaíso');       // se actualiza
    }

    public function test_viewer_cannot_update_tenant(): void
    {
        Sanctum::actingAs($this->createUserWithRole('viewer'));

        $response = $this->putJson('/api/tenant', [
            'settings' => ['companyName' => 'Hacked SpA'],
        ]);

        $response->assertForbidden();
    }
}
