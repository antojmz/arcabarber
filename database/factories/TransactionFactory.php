<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Support\RevenueSplit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'barber_id' => User::factory(),
            'service_id' => Service::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'registered_by' => fn (array $attributes) => $attributes['barber_id'],
            'performed_at' => now(),
            'service_name' => fn (array $attributes) => Service::whereKey($attributes['service_id'])->firstOrFail()->name,
            'service_base_price' => fn (array $attributes) => Service::whereKey($attributes['service_id'])->firstOrFail()->base_price,
            'payment_method_name' => fn (array $attributes) => PaymentMethod::whereKey($attributes['payment_method_id'])->firstOrFail()->name,
            'iva_rate' => fn (array $attributes) => PaymentMethod::whereKey($attributes['payment_method_id'])->firstOrFail()->iva_rate,
            'barber_commission_rate' => fn (array $attributes) => User::whereKey($attributes['barber_id'])->firstOrFail()->commission_rate,
            'amount' => fn (array $attributes) => $attributes['service_base_price'],
            'tip' => 0,
            'iva_amount' => fn (array $attributes) => $this->split($attributes)->iva,
            'barber_amount' => fn (array $attributes) => $this->split($attributes)->barber,
            'shop_amount' => fn (array $attributes) => $this->split($attributes)->shop,
        ];
    }

    /**
     * Indicate that the transaction has been voided.
     */
    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'voided_at' => now(),
            'voided_by' => $attributes['registered_by'],
            'void_reason' => 'Error de registro',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function split(array $attributes): RevenueSplit
    {
        return RevenueSplit::calculate(
            $attributes['amount'],
            $attributes['iva_rate'],
            $attributes['barber_commission_rate'],
        );
    }
}
