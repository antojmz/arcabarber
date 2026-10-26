<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\PaymentMethod;
use App\Services\ReportService;
use Inertia\Inertia;
use Inertia\Response;

class MyProductionController extends Controller
{
    /**
     * Live payroll of the authenticated barber. It never accepts a barber id
     * from the request, so a barber can only see their own figures.
     */
    public function __invoke(ReportRequest $request, ReportService $reports): Response
    {
        $user = $request->user();
        $period = $request->period();
        [$start, $end] = $period->range($request->referenceDate());

        return Inertia::render('my/Production', [
            'filters' => [
                'period' => $period->value,
                'date' => $request->referenceDate()->toDateString(),
            ],
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'barber' => $user->only('id', 'name', 'commission_rate'),
            'summary' => $reports->summary($start, $end, $user->id),
            'serviceCounts' => $reports->serviceCountsByPaymentMethod($start, $end, $user->id),
            'paymentMethods' => PaymentMethod::withTrashed()->ordered()->get(['id', 'name']),
            'closings' => $user->cashClosings()
                ->latest('period_start')
                ->limit(12)
                ->get()
                ->map(fn ($closing) => [
                    'id' => $closing->id,
                    'period_label' => $closing->period_type->label(),
                    'period_start' => $closing->period_start->toDateString(),
                    'period_end' => $closing->period_end->toDateString(),
                    'services_count' => $closing->services_count,
                    'payout_total' => $closing->payout_total,
                    'paid_at' => $closing->paid_at?->toIso8601String(),
                ]),
        ]);
    }
}
