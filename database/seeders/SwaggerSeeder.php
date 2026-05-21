<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class SwaggerSeeder extends Seeder
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

        User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'tenant_id' => $tenant->id,
            'name' => 'Test User',
            'password' => Hash::make('password'),
        ]);

        $client = Client::firstOrCreate([
            'tenant_id' => $tenant->id,
            'name' => 'Acme SpA',
        ], [
            'rut' => '76123456-7',
            'email' => 'billing@acme.test',
            'phone' => '+56 9 1234 5678',
            'address' => 'Av. Principal 123',
            'city' => 'Santiago',
            'notes' => 'Cliente demo',
        ]);

        Invoice::firstOrCreate([
            'tenant_id' => $tenant->id,
            'number' => 'F-00000001',
        ], [
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'currency' => 'CLP',
            'subtotal' => 100000,
            'tax_total' => 19000,
            'total' => 119000,
            'notes' => 'Factura demo',
            'sent_at' => now(),
            'paid_at' => null,
        ]);
    }
}
