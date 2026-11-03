<?php

use App\Http\Controllers\Admin\HelpArticleAdminController;
use App\Http\Controllers\Admin\HelpCategoryAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('help')->name('help.')
    ->middleware('admin.permission:help.manage')
    ->group(function (): void {
        Route::get('/categories', [HelpCategoryAdminController::class, 'index'])->name('categories.index');
        Route::post('/categories', [HelpCategoryAdminController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{helpCategory}', [HelpCategoryAdminController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{helpCategory}', [HelpCategoryAdminController::class, 'destroy'])->name('categories.destroy');
        Route::get('/', [HelpArticleAdminController::class, 'index'])->name('index');
        Route::get('/create', [HelpArticleAdminController::class, 'create'])->name('create');
        Route::post('/', [HelpArticleAdminController::class, 'store'])->name('store');
        Route::get('/{help}/edit', [HelpArticleAdminController::class, 'edit'])->name('edit');
        Route::patch('/{help}', [HelpArticleAdminController::class, 'update'])->name('update');
        Route::delete('/{help}', [HelpArticleAdminController::class, 'destroy'])->name('destroy');
    });
