<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        $adminPrefix = config('admin.route_prefix');

        return $request->is($adminPrefix, $adminPrefix.'/*')
            ? route('admin.login')
            : (Route::has('public.login') ? route('public.login') : '/login');
    }
}
