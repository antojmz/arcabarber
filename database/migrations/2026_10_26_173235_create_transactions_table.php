<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->foreignId('registered_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('performed_at')->index();

            $table->string('service_name', 100);
            $table->unsignedInteger('service_base_price');
            $table->string('payment_method_name', 50);
            $table->decimal('atm_fee_rate', 5, 2);
            $table->decimal('barber_commission_rate', 5, 2);

            $table->unsignedInteger('amount');
            $table->unsignedInteger('tip')->default(0);
            $table->unsignedInteger('atm_fee_amount');
            $table->unsignedInteger('barber_amount');
            $table->unsignedInteger('shop_amount');
            $table->string('notes')->nullable();

            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('void_reason')->nullable();
            $table->timestamps();

            $table->index(['barber_id', 'performed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
