<?php

namespace App\Models;

use App\Enums\ClosingPeriod;
use Carbon\CarbonImmutable;
use Database\Factories\CashClosingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property ClosingPeriod $period_type
 * @property CarbonImmutable $period_start
 * @property CarbonImmutable $period_end
 * @property int|null $barber_id
 * @property int $closed_by
 * @property int $services_count
 * @property int $gross_total
 * @property int $tips_total
 * @property int $atm_fee_total
 * @property int $barber_total
 * @property int $shop_total
 * @property int $payout_total
 * @property string|null $notes
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $created_at
 */
#[Fillable([
    'period_type', 'period_start', 'period_end', 'barber_id', 'closed_by',
    'services_count', 'gross_total', 'tips_total', 'atm_fee_total', 'barber_total', 'shop_total', 'payout_total',
    'notes', 'paid_at',
])]
class CashClosing extends Model
{
    /** @use HasFactory<CashClosingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_type' => ClosingPeriod::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'paid_at' => 'datetime',
            'services_count' => 'integer',
            'gross_total' => 'integer',
            'tips_total' => 'integer',
            'atm_fee_total' => 'integer',
            'barber_total' => 'integer',
            'shop_total' => 'integer',
            'payout_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function barber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'barber_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by')->withTrashed();
    }

    /**
     * @return HasMany<CashClosingLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(CashClosingLine::class);
    }

    public function isGeneral(): bool
    {
        return $this->barber_id === null;
    }
}
