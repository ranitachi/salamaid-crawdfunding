<?php

use Illuminate\Support\Facades\Route;
use Modules\Transparency\App\Http\Controllers\Admin\CampaignUpdateAdminController as Controller;

/*
| A9 Transparency admin routes. Registered by this module's RouteServiceProvider
| with middleware [web, auth, admin], prefix "admin/transparency" and name
| prefix "admin.transparency." — routes/admin.php is intentionally untouched.
|
| Area access: transparency.view or transparency.review. Every action is also
| authorized by CampaignUpdatePolicy in the controller / Form Request.
| Attachment routes use scoped bindings: an attachment must belong to {update}.
*/

Route::middleware('admin.permission:transparency.view,transparency.review,transparency.manage')
    ->whereUuid(['update', 'attachment'])
    ->group(function () {
        Route::get('/', [Controller::class, 'index'])->name('index');
        Route::get('create', [Controller::class, 'create'])->name('create');
        Route::post('/', [Controller::class, 'store'])->name('store');
        Route::get('{update}', [Controller::class, 'show'])->name('show');
        Route::post('{update}/submit', [Controller::class, 'submit'])->name('submit');
        Route::post('{update}/approve', [Controller::class, 'approve'])->name('approve');
        Route::post('{update}/reject', [Controller::class, 'reject'])->name('reject');
        Route::post('{update}/publish', [Controller::class, 'publish'])->name('publish');
        Route::post('{update}/unpublish', [Controller::class, 'unpublish'])->name('unpublish');

        Route::scopeBindings()->group(function () {
            Route::post('{update}/attachments', [Controller::class, 'storeAttachment'])->name('attachments.store');
            Route::get('{update}/attachments/{attachment}/preview', [Controller::class, 'previewAttachment'])->name('attachments.preview');
            Route::get('{update}/attachments/{attachment}', [Controller::class, 'downloadAttachment'])->name('attachments.download');
            Route::delete('{update}/attachments/{attachment}', [Controller::class, 'destroyAttachment'])->name('attachments.destroy');
        });
    });
