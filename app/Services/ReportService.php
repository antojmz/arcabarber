<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Aggregated columns shared by every breakdown.
     */
    private const string AGGREGATES = 'COUNT(*) as services_count, SUM(amount) as gross_total, SUM(tip) as tips_total, '
        .'SUM(iva_amount) as iva_total, SUM(barber_amount) as barber_total, SUM(shop_amount) as shop_total';

    /**
     * Build the financial summary of a period, optionally limited to one barber.
     * Voided transactions are always excluded.
     *
     * @return array{
     *     totals: array<string, int>,
     *     by_payment_method: list<array<string, int|string|null>>,
     *     by_service: list<array<string, int|string|null>>,
     *     by_barber: list<array<string, int|string|null>>,
     * }
     */
    public function summary(CarbonInterface $start, CarbonInterface $end, ?int $barberId = null): array
    {
        $byPaymentMethod = $this->breakdown($start, $end, $barberId, 'payment_method_id', 'MAX(payment_method_name) as payment_method_name');
        $byService = $this->byService($start, $end, $barberId);
        $byBarber = $barberId === null ? $this->byBarber($start, $end) : [];

        return [
            'totals' => $this->totals($byPaymentMethod),
            'by_payment_method' => $byPaymentMethod,
            'by_service' => $byService,
            'by_barber' => $byBarber,
        ];
    }

    /**
     * Count services per service and payment method, e.g. "Corte: 3 débito, 2 efectivo".
     *
     * @return list<array{service_id: int, payment_method_id: int, services_count: int}>
     */
    public function serviceCountsByPaymentMethod(CarbonInterface $start, CarbonInterface $end, ?int $barberId = null): array
    {
        return array_values($this->itemsQuery($start, $end, $barberId)
            ->selectRaw('transaction_items.service_id, transactions.payment_method_id, COUNT(*) as services_count')
            ->groupBy('transaction_items.service_id', 'transactions.payment_method_id')
            ->toBase()
            ->get()
            ->map(fn (object $row) => [
                'service_id' => (int) $row->service_id,
                'payment_method_id' => (int) $row->payment_method_id,
                'services_count' => (int) $row->services_count,
            ])
            ->all());
    }

    /**
     * @param  list<array<string, int|string|null>>  $lines
     * @return array<string, int>
     */
    public function totals(array $lines): array
    {
        $lines = collect($lines);

        $totals = collect(['services_count', 'gross_total', 'tips_total', 'iva_total', 'barber_total', 'shop_total'])
            ->mapWithKeys(fn (string $column) => [$column => (int) $lines->sum($column)])
            ->all();

        $totals['payout_total'] = $totals['barber_total'] + $totals['tips_total'];

        return $totals;
    }

    /**
     * Totals per service, read from the services of each transaction. Tips
     * belong to the whole visit, so they are not attributed to any service.
     *
     * @return list<array<string, int|string|null>>
     */
    private function byService(CarbonInterface $start, CarbonInterface $end, ?int $barberId): array
    {
        return array_values($this->itemsQuery($start, $end, $barberId)
            ->selectRaw('transaction_items.service_id, MAX(transaction_items.service_name) as service_name, COUNT(*) as services_count, '
                .'SUM(transaction_items.amount) as gross_total, 0 as tips_total, SUM(transaction_items.iva_amount) as iva_total, '
                .'SUM(transaction_items.barber_amount) as barber_total, SUM(transaction_items.shop_amount) as shop_total')
            ->groupBy('transaction_items.service_id')
            ->orderByDesc('gross_total')
            ->toBase()
            ->get()
            ->map(fn (object $row) => $this->castRow((array) $row))
            ->all());
    }

    /**
     * @return list<array<string, int|string|null>>
     */
    private function byBarber(CarbonInterface $start, CarbonInterface $end): array
    {
        $lines = $this->breakdown($start, $end, null, 'barber_id');

        $names = User::withTrashed()
            ->whereIn('id', array_column($lines, 'barber_id'))
            ->pluck('name', 'id');

        return array_map(fn (array $line) => [
            'barber_id' => $line['barber_id'],
            'barber_name' => $names[$line['barber_id']] ?? null,
            ...$line,
        ], $lines);
    }

    /**
     * @param  literal-string  $groupColumn
     * @param  literal-string|null  $labelColumn
     * @return list<array<string, int|string|null>>
     */
    private function breakdown(CarbonInterface $start, CarbonInterface $end, ?int $barberId, string $groupColumn, ?string $labelColumn = null): array
    {
        $columns = $labelColumn === null ? $groupColumn : "{$groupColumn}, {$labelColumn}";

        return array_values($this->query($start, $end, $barberId)
            ->selectRaw("{$columns}, ".self::AGGREGATES)
            ->groupBy($groupColumn)
            ->orderByDesc('gross_total')
            ->toBase()
            ->get()
            ->map(fn (object $row) => $this->castRow((array) $row))
            ->all());
    }

    /**
     * @return Builder<Transaction>
     */
    private function query(CarbonInterface $start, CarbonInterface $end, ?int $barberId): Builder
    {
        return Transaction::query()
            ->valid()
            ->performedBetween($start, $end)
            ->when($barberId !== null, fn (Builder $query) => $query->where('barber_id', $barberId));
    }

    /**
     * Services of the valid transactions of the period.
     *
     * @return Builder<TransactionItem>
     */
    private function itemsQuery(CarbonInterface $start, CarbonInterface $end, ?int $barberId): Builder
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->whereNull('transactions.voided_at')
            ->whereBetween('transactions.performed_at', [$start, $end])
            ->when($barberId !== null, fn (Builder $query) => $query->where('transactions.barber_id', $barberId));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, int|string|null>
     */
    private function castRow(array $row): array
    {
        return (new Collection($row))
            ->map(fn (mixed $value, string $key) => str_ends_with($key, '_name') ? $value : (int) $value)
            ->all();
    }
}
