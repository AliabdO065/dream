<?php

namespace App\Http\Middleware;

use App\Support\Access;
use App\Support\CurrentClient;
use App\Support\ManageMenu;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /** The root template that's loaded on the first page visit. */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props every dashboard page receives. Everything is a closure so it is evaluated when the response is built —
     * i.e. AFTER the "manage" middleware has pinned the current client.
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'app' => ['name' => config('app.name'), 'homeUrl' => url('/')],

            // the DASHBOARD's own language — independent of the languages a client's public site offers
            'locale' => app()->getLocale(),
            'direction' => in_array(app()->getLocale(), ['ar', 'he', 'fa', 'ur'], true) ? 'rtl' : 'ltr',

            'auth' => fn () => ($user = $request->user()) ? [
                'user' => [
                    ...$user->only('id', 'name', 'email'),
                    'is_super_admin' => (bool) $user->is_super_admin, // admin-only screens and buttons
                    'is_staff' => $user->isStaff(),                   // admin or assistant: the platform area
                    'platform_role' => $user->platformRole(),
                ],
                // the clients a (non-staff) user can switch between
                'clients' => $user->isStaff() ? [] : $user->clients()->orderBy('clients.name')->get()
                    ->map(fn ($c) => ['name' => $c->name, 'slug' => $c->slug])->all(),
                // this user's access on the client currently being managed (only inside /manage/{client}/…)
                'membership' => ($c = app(CurrentClient::class)->get()) ? [
                    'role' => $user->isStaff() ? 'owner' : $user->roleFor($c),
                    'can' => Access::map($user, $c), // what the UI may offer; the server checks the same abilities again
                ] : null,
            ] : null,

            'flash' => fn () => [
                'status' => $request->session()->get('status'),
                'created' => $request->session()->get('created'),
                'password' => $request->session()->get('password'), // ['email' => …, 'password' => …] right after a reset, shown once
            ],

            // the client being managed (only inside /manage/{client}/…)
            'client' => fn () => ($c = app(CurrentClient::class)->get()) ? [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'status' => $c->status,
                'url' => $c->publicUrl(),
                'is_home' => $c->isHome(),
                'logo' => $c->logo_path ? asset('uploads/' . $c->logo_path) : null,
            ] : null,

            // that client's sidebar entries; modules add their own (with an unread-style badge count)
            'menu' => fn () => ($c = app(CurrentClient::class)->get())
                ? array_map(fn ($i) => [
                    'label' => $i['label'],
                    'route' => $i['route'],
                    'icon' => $i['icon'],
                    'badge' => $i['badge'] ? (int) ($i['badge'])($c) : 0,
                ], ManageMenu::for($c, Access::map($request->user(), $c)))
                : [],
        ];
    }
}
