<?php

namespace App\Support;

use App\Models\CashClosing;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Resolves in a single query which transactions of a date range fall inside a closed period.
 */
final class ClosedPeriods
{
    /**
     * @param  Collection<int, CashClosing>  $closings
     */
    private function __construct(private Collection $closings) {}

    public static function between(CarbonInterface $start, CarbonInterface $end): self
    {
        return new self(CashClosing::query()
            ->whereDate('period_start', '<=', $end->toDateString())
            ->whereDate('period_end', '>=', $start->toDateString())
            ->get(['period_start', 'period_end', 'barber_id']));
    }

    public function covers(Transaction $transaction): bool
    {
        $day = $transaction->performed_at->toDateString();

        return $this->closings->contains(fn (CashClosing $closing) => $closing->period_start->toDateString() <= $day
            && $closing->period_end->toDateString() >= $day
            && ($closing->barber_id === null || $closing->barber_id === $transaction->barber_id));
    }
}
