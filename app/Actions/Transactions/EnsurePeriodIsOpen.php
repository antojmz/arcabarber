<?php

namespace App\Actions\Transactions;

use App\Models\CashClosing;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class EnsurePeriodIsOpen
{
    /**
     * Reject writes on a date that is already covered by a cash closing
     * for the whole shop or for the given barber.
     *
     * @throws ValidationException
     */
    public function handle(int $barberId, CarbonInterface $date): void
    {
        $day = $date->toDateString();

        $isClosed = CashClosing::query()
            ->whereDate('period_start', '<=', $day)
            ->whereDate('period_end', '>=', $day)
            ->where(fn (Builder $query) => $query->whereNull('barber_id')->orWhere('barber_id', $barberId))
            ->exists();

        if ($isClosed) {
            throw ValidationException::withMessages([
                'performed_at' => __('El periodo de esta fecha ya está cerrado.'),
            ]);
        }
    }
}
