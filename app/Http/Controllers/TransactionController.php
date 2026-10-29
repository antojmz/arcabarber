<?php

namespace App\Http\Controllers;

use App\Actions\Transactions\RegisterTransaction;
use App\Actions\Transactions\UpdateTransaction;
use App\Actions\Transactions\VoidTransaction;
use App\Http\Requests\Transactions\StoreTransactionRequest;
use App\Http\Requests\Transactions\UpdateTransactionRequest;
use App\Http\Requests\Transactions\VoidTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Support\ClosedPeriods;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * List transactions. Barbers only ever see their own.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'barber_id' => ['nullable', 'integer'],
            'payment_method_id' => ['nullable', 'integer'],
        ]);

        $from = CarbonImmutable::parse($filters['from'] ?? 'today')->startOfDay();
        $to = CarbonImmutable::parse($filters['to'] ?? $from)->endOfDay();

        $query = Transaction::query()
            ->performedBetween($from, $to)
            ->when(! $user->isAdmin(), fn (Builder $query) => $query->where('barber_id', $user->id))
            ->when($user->isAdmin() && ! empty($filters['barber_id']), fn (Builder $query) => $query->where('barber_id', $filters['barber_id']))
            ->when(! empty($filters['payment_method_id']), fn (Builder $query) => $query->where('payment_method_id', $filters['payment_method_id']));

        $totals = (clone $query)->valid()->toBase()->selectRaw(
            'COUNT(*) as services_count, COALESCE(SUM(amount), 0) as gross_total, COALESCE(SUM(tip), 0) as tips_total, '
            .'COALESCE(SUM(iva_amount), 0) as iva_total, COALESCE(SUM(barber_amount), 0) as barber_total, COALESCE(SUM(shop_amount), 0) as shop_total',
        )->first();

        $closedPeriods = ClosedPeriods::between($from, $to);

        $transactions = $query
            ->with(['barber:id,name', 'items:id,transaction_id,service_id,service_name,service_base_price,amount'])
            ->latest('performed_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Transaction $transaction) => (new TransactionResource($transaction))
                ->withLock($closedPeriods->covers($transaction))
                ->resolve());

        return Inertia::render('transactions/Index', [
            'transactions' => $transactions,
            'totals' => array_map('intval', (array) $totals),
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'barber_id' => $user->isAdmin() ? ($filters['barber_id'] ?? null) : null,
                'payment_method_id' => $filters['payment_method_id'] ?? null,
            ],
            'barbers' => $user->isAdmin() ? $this->barberOptions() : [],
            'paymentMethods' => PaymentMethod::withTrashed()->ordered()->get(['id', 'name']),
        ]);
    }

    /**
     * Show the point of sale form.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('transactions/Register', [
            ...$this->catalogs($request->user()),
        ]);
    }

    /**
     * Register a new transaction.
     */
    public function store(StoreTransactionRequest $request, RegisterTransaction $registerTransaction): RedirectResponse
    {
        $transaction = $registerTransaction->handle($request->user(), $request->transactionData());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice('{1} Servicio registrado: :services para :barber.|[2,*] Servicios registrados: :services para :barber.', $transaction->items->count(), [
                'services' => $transaction->items->pluck('service_name')->join(', ', ' y '),
                'barber' => $transaction->barber->name,
            ]),
        ]);

        return to_route('transactions.create');
    }

    /**
     * Show the form to correct a transaction.
     */
    public function edit(Request $request, Transaction $transaction): Response
    {
        Gate::authorize('update', $transaction);

        return Inertia::render('transactions/Edit', [
            'transaction' => (new TransactionResource($transaction->load(['barber:id,name', 'items'])))->resolve(),
            ...$this->catalogs($request->user(), $transaction),
        ]);
    }

    /**
     * Correct a transaction.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction, UpdateTransaction $updateTransaction): RedirectResponse
    {
        Gate::authorize('update', $transaction);

        $updateTransaction->handle($transaction, $request->transactionData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transacción actualizada.')]);

        return to_route('transactions.index', ['from' => $transaction->performed_at->toDateString()]);
    }

    /**
     * Void a transaction instead of deleting it.
     */
    public function void(VoidTransactionRequest $request, Transaction $transaction, VoidTransaction $voidTransaction): RedirectResponse
    {
        Gate::authorize('void', $transaction);

        $voidTransaction->handle($transaction, $request->user(), $request->validated('void_reason'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transacción anulada.')]);

        return back();
    }

    /**
     * Catalogs needed by the point of sale form. When editing, the items used by
     * the transaction are included even if they were deactivated since.
     *
     * @return array<string, mixed>
     */
    private function catalogs(User $user, ?Transaction $transaction = null): array
    {
        return [
            'services' => Service::withTrashed()
                ->where($this->activeOr($transaction?->items->pluck('service_id')->all() ?? []))
                ->ordered()
                ->get(['id', 'name', 'base_price']),
            'paymentMethods' => PaymentMethod::withTrashed()
                ->where($this->activeOr(array_filter([$transaction?->payment_method_id])))
                ->ordered()
                ->get(['id', 'name', 'iva_rate']),
            'barbers' => $user->isAdmin() ? $this->barberOptions($transaction?->barber_id) : [],
            'currentBarber' => $user->isAdmin() ? null : $user->only('id', 'name', 'commission_rate'),
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private function barberOptions(?int $includeId = null): Collection
    {
        return User::withTrashed()
            ->barbers()
            ->where($this->activeOr(array_filter([$includeId])))
            ->orderBy('name')
            ->get(['id', 'name', 'commission_rate']);
    }

    /**
     * Constrain a catalog query to active, non deleted rows, plus the given ids.
     *
     * @param  list<int>  $ids
     * @return Closure(Builder<*>): void
     */
    private function activeOr(array $ids): Closure
    {
        return function (Builder $query) use ($ids): void {
            $query
                ->where(fn (Builder $query) => $query->where('is_active', true)->whereNull('deleted_at'))
                ->when($ids !== [], fn (Builder $query) => $query->orWhereIn('id', $ids));
        };
    }
}
