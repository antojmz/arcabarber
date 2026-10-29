<?php

namespace App\Actions\Transactions;

use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Support\RevenueSplit;
use Illuminate\Support\Facades\DB;

class UpdateTransaction
{
    public function __construct(private EnsurePeriodIsOpen $ensurePeriodIsOpen) {}

    /**
     * Correct a transaction. The snapshot of a catalog item is only refreshed
     * when that item changes, so editing the tip of an old transaction keeps
     * the rates that were in force when it was registered.
     *
     * @param  array{barber_id: int, service_id: int, payment_method_id: int, amount: int, tip: int, notes: string|null, performed_at: string}  $data
     */
    public function handle(Transaction $transaction, array $data): Transaction
    {
        $performedAt = now()->parse($data['performed_at']);

        $this->ensurePeriodIsOpen->handle($data['barber_id'], $performedAt);

        if ($data['service_id'] !== $transaction->service_id) {
            $service = Service::withTrashed()->findOrFail($data['service_id']);
            $transaction->service_id = $service->id;
            $transaction->service_name = $service->name;
            $transaction->service_base_price = $service->base_price;
        }

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

        $transaction->amount = $data['amount'];
        $transaction->tip = $data['tip'];
        $transaction->notes = $data['notes'];
        $transaction->performed_at = $performedAt;

        $split = RevenueSplit::calculate($transaction->amount, $transaction->iva_rate, $transaction->barber_commission_rate);
        $transaction->iva_amount = $split->iva;
        $transaction->barber_amount = $split->barber;
        $transaction->shop_amount = $split->shop;

        DB::transaction(fn () => $transaction->save());

        return $transaction;
    }
}
