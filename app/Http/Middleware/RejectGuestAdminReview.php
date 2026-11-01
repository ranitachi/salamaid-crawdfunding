<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Review endpoints are internal operations and deliberately return 403 for
 * guests; the dashboard entrypoint keeps the normal redirect-to-login UX.
 */
class RejectGuestAdminReview
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user(), 403);

        return $next($request);
    }
}
