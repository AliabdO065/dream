<?php

use App\Http\Middleware\EnsurePlatformStaff;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveManagedClient;
use App\Http\Middleware\SetDashboardLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Public client sites: deliberately NOT in the "web" group (no session/cookies),
            // so pages stay cacheable. Registered last because it is a catch-all: /{slug}.
            Route::middleware('throttle:site')->group(base_path('routes/site.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'staff' => EnsurePlatformStaff::class, // admins + assistants
            'super' => EnsureSuperAdmin::class,    // admins only
            'manage' => ResolveManagedClient::class,
        ]);
        // Dashboards are an Inertia (Vue) app; the public sites are outside the "web" group and unaffected.
        // Locale must be set before HandleInertiaRequests shares it (and before any __() call in a controller).
        $middleware->web(append: [SetDashboardLocale::class, HandleInertiaRequests::class]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/home');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
