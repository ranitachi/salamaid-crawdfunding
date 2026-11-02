<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\App\Http\Controllers\Admin\PaymentAdminController;
use Modules\Payment\App\Http\Controllers\Admin\PaymentChannelAdminController;
use Modules\Payment\App\Http\Controllers\Admin\PaymentSyncAdminController;
use Modules\Payment\App\Http\Controllers\Admin\MayarTransactionController;

Route::middleware('admin.permission:payments.manage')->group(function () {
    Route::get('payment-channels', [PaymentChannelAdminController::class, 'index'])->name('payment-channels.index');
    Route::post('payment-channels', [PaymentChannelAdminController::class, 'store'])->name('payment-channels.store');
    Route::patch('payment-channels/{channel}', [PaymentChannelAdminController::class, 'update'])->name('payment-channels.update');
    Route::post('payment-channels/{channel}/activate', [PaymentChannelAdminController::class, 'activate'])->name('payment-channels.activate');
    Route::post('payment-channels/{channel}/deactivate', [PaymentChannelAdminController::class, 'deactivate'])->name('payment-channels.deactivate');
    Route::post('payment-channels/{channel}/fees', [PaymentChannelAdminController::class, 'storeFee'])->name('payment-channels.fees.store');

    // A7: provider confirmation only — there is no route to set a payment status manually.
    Route::post('payments/{payment}/sync', [PaymentSyncAdminController::class, 'sync'])->whereUuid('payment')->name('payments.sync');
    Route::post('webhooks/{webhook}/retry', [PaymentSyncAdminController::class, 'retryWebhook'])->whereUuid('webhook')->name('payments.webhooks.retry');
});

Route::middleware('admin.permission:payments.view,payments.manage')->group(function () {
    Route::get('mayar-transactions', [MayarTransactionController::class, 'index'])->name('mayar-transactions.index');
    Route::get('mayar-transactions/{transaction}', [MayarTransactionController::class, 'show'])->name('mayar-transactions.show');
    Route::get('payments', [PaymentAdminController::class, 'index'])->name('payments.index');
    Route::get('payments/export', [PaymentAdminController::class, 'export'])->name('payments.export');
    Route::get('payments/{payment}', [PaymentAdminController::class, 'show'])->name('payments.show');
    Route::get('webhooks', [PaymentAdminController::class, 'webhooks'])->name('payments.webhooks');
    Route::get('payments-reconciliation', [PaymentAdminController::class, 'reconciliation'])->name('payments.reconciliation');
});
