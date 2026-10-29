<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionItem>
 */
class TransactionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'service_id' => Service::factory(),
            'service_name' => fn (array $attributes) => Service::whereKey($attributes['service_id'])->firstOrFail()->name,
            'service_base_price' => fn (array $attributes) => Service::whereKey($attributes['service_id'])->firstOrFail()->base_price,
            'amount' => fn (array $attributes) => $attributes['service_base_price'],
            'iva_amount' => 0,
            'barber_amount' => fn (array $attributes) => intdiv($attributes['amount'], 2),
            'shop_amount' => fn (array $attributes) => $attributes['amount'] - intdiv($attributes['amount'], 2),
        ];
    }
}
