<?php

namespace App\Actions\Transactions;

use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;

class UpdateTransaction
{
    public function __construct(
        private EnsurePeriodIsOpen $ensurePeriodIsOpen,
        private SaveTransactionWithItems $saveTransactionWithItems,
    ) {}

    /**
     * Correct a transaction. The snapshot of a catalog item is only refreshed
     * when that item changes, so editing the tip of an old transaction keeps
     * the rates, names and base prices that were in force when it was registered.
     *
     * @param  array{barber_id: int, services: list<array{service_id: int, amount: int}>, payment_method_id: int, tip: int, notes: string|null, performed_at: string}  $data
     */
    public function handle(Transaction $transaction, array $data): Transaction
    {
        $performedAt = now()->parse($data['performed_at']);

        $this->ensurePeriodIsOpen->handle($data['barber_id'], $performedAt);

        if ($data['payment_method_id'] !== $transaction->payment_method_id) {
            $paymentMethod = PaymentMethod::withTrashed()->findOrFail($data['payment_method_id']);
            $transaction->payment_method_id = $paymentMethod->id;
            $transaction->payment_method_name = $paymentMethod->name;
            $transaction->iva_rate = $paymentMethod->iva_rate;
        }

        if ($data['barber_id'] !== $transaction->barber_id) {
            $barber = User::withTrashed()->findOrFail($data['barber_id']);
            $transaction->barber_id = $barber->id;
            $transaction->barber_commission_rate = $barber->commission_rate;
        }

        $transaction->tip = $data['tip'];
        $transaction->notes = $data['notes'];
        $transaction->performed_at = $performedAt;

        $currentItems = $transaction->items->keyBy('service_id');
        $newServices = Service::withTrashed()
            ->findOrFail(array_values(array_diff(array_column($data['services'], 'service_id'), $currentItems->keys()->all())))
            ->keyBy('id');

        return $this->saveTransactionWithItems->handle($transaction, array_map(function (array $line) use ($currentItems, $newServices): array {
            $currentItem = $currentItems->get($line['service_id']);

            if ($currentItem !== null) {
                $name = $currentItem->service_name;
                $basePrice = $currentItem->service_base_price;
            } else {
                $service = $newServices->get($line['service_id']);
                $name = $service->name;
                $basePrice = $service->base_price;
            }

            return [
                'service_id' => $line['service_id'],
                'service_name' => $name,
                'service_base_price' => $basePrice,
                'amount' => $line['amount'],
            ];
        }, $data['services']));
    }
}
