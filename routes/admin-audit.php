<?php

use App\Http\Controllers\Admin\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::get('audit-logs', [AuditLogController::class, 'index'])
    ->middleware('admin.permission:audit.view')
    ->name('audit-logs.index');
