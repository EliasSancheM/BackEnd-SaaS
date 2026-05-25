<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant = Tenant::create([
            'name' => 'Demo Company',
            'slug' => 'demo-company',
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(14),
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme SpA',
            'rut' => '76123456-7',
            'email' => 'billing@acme.test',
            'phone' => '+56 9 1234 5678',
            'address' => 'Av. Principal 123',
            'city' => 'Santiago',
            'notes' => 'Cliente demo',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'number' => 'F-00000001',
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

        InvoiceItem::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'description' => 'Servicio mensual',
            'quantity' => 1,
            'unit_price' => 100000,
            'total' => 100000,
            'sort_order' => 1,
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'provider' => 'mercadopago',
            'provider_payment_id' => '12345678',
            'amount' => 119000,
            'status' => 'pending',
            'paid_at' => null,
            'raw_payload' => [],
        ]);
    }
}
