<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use App\Support\RevenueSplit;
use Illuminate\Support\Facades\DB;

class SaveTransactionWithItems
{
    /**
     * Save a transaction together with its services. Each service gets its own
     * split with the rates of the transaction, and the transaction amounts are
     * the sum of its services so reports can keep reading from transactions.
     *
     * @param  list<array{service_id: int, service_name: string, service_base_price: int, amount: int}>  $items
     */
    public function handle(Transaction $transaction, array $items): Transaction
    {
        $items = array_map(function (array $item) use ($transaction): array {
            $split = RevenueSplit::calculate($item['amount'], $transaction->iva_rate, $transaction->barber_commission_rate);

            return [...$item, 'iva_amount' => $split->iva, 'barber_amount' => $split->barber, 'shop_amount' => $split->shop];
        }, $items);

        foreach (['amount', 'iva_amount', 'barber_amount', 'shop_amount'] as $column) {
            $transaction->{$column} = array_sum(array_column($items, $column));
        }

        return DB::transaction(function () use ($transaction, $items): Transaction {
            $transaction->save();
            $transaction->items()->delete();
            $transaction->items()->createMany($items);

            return $transaction->load('items');
        });
    }
}
