<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\App\Http\Controllers\Admin\CampaignModerationController;
use App\Http\Controllers\Admin\DonorAdminController;
use Modules\Core\App\Http\Controllers\Admin\OrganizationReviewController;
use Modules\Core\App\Http\Controllers\Admin\OrganizationProfileController;

/*
|--------------------------------------------------------------------------
| Admin review routes (A4 & A5)
|--------------------------------------------------------------------------
|
| File ini dipisahkan dari routes/admin.php untuk menjaga ownership domain.
| Route di-include oleh routes/admin.php di dalam group auth + admin.
|
*/

Route::prefix('organizations')->name('organizations.')
    ->middleware('admin.permission:organizations.view,organizations.review')
    ->group(function () {
    Route::get('/', [OrganizationReviewController::class, 'index'])->name('index');
    Route::get('/{organization}/verification-document', [OrganizationReviewController::class, 'verificationDocument'])->name('verification-document');
    Route::get('/{organization}', [OrganizationReviewController::class, 'show'])->name('show');
    Route::post('/{organization}/approve', [OrganizationReviewController::class, 'approve'])->name('approve');
    Route::post('/{organization}/reject', [OrganizationReviewController::class, 'reject'])->name('reject');
    Route::post('/{organization}/suspend', [OrganizationReviewController::class, 'suspend'])->name('suspend');
    Route::post('/{organization}/restore', [OrganizationReviewController::class, 'restore'])->name('restore');
    });

Route::prefix('organizations')->name('organizations.')
    ->middleware('admin.permission:organizations.manage')
    ->group(function () {
        Route::get('/{organization}/settings', [OrganizationProfileController::class, 'edit'])->name('settings.edit');
        Route::put('/{organization}/settings', [OrganizationProfileController::class, 'update'])->name('settings.update');
    });

Route::prefix('campaigns')->name('campaigns.')
    ->middleware('admin.permission:campaigns.manage')
    ->group(function () {
        Route::get('/create', [CampaignModerationController::class, 'create'])->name('create');
        Route::post('/', [CampaignModerationController::class, 'store'])->name('store');
    });

Route::prefix('campaigns')->name('campaigns.')
    ->middleware('admin.permission:campaigns.view,campaigns.review,campaigns.publish')
    ->group(function () {
    Route::get('/', [CampaignModerationController::class, 'index'])->name('index');
    Route::get('/{campaign}/donors', [DonorAdminController::class, 'campaign'])->name('donors');
    Route::get('/{campaign}/edit', [CampaignModerationController::class, 'edit'])->name('edit');
    Route::put('/{campaign}', [CampaignModerationController::class, 'update'])->name('update');
    Route::post('/{campaign}/media', [CampaignModerationController::class, 'storeMedia'])->name('media.store');
    Route::delete('/{campaign}/media/{media}', [CampaignModerationController::class, 'destroyMedia'])->name('media.destroy');
    Route::get('/{campaign}', [CampaignModerationController::class, 'show'])->name('show');
    Route::post('/{campaign}/submit', [CampaignModerationController::class, 'submit'])->name('submit');
    Route::post('/{campaign}/publish', [CampaignModerationController::class, 'publish'])->name('publish');
    Route::post('/{campaign}/reject', [CampaignModerationController::class, 'reject'])->name('reject');
    Route::post('/{campaign}/suspend', [CampaignModerationController::class, 'suspend'])->name('suspend');
    Route::post('/{campaign}/restore', [CampaignModerationController::class, 'restore'])->name('restore');
    Route::post('/{campaign}/close', [CampaignModerationController::class, 'close'])->name('close');
    Route::post('/{campaign}/feature', [CampaignModerationController::class, 'feature'])->name('feature');
    Route::post('/{campaign}/unfeature', [CampaignModerationController::class, 'unfeature'])->name('unfeature');
    Route::delete('/{campaign}', [CampaignModerationController::class, 'destroy'])->name('destroy');
    Route::delete('/{campaign}/force', [CampaignModerationController::class, 'forceDestroy'])->name('force-destroy');
    });

