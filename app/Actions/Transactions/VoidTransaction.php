<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use App\Models\User;

class VoidTransaction
{
    /**
     * Void a transaction. Transactions are never deleted so the history stays auditable.
     */
    public function handle(Transaction $transaction, User $actor, string $reason): Transaction
    {
        $transaction->forceFill([
            'voided_at' => now(),
            'voided_by' => $actor->id,
            'void_reason' => $reason,
        ])->save();

        return $transaction;
    }
}
