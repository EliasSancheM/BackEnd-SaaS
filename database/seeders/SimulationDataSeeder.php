<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SimulationDataSeeder extends Seeder
{
    private int $userCounter = 0;

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $totalTenants = 100;
        $totalUsers = 0;
        $totalClients = 0;
        $totalInvoices = 0;
        $totalItems = 0;
        $totalPayments = 0;

        $this->command->info('Creating simulation data...');

        $this->createDemoTenant();
        $totalUsers++;

        $bar = $this->command->getOutput()->createProgressBar($totalTenants - 1);
        $bar->start();

        for ($t = 1; $t < $totalTenants; $t++) {
            DB::beginTransaction();

            try {
                $tenant = Tenant::create([
                    'name' => fake()->company(),
                    'slug' => 'tenant-'.$t,
                    'plan' => fake()->randomElement(['free', 'free', 'free', 'pro', 'pro', 'enterprise']),
                    'trial_ends_at' => fake()->boolean(30) ? now()->addDays(rand(1, 30)) : null,
                ]);

                setPermissionsTeamId($tenant->id);

                $this->ensureTenantRoles($tenant);

                $usersPerTenant = $this->createUsersForTenant($tenant);
                $totalUsers += count($usersPerTenant);

                $clientCount = rand(10, 25);
                $clients = collect();

                for ($i = 0; $i < $clientCount; $i++) {
                    $clients->push(Client::create([
                        'tenant_id' => $tenant->id,
                        'name' => fake()->company(),
                        'rut' => fake()->numerify('########-#'),
                        'email' => fake()->safeEmail(),
                        'phone' => fake()->phoneNumber(),
                        'address' => fake()->streetAddress(),
                        'city' => fake()->city(),
                        'notes' => fake()->optional(0.3)->sentence(),
                    ]));
                }

                $totalClients += $clientCount;

                $invoiceSeq = 0;

                foreach ($clients as $client) {
                    $invoicesPerClient = rand(0, 5);

                    for ($i = 0; $i < $invoicesPerClient; $i++) {
                        $invoiceSeq++;
                        $status = $this->weightedRandom([
                            'draft' => 30,
                            'sent' => 25,
                            'paid' => 25,
                            'overdue' => 12,
                            'cancelled' => 8,
                        ]);

                        $issueDate = now()->subDays(rand(1, 365));
                        $dueDate = (clone $issueDate)->addDays(rand(15, 60));

                        $currency = fake()->randomElement(['CLP', 'CLP', 'CLP', 'CLP', 'USD']);

                        $sentAt = in_array($status, ['sent', 'paid', 'overdue'], true)
                            ? (clone $issueDate)->addDays(rand(1, 30))
                            : null;

                        $paidAt = $status === 'paid'
                            ? (clone $issueDate)->addDays(rand(1, 60))
                            : null;

                        $invoice = Invoice::create([
                            'tenant_id' => $tenant->id,
                            'client_id' => $client->id,
                            'number' => 'F-'
                                .str_pad((string) $tenant->id, 4, '0', STR_PAD_LEFT).'-'
                                .str_pad((string) $client->id, 6, '0', STR_PAD_LEFT).'-'
                                .str_pad((string) $invoiceSeq, 4, '0', STR_PAD_LEFT),
                            'issue_date' => $issueDate->format('Y-m-d'),
                            'due_date' => $dueDate->format('Y-m-d'),
                            'status' => $status,
                            'currency' => $currency,
                            'subtotal' => 0,
                            'tax_total' => 0,
                            'total' => 0,
                            'notes' => fake()->optional(0.3)->sentence(),
                            'sent_at' => $sentAt,
                            'paid_at' => $paidAt,
                        ]);

                        $totalInvoices++;

                        $itemNames = [
                            'Servicio de consultoría',
                            'Desarrollo de software',
                            'Mantención mensual',
                            'Licencia anual',
                            'Hosting dedicado',
                            'Soporte técnico 24/7',
                            'Capacitación presencial',
                            'Diseño UX/UI',
                            'Campaña publicitaria',
                            'Servicio cloud',
                            'Certificación SSL',
                            'Migración de datos',
                            'Consultoría SEO',
                            'Desarrollo móvil',
                            'Seguridad informática',
                        ];

                        $itemCount = rand(1, 5);
                        $itemsSubtotal = 0;

                        for ($j = 0; $j < $itemCount; $j++) {
                            $qty = fake()->randomFloat(2, 1, 20);
                            $unitPrice = fake()->randomFloat(2, 5000, 500000);
                            $itemTotal = round($qty * $unitPrice, 2);
                            $itemsSubtotal += $itemTotal;

                            InvoiceItem::create([
                                'tenant_id' => $tenant->id,
                                'invoice_id' => $invoice->id,
                                'description' => fake()->randomElement($itemNames).' - '.fake()->word(),
                                'quantity' => $qty,
                                'unit_price' => $unitPrice,
                                'total' => $itemTotal,
                                'sort_order' => $j + 1,
                            ]);

                            $totalItems++;
                        }

                        $tax = round($itemsSubtotal * 0.19, 2);
                        $total = $itemsSubtotal + $tax;

                        $invoice->updateQuietly([
                            'subtotal' => $itemsSubtotal,
                            'tax_total' => $tax,
                            'total' => $total,
                        ]);

                        if (in_array($status, ['paid', 'sent'], true)
                            && fake()->boolean($status === 'paid' ? 90 : 20)
                        ) {
                            $this->createPaymentForInvoice($tenant, $invoice);
                            $totalPayments++;
                        }
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                throw $e;
            }

            $bar->advance();
        }

        $bar->finish();

        $this->command->newLine(2);
        $this->command->info('✅ Simulation data generated:');
        $this->command->info("   Tenants:     {$totalTenants}");
        $this->command->info("   Users:       {$totalUsers}");
        $this->command->info("   Clients:     {$totalClients}");
        $this->command->info("   Invoices:    {$totalInvoices}");
        $this->command->info("   Items:       {$totalItems}");
        $this->command->info("   Payments:    {$totalPayments}");
    }

    private function ensureTenantRoles(Tenant $tenant): void
    {
        $permissions = Permission::all();

        $roleConfig = [
            'owner' => $permissions->pluck('name')->toArray(),
            'admin' => $permissions->pluck('name')->toArray(),
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

        foreach ($roleConfig as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);

            $role->syncPermissions($rolePermissions);
        }
    }

    private function createDemoTenant(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo'],
            [
                'name' => 'Demo Company',
                'plan' => 'enterprise',
                'trial_ends_at' => now()->addDays(30),
            ],
        );

        setPermissionsTeamId($tenant->id);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ],
        )->assignRole('owner');
    }

    private function createUsersForTenant(Tenant $tenant): array
    {
        $count = rand(10, 12);
        $users = [];

        $users[] = tap(User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $tenant->name.' (Owner)',
            'email' => $this->nextEmail(),
        ]))->assignRole('owner');

        $adminCount = rand(1, 2);

        for ($i = 0; $i < $adminCount; $i++) {
            $users[] = tap(User::factory()->create([
                'tenant_id' => $tenant->id,
                'email' => $this->nextEmail(),
            ]))->assignRole('admin');
        }

        $billingCount = rand(3, 5);

        for ($i = 0; $i < $billingCount; $i++) {
            $users[] = tap(User::factory()->create([
                'tenant_id' => $tenant->id,
                'email' => $this->nextEmail(),
            ]))->assignRole('billing');
        }

        $viewerCount = $count - 1 - $adminCount - $billingCount;

        for ($i = 0; $i < $viewerCount; $i++) {
            $users[] = tap(User::factory()->create([
                'tenant_id' => $tenant->id,
                'email' => $this->nextEmail(),
            ]))->assignRole('viewer');
        }

        return $users;
    }

    private function nextEmail(): string
    {
        $this->userCounter++;

        return 'user.'.$this->userCounter.'@example.com';
    }

    private function createPaymentForInvoice(Tenant $tenant, Invoice $invoice): void
    {
        $provider = fake()->randomElement(['mercadopago', 'mercadopago', 'paypal', 'manual']);

        $data = [
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'provider' => $provider,
            'amount' => $invoice->total,
            'status' => $invoice->status === 'paid' ? 'paid' : 'pending',
            'paid_at' => $invoice->paid_at,
            'raw_payload' => [],
        ];

        if ($provider === 'mercadopago') {
            $data['provider_payment_id'] = 'MP_'.strtoupper(fake()->bothify('????????????'));
        } elseif ($provider === 'paypal') {
            $data['provider_payment_id'] = 'PP_'.strtoupper(fake()->bothify('????????????'));
            $data['paypal_order_id'] = fake()->uuid();
            $data['paypal_payer_id'] = 'PAYERID_'.fake()->bothify('????????');
        }

        Payment::create($data);
    }

    private function weightedRandom(array $weights): string
    {
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($weights as $value => $weight) {
            $cumulative += $weight;

            if ($rand <= $cumulative) {
                return $value;
            }
        }

        return (string) array_key_first($weights);
    }
}
