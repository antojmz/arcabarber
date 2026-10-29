<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * Whether the transaction falls inside a closed period. Resolved by the
     * caller in bulk to avoid one query per row.
     */
    public bool $locked = false;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'performed_at' => $this->performed_at->toIso8601String(),
            'barber_id' => $this->barber_id,
            'barber_name' => $this->whenLoaded('barber', fn () => $this->barber->name),
            'service_id' => $this->service_id,
            'service_name' => $this->service_name,
            'service_base_price' => $this->service_base_price,
            'payment_method_id' => $this->payment_method_id,
            'payment_method_name' => $this->payment_method_name,
            'iva_rate' => (float) $this->iva_rate,
            'barber_commission_rate' => (float) $this->barber_commission_rate,
            'amount' => $this->amount,
            'tip' => $this->tip,
            'iva_amount' => $this->iva_amount,
            'barber_amount' => $this->barber_amount,
            'shop_amount' => $this->shop_amount,
            'notes' => $this->notes,
            'voided' => $this->isVoided(),
            'void_reason' => $this->void_reason,
            'locked' => $this->locked,
            'can_update' => $request->user()->isAdmin() && ! $this->isVoided() && ! $this->locked,
        ];
    }

    public function withLock(bool $locked): static
    {
        $this->locked = $locked;

        return $this;
    }
}
