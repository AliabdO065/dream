<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Sections\Renderer;
use App\Sections\Translations;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    /** "/" — the platform's own marketing site, or the login page if none has been set up yet. */
    public function home(Request $request, Renderer $renderer)
    {
        $client = Client::where('slug', config('platform.home_client'))->first();

        if (! $client || ! $client->isActive()) {
            return redirect()->route('login');
        }

        return $this->respond($request, $renderer, $client);
    }

    public function show(Request $request, Renderer $renderer, string $client)
    {
        $client = Client::where('slug', $client)->first();
        abort_unless($client && $client->isActive(), 404);

        // The home client has ONE address: "/".
        if ($client->isHome()) {
            $query = $request->getQueryString();

            return redirect()->to(url('/') . ($query ? '?' . $query : ''), 301);
        }

        return $this->respond($request, $renderer, $client);
    }

    private function respond(Request $request, Renderer $renderer, Client $client)
    {
        // A language that isn't fully translated yet is not offered — an old ?lang= link gets the main language
        // rather than a page that is half one language, half another.
        $lang = $request->query('lang');
        $locale = is_string($lang) && in_array($lang, Translations::publicLocales($client), true) ? $lang : $client->default_locale;

        $html = $renderer->page($client, $locale);

        // The home site's header shows "Log in" or, for a signed-in user, their dashboard link —
        // so it differs per visitor and must not be kept by shared or browser caches.
        if ($client->isHome()) {
            if ($user = $request->user()) {
                $account = view('site.partials.nav-account', [
                    'user' => $user,
                    'label' => config("ui.dashboard.$locale") ?? config('ui.dashboard.en'),
                ])->render();
                $html = preg_replace_callback('/<!--nav-account-->.*?<!--\/nav-account-->/s', fn () => $account, $html);
            }

            return response($html)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Cache-Control', 'private, no-cache');
        }

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=60');
    }
}
