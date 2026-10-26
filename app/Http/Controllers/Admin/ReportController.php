<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Models\CashClosing;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\ReportService;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    /**
     * Payroll report of a single barber for a period.
     */
    public function barber(ReportRequest $request): Response
    {
        $period = $request->period();
        [$start, $end] = $period->range($request->referenceDate());

        $barber = $request->filled('barber_id')
            ? User::withTrashed()->barbers()->whereKey($request->integer('barber_id'))->first()
            : null;

        return Inertia::render('admin/reports/Barber', [
            'filters' => [
                'period' => $period->value,
                'date' => $request->referenceDate()->toDateString(),
                'barber_id' => $barber?->id,
            ],
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'barbers' => User::withTrashed()->barbers()->orderBy('name')->get(['id', 'name', 'commission_rate', 'is_active']),
            'paymentMethods' => PaymentMethod::withTrashed()->ordered()->get(['id', 'name']),
            'barber' => $barber?->only('id', 'name', 'commission_rate'),
            'summary' => $barber ? $this->reports->summary($start, $end, $barber->id) : null,
            'serviceCounts' => $barber ? $this->reports->serviceCountsByPaymentMethod($start, $end, $barber->id) : [],
            'closing' => $barber ? $this->findClosing($period->value, $start->toDateString(), $barber->id) : null,
        ]);
    }

    /**
     * Consolidated report of the whole shop for a period.
     */
    public function general(ReportRequest $request): Response
    {
        $period = $request->period();
        [$start, $end] = $period->range($request->referenceDate());

        return Inertia::render('admin/reports/General', [
            'filters' => [
                'period' => $period->value,
                'date' => $request->referenceDate()->toDateString(),
            ],
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'summary' => $this->reports->summary($start, $end),
            'closing' => $this->findClosing($period->value, $start->toDateString(), null),
        ]);
    }

    /**
     * @return array{id: int, created_at: string|null}|null
     */
    private function findClosing(string $period, string $start, ?int $barberId): ?array
    {
        return CashClosing::query()
            ->where('period_type', $period)
            ->whereDate('period_start', $start)
            ->where('barber_id', $barberId)
            ->first(['id', 'created_at'])
            ?->only('id', 'created_at');
    }
}
