<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicSite\CampaignController;

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
Route::view('/inbox', 'public.inbox')->name('public.inbox');
Route::view('/account', 'public.account')->name('public.account');
Route::view('/account/donations', 'public.donations')->name('public.donations');
