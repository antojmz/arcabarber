<?php

use App\Http\Controllers\Admin\BarberController;
use App\Http\Controllers\Admin\CashClosingController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MyProductionController;
use App\Http\Controllers\TransactionController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::bind('barber', fn (string $value) => User::barbers()->findOrFail($value));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');

    Route::get('my/production', MyProductionController::class)->name('my.production');

    Route::middleware('role:admin')->group(function () {
        Route::get('transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
        Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::post('transactions/{transaction}/void', [TransactionController::class, 'void'])->name('transactions.void');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('barbers', BarberController::class)->except(['show', 'destroy']);
        Route::resource('services', ServiceController::class)->except('show');
        Route::resource('payment-methods', PaymentMethodController::class)->except('show');

        Route::get('reports/barber', [ReportController::class, 'barber'])->name('reports.barber');
        Route::get('reports/general', [ReportController::class, 'general'])->name('reports.general');

        Route::resource('closings', CashClosingController::class)->only(['index', 'store', 'show']);
        Route::post('closings/{closing}/mark-paid', [CashClosingController::class, 'markPaid'])->name('closings.mark-paid');
    });
});
