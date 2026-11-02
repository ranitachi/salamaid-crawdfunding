<?php

use App\Http\Controllers\Admin\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('admin.permission:notifications.view')->group(function (): void {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});
