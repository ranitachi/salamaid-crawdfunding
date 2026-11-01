<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\App\Http\Controllers\Admin\PaymentAdminController;

Route::middleware('admin.permission:payments.view,payments.manage')->group(function () {
    Route::get('payments', [PaymentAdminController::class, 'index'])->name('payments.index');
    Route::get('payments/export', [PaymentAdminController::class, 'export'])->name('payments.export');
    Route::get('payments/{payment}', [PaymentAdminController::class, 'show'])->name('payments.show');
    Route::get('webhooks', [PaymentAdminController::class, 'webhooks'])->name('payments.webhooks');
    Route::get('payments-reconciliation', [PaymentAdminController::class, 'reconciliation'])->name('payments.reconciliation');
});
