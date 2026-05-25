<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provider = fake()->randomElement(['mercadopago', 'paypal']);

        return [
            'tenant_id' => Tenant::factory(),
            'invoice_id' => Invoice::factory(),
            'provider' => $provider,
            'provider_payment_id' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            'paypal_order_id' => $provider === 'paypal' ? fake()->uuid() : null,
            'paypal_payer_id' => $provider === 'paypal' ? 'PAYERID'.fake()->unique()->numberBetween(1000000, 9999999) : null,
            'amount' => fake()->randomFloat(2, 1000, 100000),
            'status' => 'pending',
            'paid_at' => null,
            'raw_payload' => [],
        ];
    }
}
