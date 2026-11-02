<?php

namespace App\Providers;

use App\Services\AppSettingsService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['admin.layouts.app', 'admin.auth.login'], function ($view): void {
            $view->with('brand', app(AppSettingsService::class)->all());
        });
    }
}
