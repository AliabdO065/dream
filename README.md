# ERP Sites — generic multi-tenant site platform

Any business gets a public site at `/{slug}`, built from **sections** the client (or you) can add, reorder,
switch off or delete. Nothing is tied to one industry.

- **Public sites** (the marketing site at `/`, every client at `/{slug}`) are rendered on the server with Blade: fast, SEO-friendly, cookie-free and cached.
- **Dashboards** (`/admin` for the platform owner, `/manage/{slug}` for each client) are a **Vue 3 + Inertia + Tailwind** app with charts,
  drag & drop, dialogs and toasts. Laravel 12, MariaDB/MySQL.

## Run it

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate      # set DB_* (database "erp")
php artisan migrate
npm run build                                         # builds the Vue dashboards into public/build (npm run dev = live reload)
php artisan platform:make-admin you@example.com       # prints a one-time password
php artisan db:seed --class=ExampleSitesSeeder        # RESETS demo data: deletes all clients + non-admin users, then builds "/" and one example
                                                      # site per business kind (all 15 section types), 1 owner each + 1 platform assistant; password 12345
php artisan serve                                     # then open /login
php artisan test
```

Front-end code: `resources/js` (`Pages/` = one Vue page per dashboard screen, `Layouts/AppLayout.vue` = the sidebar shell,
`Components/ui` = buttons/cards/dialogs/chart, `Components/schema` = the generic section editor built from each type's field schema).
Controllers return `Inertia::render('Manage/Sections/Index', [...])`; routes are available in Vue as `route('name', params)` (Ziggy).
**Gotcha:** a page's own prop must never be called `client` — that name is the shared "client being managed" prop (see `HandleInertiaRequests`).

## How it fits together

| Piece | Where |
|---|---|
| Tenant = `clients` row (full business profile), one shared DB, `client_id` on tenant tables | `app/Models/Client.php`, `Concerns/BelongsToClient.php` |
| Sections = `sections` table: `type`, `config` (json), `content` (json, per-language), `position`, `is_enabled` | `app/Models/Section.php` |
| A section type = one class + one Blade view | `app/Sections/Types/*`, `resources/views/sections/*` |
| One field schema drives validation, defaults, the admin form and stored JSON | `app/Sections/Schema.php` |
| Public page render + per-client cache (busts on every edit) | `app/Sections/Renderer.php` |
| Dashboards: shared props (user, flash, current client, sidebar menu + unread badges) | `app/Http/Middleware/HandleInertiaRequests.php` |
| Dashboard pages (Vue) and the schema-driven section editor | `resources/js/Pages`, `resources/js/Components/schema` |
| Contact form, leads inbox, email alert to the owner — **core, always on** (kept in one folder) | `app/Modules/Forms/` |
| Optional feature modules (none yet) | `config/platform.php` → `modules` |
| What exists at all | `config/platform.php` |

Platform staff: **admin** (`users.is_super_admin`) and **assistant** (`users.is_assistant`), never both (`User::setPlatformRole`).
Both work in `/admin` and on every client's site with owner rights; deleting or suspending a client and the platform-team page
(`/admin/admins`) are admin-only (the `super` route middleware). The last admin can never be removed or downgraded.

Roles: **client members** (`client_user` pivot, area `/manage/{slug}`),
each either an **owner** or an **editor**. Every member can add/edit/show/hide/reorder sections and read leads; deleting sections
or leads, the business profile and the Team page (add/remove editors) are owner-only. Owners themselves are managed from `/admin`.
The whole table lives in `app/Support/Access.php` (Gates used as `can:` route middleware, shared with Vue as `auth.membership.can`).

## Change or remove things

- **Add a section type:** copy a class in `app/Sections/Types`, add a view in `resources/views/sections`, list it in `config/platform.php`.
- **Remove a section type everywhere:** delete its line in `config/platform.php`, then `php artisan sections:prune {type}`.
- **Remove a type for one client only:** Admin → client → Settings → untick it. Nothing is deleted.
- **The contact form is core**, so there is no on/off switch. A client that doesn't want one simply has no contact-form section
  (or you untick the type for them, which also stops the form accepting posts).
- **Add an optional module:** copy `app/Modules/Forms/`'s layout (provider, routes, views, migration) and list its provider under `modules` in `config/platform.php`.
- **New starting layout:** add an entry under `presets` in `config/platform.php`.
- **New kind of business on the "new client" page:** add an entry under `business_kinds` (label, icon, which preset it starts with);
  a new icon name also goes in `kindIcon` (`resources/js/Composables/icons.js`) and the label in `lang/ar.json`.

## Leads and email

Every contact-form submission is stored first (never lost), then emailed to the client's **owner(s)** after the visitor already got
their response. A mail failure is logged and never breaks the form. The email's Reply-To is the visitor, so replying goes straight to them.
If a client has no owner, the business email from the profile is used. Editors are not emailed.
Set real mail settings in `.env` (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_FROM_ADDRESS`, …); the default `log` mailer only writes emails to `storage/logs/laravel.log`.
The dashboard has a **Leads** page (list, unread filter, open, mark read/unread, mark all read, delete) with an unread count in the sidebar.

## Uploaded images

Files live in `public/uploads/clients/{id}/`. They are deleted when their section is deleted, and when an image is replaced or removed
inside a section. Cleanup is strictly limited to that client's own folder. Known gaps: files of a section whose *type* was removed from the
platform are left alone (the type's schema is needed to find them), and an upload in a form that then fails validation can leave one file behind.

## Deliberately not included (add only if you need them)

Draft/publish and revisions, billing, rich-text/HTML editor (all text is escaped, so client content can
never inject scripts), audit log. **There are no custom domains by decision:** every client lives at `/{slug}`.

## The marketing site and the design

- **`/` is the platform's own marketing site.** It is an ordinary client (slug `erp`, set by `PLATFORM_HOME_CLIENT`), built from the
  same sections and edited in the dashboard like any customer (Admin → Clients → the `erp` row → Manage). Its own slug URL redirects to `/`.
  If no such client exists, `/` just redirects to the login page. Its header has a "Log in" button — or, for a signed-in user, a "Dashboard · name" link to `/home` (swapped into the cached
  page per request in `SiteController`, which is why `/` alone has a session); contact-form leads go to its owner.
- **One design system for everything:** `public/css/site.css` styles the marketing site and every client site. A client's look is
  their brand color, font and logo. It is built with logical CSS properties, so Arabic/RTL mirrors correctly, and it is responsive.
  The header collapses to a hamburger menu by itself whenever the links don't fit (`public/js/site.js`).
- **Sections (15 types):** hero (label, headline, buttons, ✓ points), numbers, text, cards/steps (with optional links), pricing plans,
  price list, gallery, reviews, comparison table, team, FAQ, opening hours, call to action, contact details, contact form. Each has an optional short
  **menu label** per language.
- **Words the page itself prints** (menu, footer, form labels) live in `config/ui.php` for all 10 languages; a test fails if a language is missing one.
- Client footers carry a small "Made with …" link to `/` (`PLATFORM_POWERED_BY=false` turns it off).
- **Local development:** with `APP_ENV=local` and `APP_DEBUG=true`, public pages are rebuilt on every request so template/CSS edits show
  immediately. In production they are cached per client and version — clear the cache (`php artisan cache:clear`) after deploying template changes.

## Dashboard language (English / Arabic, RTL)

The **dashboard** (`/admin`, `/manage/{slug}`) has its own language switch, independent of a client's public-site
languages. It is session-based (`dashboard_locale`, `SetDashboardLocale` middleware) so it works before login too.
Arabic renders the whole dashboard right-to-left: `<html dir>` is set server-side on first paint (`resources/views/app.blade.php`)
and kept in sync on every Inertia visit (`resources/js/app.js`, listening on the `success`/`navigate` router events —
**`navigate` alone is not reliable for this**, it did not fire on some visits in testing; `success` is what actually carries every page).

- **Translations:** `resources/js/i18n/{en,ar}.js` (dashboard chrome — nav, buttons, headings, empty states) via the `useI18n()`
  composable; `lang/ar.json` + `lang/ar/validation.php` (server flash messages and form-validation errors, via `__()`).
  Adding a UI string: add the English text as the key in both files.
- **Layout:** Tailwind's logical-property utilities (`ps-*`/`pe-*`, `start-*`/`end-*`, `ms-*`/`me-*`) instead of `pl-*`/`right-*`/etc.,
  so most of the mirroring is automatic. Directional icons (back arrows, pagination chevrons) get an explicit `rtl:rotate-180`.
  **Known Tailwind v4 gotcha:** an `rtl:` variant class beats a responsive (`lg:`) class in the cascade regardless of viewport width —
  scope the responsive one first, e.g. `max-lg:rtl:translate-x-full`, not `lg:translate-x-0` + bare `rtl:translate-x-full`
  (see the sidebar slide-in in `AppLayout.vue` for a worked example; it hid the sidebar off-screen on desktop until fixed).
- **Section-type field labels are translated too** (e.g. "Headline" → "العنوان الرئيسي", "Background" → "الخلفية"): every
  `SectionType` class wraps its `label()`, `description()`, field labels and select-option labels in `__()`, resolved against
  `lang/ar.json` at request time (same session-based dashboard locale as the rest of the UI — no separate switch). Adding a new
  field: wrap its `'label' => '...'` in `__('...')` and add the English string as a key to `lang/ar.json`. A translated string
  is required for every field across all 15 types and the client-creation presets/business-type suggestions — nothing here is
  meant to silently stay English; if a key is missing, `__()` just falls back to showing the English text.

## Deployment notes

Point the web server's document root at `public/`. Set `APP_ENV=production`, `APP_DEBUG=false`, a real `APP_URL`.
Public pages are cookie-free and cached; use a shared cache (Redis) if you run more than one server.
