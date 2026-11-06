<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps role checks at the HTTP boundary so unauthorized users never reach
 * controller actions that may expose or mutate institution data.
 */
class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // A missing user and an inactive user both fail closed through hasAnyRole().
        abort_unless($request->user()?->hasAnyRole($roles), 403);

        return $next($request);
    }
}
