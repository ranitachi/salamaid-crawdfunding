<?php

use App\Http\Controllers\Admin\RoleAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Controllers\Admin\CategoryAdminController;

/*
|--------------------------------------------------------------------------
| Admin master data routes (A10)
|--------------------------------------------------------------------------
|
| User, role, and category management. Loaded from routes/admin.php inside
| the ['auth', 'admin'] group so authentication and admin-role checks are
| inherited. Each area also declares its permission explicitly.
|
*/

Route::prefix('users')->name('users.')
    ->middleware('admin.permission:users.roles.manage')
    ->group(function () {
        Route::get('/', [UserAdminController::class, 'index'])->name('index');
        Route::get('/{user}', [UserAdminController::class, 'show'])->name('show');
        Route::patch('/{user}/role', [UserAdminController::class, 'updateRole'])->name('role.update');
        Route::post('/{user}/activate', [UserAdminController::class, 'activate'])->name('activate');
        Route::post('/{user}/deactivate', [UserAdminController::class, 'deactivate'])->name('deactivate');
        Route::post('/{user}/password-reset', [UserAdminController::class, 'sendPasswordReset'])
            ->middleware('throttle:6,1')
            ->name('password-reset');
    });

Route::prefix('roles')->name('roles.')
    ->middleware('admin.permission:users.roles.manage')
    ->group(function () {
        Route::get('/', [RoleAdminController::class, 'index'])->name('index');
        Route::delete('/{role}', [RoleAdminController::class, 'destroy'])->name('destroy');
    });

Route::prefix('categories')->name('categories.')
    ->middleware('admin.permission:categories.manage')
    ->group(function () {
        Route::get('/', [CategoryAdminController::class, 'index'])->name('index');
        Route::get('/create', [CategoryAdminController::class, 'create'])->name('create');
        Route::post('/', [CategoryAdminController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [CategoryAdminController::class, 'edit'])->name('edit');
        Route::patch('/{category}', [CategoryAdminController::class, 'update'])->name('update');
        Route::post('/{category}/activate', [CategoryAdminController::class, 'activate'])->name('activate');
        Route::post('/{category}/deactivate', [CategoryAdminController::class, 'deactivate'])->name('deactivate');
        Route::delete('/{category}', [CategoryAdminController::class, 'destroy'])->name('destroy');
    });
