<?php

namespace App\Http\Controllers;

use App\Actions\Transactions\RegisterTransaction;
use App\Http\Requests\PublicServiceRequest;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public reception screen: shows today's activity without any amounts and lets
 * anyone register a service for a barber who confirms it with their password.
 */
class PublicDashboardController extends Controller
{
    /**
     * Failed password attempts allowed per barber and device before locking.
     */
    private const int MAX_PASSWORD_ATTEMPTS = 5;

    public function show(): Response
    {
        return Inertia::render('public/Dashboard', [
            'barbers' => User::barbers()->active()->orderBy('name')->get(['id', 'name']),
            'services' => Service::active()->ordered()->get(['id', 'name', 'base_price']),
            'paymentMethods' => PaymentMethod::active()->ordered()->get(['id', 'name']),
            'today' => $this->todayActivity(),
        ]);
    }

    public function store(PublicServiceRequest $request, RegisterTransaction $registerTransaction): RedirectResponse
    {
        $throttleKey = $request->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_PASSWORD_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'password' => __('Demasiados intentos. Intenta de nuevo en :seconds segundos.', [
                    'seconds' => RateLimiter::availableIn($throttleKey),
                ]),
            ]);
        }

        $barber = User::barbers()->active()->whereKey($request->integer('barber_id'))->firstOrFail();

        if (! Hash::check($request->string('password')->toString(), $barber->password)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'password' => __('La contraseña no es correcta.'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        $transaction = $registerTransaction->handle($barber, [
            'barber_id' => null,
            'services' => array_map(fn (mixed $serviceId) => ['service_id' => (int) $serviceId, 'amount' => null], $request->validated('service_ids')),
            'payment_method_id' => $request->integer('payment_method_id'),
            'tip' => $request->integer('tip'),
            'notes' => null,
            'performed_at' => null,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice('{1} Servicio registrado: :services para :barber.|[2,*] Servicios registrados: :services para :barber.', $transaction->items->count(), [
                'services' => $transaction->items->pluck('service_name')->join(', ', ' y '),
                'barber' => $barber->name,
            ]),
        ]);

        return to_route('public.dashboard');
    }

    /**
     * Today's activity without any monetary field, since every barber can see this screen.
     *
     * @return array{services_count: int, by_barber: list<array{barber_id: int, services_count: int}>, transactions: list<array{id: int, performed_at: string, barber_name: string, service_names: list<string>, payment_method_name: string}>}
     */
    private function todayActivity(): array
    {
        $today = CarbonImmutable::today();

        $transactions = Transaction::query()
            ->valid()
            ->performedBetween($today->startOfDay(), $today->endOfDay())
            ->with(['barber:id,name', 'items:id,transaction_id,service_name'])
            ->latest('created_at')
            ->latest('id')
            ->get(['id', 'barber_id', 'performed_at', 'created_at', 'payment_method_name']);

        return [
            'services_count' => $transactions->count(),
            'by_barber' => array_values($transactions
                ->countBy('barber_id')
                ->map(fn (int $count, int $barberId) => ['barber_id' => $barberId, 'services_count' => $count])
                ->all()),
            'transactions' => array_values($transactions
                ->map(fn (Transaction $transaction) => [
                    'id' => $transaction->id,
                    'performed_at' => $transaction->performed_at->toIso8601String(),
                    'barber_name' => $transaction->barber->name,
                    'service_names' => $transaction->items->pluck('service_name')->all(),
                    'payment_method_name' => $transaction->payment_method_name,
                ])
                ->all()),
        ];
    }
}
