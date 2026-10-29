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
            'payment_method_id' => PaymentMethod::factory(),
            'registered_by' => fn (array $attributes) => $attributes['barber_id'],
            'performed_at' => now(),
            'payment_method_name' => fn (array $attributes) => PaymentMethod::whereKey($attributes['payment_method_id'])->firstOrFail()->name,
            'iva_rate' => fn (array $attributes) => PaymentMethod::whereKey($attributes['payment_method_id'])->firstOrFail()->iva_rate,
            'barber_commission_rate' => fn (array $attributes) => User::whereKey($attributes['barber_id'])->firstOrFail()->commission_rate,
            'amount' => fake()->numberBetween(5, 30) * 1000,
            'tip' => 0,
            'iva_amount' => fn (array $attributes) => $this->split($attributes)->iva,
            'barber_amount' => fn (array $attributes) => $this->split($attributes)->barber,
            'shop_amount' => fn (array $attributes) => $this->split($attributes)->shop,
        ];
    }

    /**
     * Include the given services at their base price, replacing the amounts
     * of the transaction with the sum of its items.
     */
    public function withServices(Service ...$services): static
    {
        return $this->afterCreating(function (Transaction $transaction) use ($services): void {
            foreach ($services as $service) {
                $split = RevenueSplit::calculate($service->base_price, $transaction->iva_rate, $transaction->barber_commission_rate);

                $transaction->items()->create([
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'service_base_price' => $service->base_price,
                    'amount' => $service->base_price,
                    'iva_amount' => $split->iva,
                    'barber_amount' => $split->barber,
                    'shop_amount' => $split->shop,
                ]);
            }

            $transaction->update([
                'amount' => $transaction->items()->sum('amount'),
                'iva_amount' => $transaction->items()->sum('iva_amount'),
                'barber_amount' => $transaction->items()->sum('barber_amount'),
                'shop_amount' => $transaction->items()->sum('shop_amount'),
            ]);
        });
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
