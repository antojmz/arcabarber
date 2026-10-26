<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Admins see every transaction; barbers only their own.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->isAdmin() || $transaction->barber_id === $user->id;
    }

    /**
     * Only admins can change a transaction, and only while its period is still open.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        return $user->isAdmin() && ! $transaction->isVoided() && ! $transaction->isLocked();
    }

    /**
     * Voiding follows the same rules as updating.
     */
    public function void(User $user, Transaction $transaction): bool
    {
        return $this->update($user, $transaction);
    }
}
