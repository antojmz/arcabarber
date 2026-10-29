<?php

namespace App\Actions\Transactions;

use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Support\RevenueSplit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RegisterTransaction
{
    public function __construct(private EnsurePeriodIsOpen $ensurePeriodIsOpen) {}

    /**
     * Register one or more services performed by a barber for the same client.
     * Each service is stored as its own transaction so reports keep counting
     * services; the tip is recorded once, on the first of them.
     *
     * Barbers can only register services for themselves, at the service base
     * price and at the current time; only admins can pick the barber, change
     * the amounts or set a different date.
     *
     * @param  array{barber_id: int|null, services: list<array{service_id: int, amount: int|null}>, payment_method_id: int, tip: int, notes: string|null, performed_at: string|null}  $data
     * @return Collection<int, Transaction>
     */
    public function handle(User $actor, array $data): Collection
    {
        $services = Service::findOrFail(array_column($data['services'], 'service_id'))->keyBy('id');
        $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);

        if ($actor->isAdmin()) {
            $barber = User::barbers()->whereKey($data['barber_id'])->firstOrFail();
            $performedAt = $data['performed_at'] !== null ? now()->parse($data['performed_at']) : now();
        } else {
            $barber = $actor;
            $performedAt = now();
        }

        $this->ensurePeriodIsOpen->handle($barber->id, $performedAt);

        return DB::transaction(fn () => collect($data['services'])->values()->map(function (array $item, int $index) use ($actor, $barber, $data, $paymentMethod, $performedAt, $services) {
            $service = $services->get($item['service_id']);
            $amount = $actor->isAdmin() ? ($item['amount'] ?? $service->base_price) : $service->base_price;
            $split = RevenueSplit::calculate($amount, $paymentMethod->iva_rate, $barber->commission_rate);

            return Transaction::create([
                'barber_id' => $barber->id,
                'service_id' => $service->id,
                'payment_method_id' => $paymentMethod->id,
                'registered_by' => $actor->id,
                'performed_at' => $performedAt,
                'service_name' => $service->name,
                'service_base_price' => $service->base_price,
                'payment_method_name' => $paymentMethod->name,
                'iva_rate' => $paymentMethod->iva_rate,
                'barber_commission_rate' => $barber->commission_rate,
                'amount' => $amount,
                'tip' => $index === 0 ? $data['tip'] : 0,
                'iva_amount' => $split->iva,
                'barber_amount' => $split->barber,
                'shop_amount' => $split->shop,
                'notes' => $data['notes'],
            ]);
        }));
    }
}
