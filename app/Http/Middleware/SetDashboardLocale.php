<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Dashboard-only locale (English/Arabic), independent of the public site's per-client languages.
 * Session-based (not tied to the account), same pattern as the old admin locale switch.
 */
class SetDashboardLocale
{
    public const LOCALES = ['en', 'ar'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('dashboard_locale', 'en');
        if (! in_array($locale, self::LOCALES, true)) {
            $locale = 'en';
        }
        App::setLocale($locale);

        return $next($request);
    }
}
