<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $cash_closing_id
 * @property int|null $payment_method_id
 * @property string|null $payment_method_name
 * @property int|null $barber_id
 * @property string|null $barber_name
 * @property int $services_count
 * @property int $gross_total
 * @property int $tips_total
 * @property int $atm_fee_total
 * @property int $barber_total
 * @property int $shop_total
 */
#[Fillable([
    'cash_closing_id', 'payment_method_id', 'payment_method_name', 'barber_id', 'barber_name',
    'services_count', 'gross_total', 'tips_total', 'atm_fee_total', 'barber_total', 'shop_total',
])]
class CashClosingLine extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'services_count' => 'integer',
            'gross_total' => 'integer',
            'tips_total' => 'integer',
            'atm_fee_total' => 'integer',
            'barber_total' => 'integer',
            'shop_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CashClosing, $this>
     */
    public function cashClosing(): BelongsTo
    {
        return $this->belongsTo(CashClosing::class);
    }
}
