<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('admin.permission:payouts.manage')
 * Several permissions are OR-ed: 'admin.permission:payouts.view,payouts.manage'.
 */
class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        abort_unless($request->user()?->hasAnyAdminPermission(...$permissions), 403);

        return $next($request);
    }
}
