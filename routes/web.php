<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicSite\CampaignController;
use App\Http\Controllers\PublicSite\PaymentStatusController;
use App\Http\Controllers\PublicSite\PublicAccountController;
use App\Http\Controllers\PublicSite\PublicAuthController;
use App\Http\Controllers\PublicSite\PublicSeoController;

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
Route::get('/campaigns/{slug}/transparency', [CampaignController::class, 'transparency'])->name('public.campaigns.transparency');
Route::get('/campaigns/{slug}/donate', [CampaignController::class, 'donate'])->name('public.campaigns.donate');
Route::post('/campaigns/{slug}/donate', [CampaignController::class, 'storeDonation'])->name('public.campaigns.donate.store');
Route::get('/payments/status/{donation}', [PaymentStatusController::class, 'show'])->name('public.payments.status');
Route::post('/payments/status/{donation}/refresh', [PaymentStatusController::class, 'refresh'])->name('public.payments.status.refresh');
Route::get('/sitemap.xml', [PublicSeoController::class, 'sitemap'])->name('public.sitemap');
Route::get('/robots.txt', [PublicSeoController::class, 'robots'])->name('public.robots');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [PublicAuthController::class, 'create'])->name('public.login');
    Route::post('/login', [PublicAuthController::class, 'store'])->name('public.login.store');
    Route::get('/register', [PublicAuthController::class, 'register'])->name('public.register');
    Route::post('/register', [PublicAuthController::class, 'createAccount'])->name('public.register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [PublicAuthController::class, 'destroy'])->name('public.logout');
    Route::get('/inbox', [PublicAccountController::class, 'inbox'])->name('public.inbox');
    Route::post('/inbox/read-all', [PublicAccountController::class, 'markAllRead'])->name('public.inbox.read-all');
    Route::post('/inbox/{notification}/read', [PublicAccountController::class, 'markRead'])->name('public.inbox.read');
    Route::get('/account', [PublicAccountController::class, 'account'])->name('public.account');
    Route::get('/account/donations', [PublicAccountController::class, 'donations'])->name('public.donations');
    Route::get('/account/donations/{donation}/receipt', [PublicAccountController::class, 'receipt'])->name('public.receipt');
});
