<?php

namespace App\Modules\Forms;

use App\Models\Client;
use App\Sections\Registry;
use App\Support\ManageMenu;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Core (always on): the contact-form section, the leads inbox and the email alert to the owner.
 * Kept as a self-contained folder so everything about leads lives in one place.
 */
class FormsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('forms-submit', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadViewsFrom(__DIR__ . '/views', 'forms');
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        $this->app->make(Registry::class)->register(new ContactFormType());

        ManageMenu::add('Leads', 'manage.leads.index',
            badge: fn (Client $c) => Submission::where('client_id', $c->id)->whereNull('read_at')->count(),
            icon: 'inbox', order: 10);
    }
}
