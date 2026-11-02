<?php

use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('admin.permission:reports.view')->group(function () {
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
});
