<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Payment\App\Http\Controllers\Api\PaymentWebhookController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    Route::get('payment', fn (Request $request) => $request->user())->name('payment');
});

Route::post('v1/payment/webhooks/{provider}', PaymentWebhookController::class)
    ->where('provider', '[a-z0-9_-]+')
    ->name('api.payment.webhooks');
