<?php

namespace App\Actions\Transactions;

use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Support\RevenueSplit;
use Illuminate\Support\Facades\DB;

class RegisterTransaction
{
    public function __construct(private EnsurePeriodIsOpen $ensurePeriodIsOpen) {}

    /**
     * Register a service performed by a barber.
     *
     * Barbers can only register services for themselves, at the service base
     * price and at the current time; only admins can pick the barber, change
     * the amount or set a different date.
     *
     * @param  array{barber_id: int|null, service_id: int, payment_method_id: int, amount: int|null, tip: int, notes: string|null, performed_at: string|null}  $data
     */
    public function handle(User $actor, array $data): Transaction
    {
        $service = Service::findOrFail($data['service_id']);
        $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);

        if ($actor->isAdmin()) {
            $barber = User::barbers()->whereKey($data['barber_id'])->firstOrFail();
            $amount = $data['amount'] ?? $service->base_price;
            $performedAt = $data['performed_at'] !== null ? now()->parse($data['performed_at']) : now();
        } else {
            $barber = $actor;
            $amount = $service->base_price;
            $performedAt = now();
        }

        $this->ensurePeriodIsOpen->handle($barber->id, $performedAt);

        $split = RevenueSplit::calculate($amount, $paymentMethod->atm_fee_rate, $barber->commission_rate);

        return DB::transaction(fn () => Transaction::create([
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'payment_method_id' => $paymentMethod->id,
            'registered_by' => $actor->id,
            'performed_at' => $performedAt,
            'service_name' => $service->name,
            'service_base_price' => $service->base_price,
            'payment_method_name' => $paymentMethod->name,
            'atm_fee_rate' => $paymentMethod->atm_fee_rate,
            'barber_commission_rate' => $barber->commission_rate,
            'amount' => $amount,
            'tip' => $data['tip'],
            'atm_fee_amount' => $split->atmFee,
            'barber_amount' => $split->barber,
            'shop_amount' => $split->shop,
            'notes' => $data['notes'],
        ]));
    }
}
