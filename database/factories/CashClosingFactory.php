<?php

namespace Database\Factories;

use App\Enums\ClosingPeriod;
use App\Models\CashClosing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashClosing>
 */
class CashClosingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'period_type' => ClosingPeriod::Daily,
            'period_start' => today(),
            'period_end' => today(),
            'barber_id' => null,
            'closed_by' => User::factory()->admin(),
            'services_count' => 0,
            'gross_total' => 0,
            'tips_total' => 0,
            'iva_total' => 0,
            'barber_total' => 0,
            'shop_total' => 0,
            'payout_total' => 0,
        ];
    }
}
