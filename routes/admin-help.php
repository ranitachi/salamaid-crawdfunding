<?php

use App\Http\Controllers\Admin\HelpArticleAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('help')->name('help.')
    ->middleware('admin.permission:help.manage')
    ->group(function (): void {
        Route::get('/', [HelpArticleAdminController::class, 'index'])->name('index');
        Route::get('/create', [HelpArticleAdminController::class, 'create'])->name('create');
        Route::post('/', [HelpArticleAdminController::class, 'store'])->name('store');
        Route::get('/{help}/edit', [HelpArticleAdminController::class, 'edit'])->name('edit');
        Route::patch('/{help}', [HelpArticleAdminController::class, 'update'])->name('update');
        Route::delete('/{help}', [HelpArticleAdminController::class, 'destroy'])->name('destroy');
    });
