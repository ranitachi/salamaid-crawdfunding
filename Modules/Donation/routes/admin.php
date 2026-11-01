<?php

use Illuminate\Support\Facades\Route;
use Modules\Donation\App\Http\Controllers\Admin\DonationAdminController;

Route::middleware('admin.permission:donations.view')->group(function () {
    Route::get('donations', [DonationAdminController::class, 'index'])->name('donations.index');
    Route::get('donations/export', [DonationAdminController::class, 'export'])->name('donations.export');
    Route::get('donations/{donation}/receipt', [DonationAdminController::class, 'receipt'])->name('donations.receipt');
    Route::get('donations/{donation}', [DonationAdminController::class, 'show'])->name('donations.show');
});
