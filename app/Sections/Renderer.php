<?php

namespace App\Sections;

use App\Models\Client;
use Illuminate\Support\Facades\Cache;

/** Turns a client's enabled sections into the public page. The result is cached per client/version/locale. */
class Renderer
{
    public const FONTS = [
        'sans' => 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
        'serif' => 'Georgia, "Times New Roman", serif',
        'rounded' => '"Trebuchet MS", "Segoe UI", system-ui, sans-serif',
    ];

    public function page(Client $client, string $locale): string
    {
        // While developing locally (APP_ENV=local + APP_DEBUG=true) pages are rebuilt on every request,
        // so template/CSS changes show immediately. In production they are cached per client and version.
        if (app()->isLocal() && config('app.debug')) {
            return $this->build($client, $locale);
        }

        // Host is part of the key: pages contain absolute URLs, so the same page under another host must not be reused.
        // content_version already bumps on every client save (Client::booted).
        $key = "site:{$client->id}:v{$client->content_version}:{$locale}:" . request()->getHttpHost();

        return Cache::remember($key, now()->addDay(), fn () => $this->build($client, $locale));
    }

    private function build(Client $client, string $locale): string
    {
        $fallback = $client->default_locale;
        $types = $client->allowedSectionTypes();
        $blocks = [];
        $nav = [];

        foreach ($client->sections()->where('is_enabled', true)->get() as $section) {
            $type = $types[$section->type] ?? null;
            if (! $type) {
                continue; // unknown, removed or disabled type: hidden, never an error
            }

            $c = Schema::resolve($type->fields(), $section->content ?? [], $locale, $fallback);
            $config = Schema::resolve($type->configFields(), $section->config ?? [], $locale, $fallback);
            $anchor = $section->anchor ?: $section->type . '-' . $section->id;

            try {
                $blocks[] = view($type->view(), compact('c', 'config', 'section', 'client', 'locale', 'anchor'))->render();
            } catch (\Throwable $e) {
                report($e); // one broken section must never take the whole page down
                continue;
            }

            if ($config['show_in_nav'] ?? false) {
                // menu label: the short label if given, else the section heading, else its admin name
                $nav[] = ['anchor' => $anchor, 'label' => ($config['nav_label'] ?? '') ?: (($c['title'] ?? '') ?: $section->name)];
            }
        }

        $brand = $client->theme['brand'] ?? '#2563eb';
        $brand = preg_match('/^#[0-9a-fA-F]{6}$/', $brand) ? $brand : '#2563eb';
        $accent = $client->theme['accent'] ?? '#f59e0b';
        $accent = preg_match('/^#[0-9a-fA-F]{6}$/', $accent) ? $accent : '#f59e0b';

        return view('site.page', [
            'client' => $client,
            'blocks' => $blocks,
            'nav' => $nav,
            'locale' => $locale,
            'locales' => Translations::publicLocales($client), // only languages the whole page exists in
            'languageNames' => config('platform.languages', []),
            'poweredBy' => (bool) config('platform.powered_by', true) && ! $client->isHome(),
            'brand' => $brand,
            'onBrand' => $this->isLight($brand) ? '#111827' : '#ffffff',
            'accent' => $accent,
            'onAccent' => $this->isLight($accent) ? '#111827' : '#ffffff',
            'font' => self::FONTS[$client->theme['font'] ?? 'sans'] ?? self::FONTS['sans'],
        ])->render();
    }

    private function isLight(string $hex): bool
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 160;
    }
}
