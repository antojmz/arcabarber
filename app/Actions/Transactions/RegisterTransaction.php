<?php

namespace App\Actions\Transactions;

use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;

class RegisterTransaction
{
    public function __construct(
        private EnsurePeriodIsOpen $ensurePeriodIsOpen,
        private SaveTransactionWithItems $saveTransactionWithItems,
    ) {}

    /**
     * Register a visit of a client: one transaction with every service the
     * barber performed, charged together with a single tip.
     *
     * Barbers can only register services for themselves, at the service base
     * price and at the current time; only admins can pick the barber, change
     * the amounts or set a different date.
     *
     * @param  array{barber_id: int|null, services: list<array{service_id: int, amount: int|null}>, payment_method_id: int, tip: int, notes: string|null, performed_at: string|null}  $data
     */
    public function handle(User $actor, array $data): Transaction
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

        $transaction = new Transaction([
            'barber_id' => $barber->id,
            'payment_method_id' => $paymentMethod->id,
            'registered_by' => $actor->id,
            'performed_at' => $performedAt,
            'payment_method_name' => $paymentMethod->name,
            'iva_rate' => $paymentMethod->iva_rate,
            'barber_commission_rate' => $barber->commission_rate,
            'tip' => $data['tip'],
            'notes' => $data['notes'],
        ]);

        return $this->saveTransactionWithItems->handle($transaction, array_map(function (array $line) use ($actor, $services): array {
            $service = $services->get($line['service_id']);

            return [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'service_base_price' => $service->base_price,
                'amount' => $actor->isAdmin() ? ($line['amount'] ?? $service->base_price) : $service->base_price,
            ];
        }, $data['services']));
    }
}
