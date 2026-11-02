<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()),
            'iva_rate' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the payment method charges the given IVA rate.
     */
    public function withIva(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'iva_rate' => $rate,
        ]);
    }

    /**
     * Indicate that the payment method is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
