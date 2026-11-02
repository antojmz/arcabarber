<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TransactionFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $barber_id
 * @property int $payment_method_id
 * @property int $registered_by
 * @property CarbonImmutable $performed_at
 * @property string $payment_method_name
 * @property string $iva_rate
 * @property string $barber_commission_rate
 * @property int $amount
 * @property int $tip
 * @property int $iva_amount
 * @property int $barber_amount
 * @property int $shop_amount
 * @property string|null $notes
 * @property CarbonImmutable|null $voided_at
 * @property int|null $voided_by
 * @property string|null $void_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, TransactionItem> $items
 */
#[Fillable([
    'barber_id', 'payment_method_id', 'registered_by', 'performed_at',
    'payment_method_name', 'iva_rate', 'barber_commission_rate',
    'amount', 'tip', 'iva_amount', 'barber_amount', 'shop_amount', 'notes',
    'voided_at', 'voided_by', 'void_reason',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
            'voided_at' => 'datetime',
            'iva_rate' => 'decimal:2',
            'barber_commission_rate' => 'decimal:2',
            'amount' => 'integer',
            'tip' => 'integer',
            'iva_amount' => 'integer',
            'barber_amount' => 'integer',
            'shop_amount' => 'integer',
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
     * The services performed in this transaction.
     *
     * @return HasMany<TransactionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class)->orderBy('id');
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by')->withTrashed();
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    /**
     * Determine if the transaction belongs to a period that has already been closed.
     */
    public function isLocked(): bool
    {
        $date = $this->performed_at->toDateString();

        return CashClosing::query()
            ->whereDate('period_start', '<=', $date)
            ->whereDate('period_end', '>=', $date)
            ->where(fn (Builder $query) => $query->whereNull('barber_id')->orWhere('barber_id', $this->barber_id))
            ->exists();
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    #[Scope]
    protected function valid(Builder $query): void
    {
        $query->whereNull('voided_at');
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    #[Scope]
    protected function performedBetween(Builder $query, DateTimeInterface $start, DateTimeInterface $end): void
    {
        $query->whereBetween('performed_at', [$start, $end]);
    }
}
