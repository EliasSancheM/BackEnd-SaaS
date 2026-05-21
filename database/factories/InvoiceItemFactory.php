<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
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
            'invoice_id' => Invoice::factory(),
            'description' => fake()->sentence(),
            'quantity' => fake()->randomFloat(2, 1, 10),
            'unit_price' => fake()->randomFloat(2, 1000, 100000),
            'total' => fake()->randomFloat(2, 1000, 500000),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
