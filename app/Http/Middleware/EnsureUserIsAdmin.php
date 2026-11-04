<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Support\OrganizationAdminAccess;

/**
 * Runs after `auth`: only active users with a valid admin role may continue.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, 'Akun tidak aktif.');
            }

            // Keep the login entrypoint friendly for the dashboard smoke flow;
            // protected area requests remain explicit 403 responses.
            if ($request->routeIs('admin.dashboard')) {
                return redirect()->route('admin.login')
                    ->withErrors(['email' => 'Akun Anda tidak aktif.']);
            }

            abort(403, 'Akun tidak aktif.');
        }

        abort_unless($user->isAdmin(), 403);
        abort_unless(OrganizationAdminAccess::canAccess($user), 403, 'Akun lembaga belum diverifikasi.');

        return $next($request);
    }
}
