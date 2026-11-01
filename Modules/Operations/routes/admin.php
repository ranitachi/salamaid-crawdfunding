<?php

use Illuminate\Support\Facades\Route;
use Modules\Operations\App\Http\Controllers\Admin\PayoutAdminController;

Route::middleware('admin.permission:payouts.view,payouts.manage')->group(function () {
    Route::get('payouts', [PayoutAdminController::class, 'index'])->name('payouts.index');
    Route::get('payouts/{payout}', [PayoutAdminController::class, 'show'])->name('payouts.show');
    Route::post('payouts/{payout}/approve', [PayoutAdminController::class, 'approve'])->name('payouts.approve');
    Route::post('payouts/{payout}/reject', [PayoutAdminController::class, 'reject'])->name('payouts.reject');
    Route::post('payouts/{payout}/paid', [PayoutAdminController::class, 'markPaid'])->name('payouts.paid');
});
