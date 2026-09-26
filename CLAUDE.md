# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

**Read [README.md](README.md) first** — it is the authoritative description of the architecture (tenants, sections,
schema, roles, leads, uploads, i18n). This file only adds commands and the rules that are easy to break.

## Commands

```bash
php artisan test                                   # all tests (SQLite in-memory, array cache/mail — see phpunit.xml)
php artisan test --filter=RolesTest                # one file / class
php artisan test --filter=test_method_name         # one test
composer test                                      # config:clear + test
vendor/bin/pint                                    # PHP formatter
npm run build                                      # Vue dashboards → public/build (npm run dev = Vite live reload)
php artisan cache:clear                            # after changing public-site templates outside local/debug mode
php -l path/to/file.php && php artisan view:cache  # quick syntax check of PHP + all Blade views (then view:clear)
```

Local environment: Windows, XAMPP's MySQL (database `erp`, root, no password). The app is run with **`php artisan serve`**
(http://127.0.0.1:8000) — not through Apache, even though `.env` has an XAMPP-style `APP_URL`. To check a change in the real app
without clashing with the user's server, run `php artisan serve --port=8123` in the background and `curl` it; stop it afterwards.
Artisan commands that touch the DB hang if XAMPP's MySQL isn't running — check that before assuming a code bug.

## Two separate front ends

- **Public sites** (`/` and `/{slug}`): server-rendered Blade (`resources/views/site`, `resources/views/sections`),
  plain CSS in `public/css/site.css` + `public/css/themes/nova.css`, plain JS in `public/js/site.js`. No Vite, no Tailwind.
  The only page shell theme is **nova** (`site/headers/nova.blade.php`, `site/footers/nova.blade.php`).
- **Dashboards** (`/admin`, `/manage/{slug}`, `/login`): Vue 3 + Inertia + Tailwind v4 in `resources/js`, built by Vite.

## Rules that are easy to break

- **Public page HTML is cached and shared by every visitor** (`app/Sections/Renderer.php`, keyed by client, `content_version`,
  locale and host; rebuilt every request only when `APP_ENV=local` + `APP_DEBUG=true`, so the bug won't show locally).
  Never put per-visitor/per-user output in `site/*` or `sections/*` templates. Pattern for per-request bits: render a
  marker into the cached HTML and replace it in `SiteController::respond()` (see `<!--nav-account-->` in
  `site/partials/nav-cta.blade.php`).
- **Public routes have no session** (`routes/site.php` is registered outside the `web` group in `bootstrap/app.php`) so client
  sites stay cookie-free and `Cache-Control: public`. Only `/` (the platform's own site) has `web` middleware, and its response is
  `private, no-cache` because the header shows the signed-in user. Don't add `web` to `/{slug}`.
- `routes/site.php`'s `/{client}` is a catch-all: new top-level paths must go in `routes/web.php` **and** in
  `config/platform.php` → `reserved_slugs`.
- Words the public page prints itself go in `config/ui.php` with **all 10 languages** (a test enforces it).
  Dashboard strings go in `resources/js/i18n/{en,ar}.js`; server/section-field labels use `__()` + `lang/ar.json`.
- Dashboard layout uses logical Tailwind utilities (`ps-`/`pe-`, `start-`/`end-`); public CSS uses logical properties — both must work RTL.
- An Inertia page prop must never be named `client` (reserved shared prop, see `HandleInertiaRequests`).
- Access rules live only in `app/Support/Access.php` (Gates → `can:` middleware, mirrored to Vue as `auth.membership.can`).
