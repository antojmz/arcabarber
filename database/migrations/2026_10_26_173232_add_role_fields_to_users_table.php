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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('barber')->index()->after('email');
            $table->boolean('is_active')->default(true)->index()->after('role');
            $table->decimal('commission_rate', 5, 2)->default(50)->after('is_active');
            $table->string('phone', 30)->nullable()->after('commission_rate');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['role', 'is_active', 'commission_rate', 'phone']);
        });
    }
};
