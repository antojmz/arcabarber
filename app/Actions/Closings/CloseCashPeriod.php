<?php

namespace App\Actions\Closings;

use App\Enums\ClosingPeriod;
use App\Models\CashClosing;
use App\Models\User;
use App\Services\ReportService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseCashPeriod
{
    public function __construct(private ReportService $reports) {}

    /**
     * Close a period for one barber (payroll) or for the whole shop, storing a
     * snapshot of the totals. Transactions in a closed period become read-only.
     *
     * @throws ValidationException
     */
    public function handle(User $admin, ClosingPeriod $period, CarbonInterface $date, ?User $barber = null, ?string $notes = null): CashClosing
    {
        [$start, $end] = $period->range($date);

        return DB::transaction(function () use ($admin, $period, $start, $end, $barber, $notes) {
            $alreadyClosed = CashClosing::query()
                ->where('period_type', $period)
                ->whereDate('period_start', $start->toDateString())
                ->where('barber_id', $barber?->id)
                ->lockForUpdate()
                ->exists();

            if ($alreadyClosed) {
                throw ValidationException::withMessages([
                    'date' => __('Este periodo ya fue cerrado.'),
                ]);
            }

            $summary = $this->reports->summary($start, $end, $barber?->id);

            $closing = CashClosing::create([
                'period_type' => $period,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'barber_id' => $barber?->id,
                'closed_by' => $admin->id,
                'notes' => $notes,
                ...$summary['totals'],
            ]);

            foreach ($summary['by_payment_method'] as $line) {
                $closing->lines()->create($line);
            }

            foreach ($summary['by_barber'] as $line) {
                $closing->lines()->create($line);
            }

            return $closing;
        });
    }
}
