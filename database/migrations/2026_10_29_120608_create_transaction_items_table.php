<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. A transaction now groups every service performed in
     * the same visit, so the service snapshot moves to its own items table.
     */
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->string('service_name', 100);
            $table->unsignedInteger('service_base_price');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('iva_amount');
            $table->unsignedInteger('barber_amount');
            $table->unsignedInteger('shop_amount');
            $table->timestamps();
        });

        DB::table('transaction_items')->insertUsing(
            ['transaction_id', 'service_id', 'service_name', 'service_base_price', 'amount', 'iva_amount', 'barber_amount', 'shop_amount', 'created_at', 'updated_at'],
            DB::table('transactions')->select(['id', 'service_id', 'service_name', 'service_base_price', 'amount', 'iva_amount', 'barber_amount', 'shop_amount', 'created_at', 'updated_at']),
        );

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
            $table->dropColumn(['service_name', 'service_base_price']);
        });
    }

    /**
     * Reverse the migrations, keeping the first service of each transaction.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('barber_id')->constrained()->restrictOnDelete();
            $table->string('service_name', 100)->default('')->after('performed_at');
            $table->unsignedInteger('service_base_price')->default(0)->after('service_name');
        });

        DB::table('transaction_items')
            ->whereIn('id', DB::table('transaction_items')->selectRaw('MIN(id)')->groupBy('transaction_id'))
            ->orderBy('id')
            ->each(fn (object $item) => DB::table('transactions')->where('id', $item->transaction_id)->update([
                'service_id' => $item->service_id,
                'service_name' => $item->service_name,
                'service_base_price' => $item->service_base_price,
            ]));

        Schema::dropIfExists('transaction_items');
    }
};
