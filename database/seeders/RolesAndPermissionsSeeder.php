<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant = Tenant::firstOrCreate([
            'slug' => 'demo',
        ], [
            'name' => 'Demo Company',
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $owner = User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'tenant_id' => $tenant->id,
            'name' => 'Test User',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($tenant->id);

        $permissions = [
            'clients.view',
            'clients.create',
            'clients.edit',
            'clients.delete',
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.send',
            'invoices.delete',
            'reports.view',
            'reports.export',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            'owner' => $permissions,
            'admin' => [
                'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
                'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.send', 'invoices.delete',
                'reports.view', 'reports.export',
            ],
            'billing' => [
                'clients.view',
                'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.send',
            ],
            'viewer' => [
                'clients.view',
                'invoices.view',
                'reports.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);

            $role->syncPermissions($rolePermissions);
        }

        $owner->syncRoles(['owner']);
    }
}
