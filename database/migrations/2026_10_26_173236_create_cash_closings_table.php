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
        Schema::create('cash_closings', function (Blueprint $table) {
            $table->id();
            $table->string('period_type', 10);
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('barber_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('services_count');
            $table->unsignedBigInteger('gross_total');
            $table->unsignedBigInteger('tips_total');
            $table->unsignedBigInteger('atm_fee_total');
            $table->unsignedBigInteger('barber_total');
            $table->unsignedBigInteger('shop_total');
            $table->unsignedBigInteger('payout_total');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['period_type', 'period_start', 'barber_id']);
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_closings');
    }
};
