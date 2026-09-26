<?php

namespace App\Providers;

use App\Modules\Forms\FormsServiceProvider;
use App\Sections\Registry;
use App\Support\Access;
use App\Support\CurrentClient;
use App\Support\ManageMenu;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // scoped = reset per request/job, so a tenant can never leak into the next request on a long-lived worker.
        $this->app->scoped(CurrentClient::class);

        $this->app->singleton(Registry::class, function () {
            $registry = new Registry();
            foreach (config('platform.section_types', []) as $class) {
                $registry->register(new $class());
            }

            return $registry;
        });

        // Core, always-on: contact form section + leads inbox + email alerts. It keeps the module
        // folder structure (routes, views, migration, section type all in one place) but is not optional.
        $this->app->register(FormsServiceProvider::class);

        // Optional feature modules: each registers its own routes, migrations, views, section types, menu entries.
        foreach (config('platform.modules', []) as $provider) {
            $this->app->register($provider);
        }
    }

    public function boot(): void
    {
        // Named limiters: the throttle:N,M shorthand shares ONE counter per IP across every route,
        // so page views would eat login attempts. Each limiter below has its own bucket.
        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(20)->by('ip|' . $r->ip()),
            Limit::perMinute(6)->by('ip-email|' . $r->ip() . '|' . strtolower((string) $r->input('email'))),
        ]);
        RateLimiter::for('site', fn (Request $r) => Limit::perMinute(240)->by($r->ip()));

        ManageMenu::add('Overview', 'manage.dashboard', icon: 'layout-dashboard', order: 0);
        ManageMenu::add('Sections', 'manage.sections.index', icon: 'layout-template', order: 20);
        ManageMenu::add('Business profile', 'manage.profile.edit', icon: 'building-2', order: 30, ability: 'profile.edit');
        ManageMenu::add('Team', 'manage.team.index', icon: 'users', order: 40, ability: 'team.manage');

        Access::defineGates();
    }
}
