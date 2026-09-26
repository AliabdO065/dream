<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Sections\Builder;
use App\Sections\Registry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::query()
            ->withCount('sections')
            ->with('users')
            ->when($request->query('q'), function ($q, $s) {
                $s = str_replace(['%', '_'], ['\\%', '\\_'], $s); // literal search, like the leads page
                $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('slug', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%")->orWhere('business_type', 'like', "%$s%"));
            })
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByRaw('slug = ? desc', [config('platform.home_client')]) // the platform's own site is pinned on top
            ->latest('id')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clients->through(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'slug' => $c->slug, 'business_type' => $c->business_type,
                'status' => $c->status, 'sections' => $c->sections_count, 'is_home' => $c->isHome(), 'url' => $c->publicUrl(), 'logo' => $c->logoUrl(),
                'owners' => $c->users->where('pivot.role', 'owner')->pluck('email')->values()->all(),
                'created' => $c->created_at?->translatedFormat('j M Y'),
            ]),
            'filters' => ['q' => (string) $request->query('q', ''), 'status' => (string) $request->query('status', '')],
        ]);
    }

    public function create()
    {
        $types = app(Registry::class)->all();

        return Inertia::render('Admin/Clients/Create', [
            'kinds' => collect(config('platform.business_kinds'))->map(fn ($k, $key) => [
                'value' => $key, 'label' => __($k['label']), 'icon' => $k['icon'], 'preset' => $k['preset'],
            ])->values()->all(),
            // what each starting layout puts on the page — drawn in the live preview
            'presets' => collect(config('platform.presets'))->map(fn ($p) => collect($p['sections'])
                ->filter(fn ($s) => isset($types[$s['type']]))
                ->map(fn ($s) => ['type' => $s['type'], 'label' => $types[$s['type']]->label()])->values()->all()
            )->all(),
            'languages' => collect(config('platform.languages'))->map(fn ($n, $c) => ['value' => $c, 'label' => $n])->values()->all(),
            'colors' => self::BRAND_COLORS,
            'baseUrl' => rtrim(url('/'), '/'),
        ]);
    }

    /** Swatches offered for a new client's brand color (any other color can still be typed in). */
    private const BRAND_COLORS = ['#2563eb', '#0f766e', '#16a34a', '#ca8a04', '#ea580c', '#dc2626', '#db2777', '#7c3aed', '#0f172a'];

    /**
     * Live help for the "new client" form (JSON):
     *   ?name=مطعم النور&locale=ar  → a free address made from the name (Arabic etc. is transliterated): {slug: "mtaam-alnor"}
     *   ?slug=my-cafe               → is this address free?  {slug, available, message}
     */
    public function slug(Request $request)
    {
        if ($request->filled('name')) {
            return response()->json(['slug' => $this->freeSlug((string) $request->query('name'), (string) $request->query('locale', 'en')), 'available' => true, 'message' => null]);
        }

        $slug = (string) $request->query('slug', '');
        $validator = validator(['slug' => $slug], ['slug' => $this->slugRules()]);

        return response()->json(['slug' => $slug, 'available' => ! $validator->fails(), 'message' => $validator->errors()->first('slug') ?: null]);
    }

    public function store(Request $request, Builder $builder)
    {
        $kinds = config('platform.business_kinds');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'kind' => ['nullable', Rule::in(array_keys($kinds))],
            'slug' => ['nullable', ...array_slice($this->slugRules(), 1)], // empty = made from the name
            'business_type' => ['nullable', 'string', 'max:100'],
            'preset' => ['nullable', Rule::in(array_keys(config('platform.presets')))],
            'default_locale' => ['required', Rule::in(array_keys(config('platform.languages')))],
            'status' => ['nullable', Rule::in(['active', 'draft'])],
            'brand' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'owner_email' => ['required', 'email', 'max:190'],
            'owner_name' => ['nullable', 'string', 'max:120'],
        ]);

        // A picked kind of business fills in what wasn't given explicitly.
        $kind = isset($data['kind']) ? $kinds[$data['kind']] : null;
        $data['preset'] ??= $kind['preset'] ?? 'blank';
        $data['business_type'] ??= ($kind && $data['kind'] !== 'other') ? __($kind['label']) : null;
        $data['slug'] ??= $this->freeSlug($data['name'], $data['default_locale']);

        $password = null;
        $client = DB::transaction(function () use ($data, $builder, &$password) {
            $client = Client::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'business_type' => $data['business_type'],
                'status' => $data['status'] ?? 'draft', // starter content is placeholder text: hidden until someone says it's ready
                'default_locale' => $data['default_locale'],
                'locales' => [$data['default_locale']],
                'theme' => ['brand' => strtolower($data['brand'] ?? '#2563eb'), 'accent' => '#f59e0b', 'font' => 'sans'],
            ]);

            $owner = User::where('email', $data['owner_email'])->first();
            if (! $owner) {
                $password = Str::password(12, symbols: false);
                $owner = User::create(['name' => ($data['owner_name'] ?? null) ?: $data['name'], 'email' => $data['owner_email'], 'password' => $password]);
            }
            $client->users()->attach($owner->id, ['role' => 'owner']);
            $builder->preset($client, $data['preset']);

            return $client;
        });

        return redirect()->route('admin.clients.index')->with('created', [
            'name' => $client->name,
            'slug' => $client->slug,
            'url' => $client->publicUrl(),
            'published' => $client->isActive(),
            'login_url' => route('login'),
            'email' => $data['owner_email'],
            'password' => $password,
        ]);
    }

    public function edit(Client $client)
    {
        $client->load('users');

        return Inertia::render('Admin/Clients/Edit', [
            // NOT 'client': that name is the shared prop for the client being managed
            'target' => [
                'name' => $client->name, 'slug' => $client->slug, 'business_type' => $client->business_type,
                'status' => $client->status, 'url' => $client->publicUrl(), 'is_home' => $client->isHome(),
            ],
            'types' => collect(app(Registry::class)->all())->map(fn ($t) => ['key' => $t->key(), 'label' => $t->label(), 'description' => $t->description(), 'module' => $t->module()])->values()->all(),
            'allowed' => ($client->features ?? [])['types'] ?? null, // null = every type
            'modules' => collect(config('platform.modules'))->map(fn ($p, $k) => ['key' => $k, 'enabled' => $client->feature($k)])->values()->all(),
            'members' => $client->users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->pivot->role])->all(),
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => $this->slugRules($client),
            'business_type' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'draft', 'suspended'])],
        ]);

        // Suspending a client (and lifting a suspension) is an admin decision, not an assistant's.
        $suspension = $data['status'] !== $client->status && in_array('suspended', [$data['status'], $client->status], true);
        if ($suspension && ! $request->user()->is_super_admin) {
            return back()->withErrors(['status' => __('Only an admin can suspend a client or lift a suspension.')]);
        }

        // "/" finds the marketing site by this slug (config platform.home_client): renaming it would silently empty "/".
        if ($client->isHome() && $data['slug'] !== $client->slug) {
            return back()->withErrors(['slug' => __('The address of the platform\'s own site cannot be changed.')]);
        }

        // Feature flags: which modules are on, and which section types this client may use.
        $features = [];
        foreach (array_keys(config('platform.modules')) as $module) {
            $features[$module] = $request->boolean("modules.$module");
        }
        $allTypes = array_keys(app(Registry::class)->all());
        $picked = array_values(array_intersect($allTypes, (array) $request->input('types', [])));
        $features['types'] = count($picked) === count($allTypes) ? null : $picked;

        $client->update($data + ['features' => $features]);

        // the slug may have changed: go to the new address of this page
        return redirect()->route('admin.clients.edit', $client)->with('status', __('Client saved.'));
    }

    public function destroy(Client $client)
    {
        if ($client->isHome()) {
            return back()->withErrors(['client' => __('The platform\'s own site cannot be deleted.')]);
        }

        $client->delete(); // soft delete: the slug stays reserved, data can be restored

        return redirect()->route('admin.clients.index')->with('status', __(':name was deleted.', ['name' => $client->name]));
    }

    public function addMember(Request $request, Client $client)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
            'role' => ['required', Rule::in(['owner', 'editor'])],
        ]);

        $password = null;
        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            $password = Str::password(12, symbols: false);
            $user = User::create(['name' => ($data['name'] ?? null) ?: Str::before($data['email'], '@'), 'email' => $data['email'], 'password' => $password]);
        }
        $client->users()->syncWithoutDetaching([$user->id => ['role' => $data['role']]]);

        return back()->with('status', $password
            ? __('Added :email. Temporary password (shown once): :password', ['email' => $user->email, 'password' => $password])
            : __('Added existing user :email.', ['email' => $user->email]));
    }

    public function removeMember(Client $client, User $user)
    {
        $client->users()->detach($user->id);

        return back()->with('status', __('Member removed.'));
    }

    private function slugRules(?Client $client = null): array
    {
        return [
            'required', 'string', 'max:60',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::notIn(config('platform.reserved_slugs')),
            Rule::unique('clients', 'slug')->ignore($client?->id),
        ];
    }

    /** An address made from a name that no client has (deleted ones included) and that isn't reserved: name, name-2, name-3… */
    private function freeSlug(string $name, string $locale): string
    {
        $base = trim(Str::limit(Str::slug($name, '-', $locale), 50, ''), '-') ?: 'site';
        $taken = fn ($s) => in_array($s, config('platform.reserved_slugs'), true) || Client::withTrashed()->where('slug', $s)->exists();

        $slug = $base;
        for ($i = 2; $taken($slug); $i++) {
            $slug = "$base-$i";
        }

        return $slug;
    }
}
