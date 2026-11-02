<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

/*
| Admin routes — registered in RouteServiceProvider with the `web` middleware,
| prefix config('admin.route_prefix') and name prefix `admin.`.
|
| Every area route must sit inside the ['auth', 'admin'] group and declare its
| permission with `admin.permission:<permission>` (see config/admin.php).
| Model actions additionally use policies (`can:approve,payout` or
| $this->authorize() in the controller).
*/

Route::get('login', [LoginController::class, 'create'])->name('login');
Route::post('login', [LoginController::class, 'store'])->name('login.store');
Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)
        ->middleware('admin.permission:dashboard.view')
        ->name('dashboard');

    // Module-owned admin routes. Authentication and admin-role protection come
    // from this group; each module declares its own area permission.
    require base_path('Modules/Donation/routes/admin.php');
    require base_path('Modules/Payment/routes/admin.php');
    require base_path('Modules/Operations/routes/admin.php');
    require base_path('routes/admin-master.php');
    require base_path('routes/admin-audit.php');
    require base_path('routes/admin-report.php');
    require base_path('routes/admin-notifications.php');
});

// Review routes use a strict internal response for guests (403) while the
// dashboard entrypoint keeps the normal redirect-to-login behavior.
Route::middleware(['admin.review.guest', 'admin'])
    ->group(base_path('routes/admin-review.php'));
