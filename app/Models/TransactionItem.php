<?php

namespace App\Models;

use Database\Factories\TransactionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A service performed within a transaction, with the catalog snapshot and
 * its share of the transaction split.
 *
 * @property int $id
 * @property int $transaction_id
 * @property int $service_id
 * @property string $service_name
 * @property int $service_base_price
 * @property int $amount
 * @property int $iva_amount
 * @property int $barber_amount
 * @property int $shop_amount
 */
#[Fillable([
    'transaction_id', 'service_id', 'service_name', 'service_base_price',
    'amount', 'iva_amount', 'barber_amount', 'shop_amount',
])]
class TransactionItem extends Model
{
    /** @use HasFactory<TransactionItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_base_price' => 'integer',
            'amount' => 'integer',
            'iva_amount' => 'integer',
            'barber_amount' => 'integer',
            'shop_amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }
}
