<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Closings\CloseCashPeriod;
use App\Enums\ClosingPeriod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCashClosingRequest;
use App\Models\CashClosing;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashClosingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'barber_id' => ['nullable', 'string'],
            'period' => ['nullable', 'string'],
        ]);

        $closings = CashClosing::query()
            ->with(['barber:id,name', 'closedBy:id,name'])
            ->when(($filters['barber_id'] ?? null) === 'general', fn (Builder $query) => $query->whereNull('barber_id'))
            ->when(is_numeric($filters['barber_id'] ?? null), fn (Builder $query) => $query->where('barber_id', $filters['barber_id']))
            ->when(ClosingPeriod::tryFrom($filters['period'] ?? ''), fn (Builder $query, ClosingPeriod $period) => $query->where('period_type', $period))
            ->latest('period_start')
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (CashClosing $closing) => $this->present($closing));

        return Inertia::render('admin/closings/Index', [
            'closings' => $closings,
            'filters' => [
                'barber_id' => $filters['barber_id'] ?? null,
                'period' => $filters['period'] ?? null,
            ],
            'barbers' => User::withTrashed()->barbers()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreCashClosingRequest $request, CloseCashPeriod $closeCashPeriod): RedirectResponse
    {
        $closing = $closeCashPeriod->handle(
            $request->user(),
            ClosingPeriod::from($request->validated('period')),
            CarbonImmutable::parse($request->validated('date')),
            $request->filled('barber_id') ? User::withTrashed()->barbers()->whereKey($request->integer('barber_id'))->firstOrFail() : null,
            $request->validated('notes'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Periodo cerrado correctamente.')]);

        return to_route('admin.closings.show', $closing);
    }

    public function show(CashClosing $closing): Response
    {
        $closing->load(['barber:id,name', 'closedBy:id,name', 'lines']);

        return Inertia::render('admin/closings/Show', [
            'closing' => [
                ...$this->present($closing),
                'notes' => $closing->notes,
                'by_payment_method' => $closing->lines->whereNull('barber_id')->values(),
                'by_barber' => $closing->lines->whereNotNull('barber_id')->values(),
            ],
        ]);
    }

    public function markPaid(CashClosing $closing): RedirectResponse
    {
        abort_if($closing->isGeneral(), 422);

        $closing->update(['paid_at' => $closing->paid_at ? null : now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $closing->paid_at ? __('Liquidación marcada como pagada.') : __('Liquidación marcada como pendiente.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(CashClosing $closing): array
    {
        return [
            'id' => $closing->id,
            'period_type' => $closing->period_type->value,
            'period_label' => $closing->period_type->label(),
            'period_start' => $closing->period_start->toDateString(),
            'period_end' => $closing->period_end->toDateString(),
            'barber_id' => $closing->barber_id,
            'barber_name' => $closing->barber?->name,
            'closed_by_name' => $closing->closedBy->name,
            'services_count' => $closing->services_count,
            'gross_total' => $closing->gross_total,
            'tips_total' => $closing->tips_total,
            'atm_fee_total' => $closing->atm_fee_total,
            'barber_total' => $closing->barber_total,
            'shop_total' => $closing->shop_total,
            'payout_total' => $closing->payout_total,
            'paid_at' => $closing->paid_at?->toIso8601String(),
            'created_at' => $closing->created_at?->toIso8601String(),
        ];
    }
}
