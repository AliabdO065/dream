<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Guards /admin: platform admins and assistants. Admin-only screens add the `super` middleware on top. */
class EnsurePlatformStaff
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->isStaff(), 403);

        return $next($request);
    }
}
