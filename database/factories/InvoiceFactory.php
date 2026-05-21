<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'client_id' => Client::factory(),
            'number' => 'F-'.fake()->unique()->numerify('########'),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'currency' => 'CLP',
            'subtotal' => fake()->randomFloat(2, 1000, 100000),
            'tax_total' => fake()->randomFloat(2, 190, 19000),
            'total' => fake()->randomFloat(2, 1190, 119000),
            'notes' => fake()->optional()->sentence(),
            'sent_at' => null,
            'paid_at' => null,
        ];
    }
}
