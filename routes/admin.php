<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
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

    Route::middleware('admin.permission:settings.manage')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update');
        Route::get('locations', [\App\Http\Controllers\Admin\LocationController::class, 'index'])->name('locations.index');
        Route::post('locations/provinces', [\App\Http\Controllers\Admin\LocationController::class, 'storeProvince'])->name('locations.provinces.store');
        Route::post('locations/cities', [\App\Http\Controllers\Admin\LocationController::class, 'storeCity'])->name('locations.cities.store');
        Route::patch('locations/provinces/{province}/toggle', [\App\Http\Controllers\Admin\LocationController::class, 'toggleProvince'])->name('locations.provinces.toggle');
        Route::patch('locations/cities/{city}/toggle', [\App\Http\Controllers\Admin\LocationController::class, 'toggleCity'])->name('locations.cities.toggle');
    });

    // Module-owned admin routes. Authentication and admin-role protection come
    // from this group; each module declares its own area permission.
    require base_path('Modules/Donation/routes/admin.php');
    require base_path('Modules/Payment/routes/admin.php');
    require base_path('Modules/Operations/routes/admin.php');
    require base_path('routes/admin-master.php');
    require base_path('routes/admin-help.php');
    require base_path('routes/admin-audit.php');
    require base_path('routes/admin-report.php');
    Route::middleware('admin.permission:donations.view')->group(function () {
        Route::get('donors', [\App\Http\Controllers\Admin\DonorAdminController::class, 'index'])->name('donors.index');
    });
    require base_path('routes/admin-notifications.php');
});

// Review routes use a strict internal response for guests (403) while the
// dashboard entrypoint keeps the normal redirect-to-login behavior.
Route::middleware(['admin.review.guest', 'admin'])
    ->group(base_path('routes/admin-review.php'));
