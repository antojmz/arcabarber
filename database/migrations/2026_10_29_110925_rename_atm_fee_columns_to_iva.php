<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns renamed from the ATM fee to IVA, grouped by table.
     *
     * @var array<string, array<string, string>>
     */
    private array $columns = [
        'payment_methods' => ['atm_fee_rate' => 'iva_rate'],
        'transactions' => ['atm_fee_rate' => 'iva_rate', 'atm_fee_amount' => 'iva_amount'],
        'cash_closings' => ['atm_fee_total' => 'iva_total'],
        'cash_closing_lines' => ['atm_fee_total' => 'iva_total'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->columns as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $from => $to) {
                    $table->renameColumn($from, $to);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->columns as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $from => $to) {
                    $table->renameColumn($to, $from);
                }
            });
        }
    }
};
