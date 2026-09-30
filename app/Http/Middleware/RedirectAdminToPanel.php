<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminToPanel
{
    /**
     * Keep admins out of the game so they cannot use up the player's progress.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_admin) {
            // The panel is not an Inertia page, so Inertia visits need a full page load to get there.
            return Inertia::location('/admin');
        }

        return $next($request);
    }
}
