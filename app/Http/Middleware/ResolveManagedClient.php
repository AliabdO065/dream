<?php

namespace App\Http\Middleware;

use App\Models\Client;
use App\Support\CurrentClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Guards /manage/{client}/...: the user must be a member of that client (or a super admin).
 * Then it pins the tenant for the request, so tenant models are auto-scoped from here on.
 */
class ResolveManagedClient
{
    public function handle(Request $request, Closure $next)
    {
        $client = $request->route('client');
        if (! $client instanceof Client) {
            // route-model binding may not have run yet; resolve it ourselves
            $client = Client::where('slug', $client)->firstOrFail();
            $request->route()->setParameter('client', $client);
        }

        $user = $request->user();
        abort_unless($user && $user->canManage($client), 403);

        app(CurrentClient::class)->set($client);
        View::share('managedClient', $client);

        return $next($request);
    }

    /** Clear the tenant once the response is sent (defence in depth for long-lived workers). */
    public function terminate(Request $request, $response): void
    {
        app(CurrentClient::class)->set(null);
    }
}
