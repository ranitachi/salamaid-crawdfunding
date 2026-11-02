<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicSite\CampaignController;
use App\Http\Controllers\PublicSite\PaymentStatusController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [CampaignController::class, 'home'])->name('public.home');

Route::get('/program', [CampaignController::class, 'program'])->name('public.program');
Route::get('/campaigns/{slug}', [CampaignController::class, 'show'])->name('public.campaigns.show');
Route::get('/campaigns/{slug}/donate', [CampaignController::class, 'donate'])->name('public.campaigns.donate');
Route::post('/campaigns/{slug}/donate', [CampaignController::class, 'storeDonation'])->name('public.campaigns.donate.store');
Route::get('/payments/status/{donation}', [PaymentStatusController::class, 'show'])->name('public.payments.status');
Route::post('/payments/status/{donation}/refresh', [PaymentStatusController::class, 'refresh'])->name('public.payments.status.refresh');
Route::view('/inbox', 'public.inbox')->name('public.inbox');
Route::view('/account', 'public.account')->name('public.account');
Route::view('/account/donations', 'public.donations')->name('public.donations');
