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
        Schema::create('cash_closing_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_closing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method_name', 50)->nullable();
            $table->foreignId('barber_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('barber_name')->nullable();
            $table->unsignedInteger('services_count');
            $table->unsignedBigInteger('gross_total');
            $table->unsignedBigInteger('tips_total');
            $table->unsignedBigInteger('atm_fee_total');
            $table->unsignedBigInteger('barber_total');
            $table->unsignedBigInteger('shop_total');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_closing_lines');
    }
};
