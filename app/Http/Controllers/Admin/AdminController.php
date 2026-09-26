<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** The platform's own staff: who is an admin, who is an assistant (admins only — see routes/web.php). */
class AdminController extends Controller
{
    public function index(Request $request)
    {
        // Tabs: "all", "staff", or one site (client slug) — only sites that have people.
        $sites = Client::has('users')->withCount('users')->orderBy('name')->get(['id', 'slug', 'name', 'logo_path']);
        $tab = (string) $request->query('site', 'all');
        $site = $sites->firstWhere('slug', $tab);
        if ($tab !== 'staff' && ! $site) {
            $tab = 'all';
        }

        // The membership used for sorting: in the chosen site, or else the (alphabetically first) one.
        $membership = fn (string $column) => DB::table('client_user')
            ->join('clients', 'clients.id', '=', 'client_user.client_id')
            ->whereColumn('client_user.user_id', 'users.id')->whereNull('clients.deleted_at')
            ->when($site, fn ($q) => $q->where('clients.id', $site->id))
            ->orderBy('clients.name')->limit(1)->select($column);

        // Staff first, then everyone grouped by site, its owners before its editors. People who belong to no client come last.
        $users = User::with(['clients' => fn ($q) => $q->select('clients.id', 'clients.slug', 'clients.name')->orderBy('clients.name')])
            ->when($tab === 'staff', fn ($q) => $q->where(fn ($q) => $q->where('is_super_admin', true)->orWhere('is_assistant', true)))
            ->when($site, fn ($q) => $q->whereHas('clients', fn ($q) => $q->whereKey($site->id)))
            ->select('users.*')
            ->addSelect(['site_name' => $membership('clients.name'), 'site_role' => $membership('client_user.role')])
            ->orderByDesc('is_super_admin')->orderByDesc('is_assistant')
            ->orderByRaw('site_name is null')->orderBy('site_name')
            ->orderByDesc('site_role') // 'owner' sorts after 'editor', so descending puts owners first
            ->orderBy('name')->paginate(30)->withQueryString();

        return Inertia::render('Admin/Admins/Index', [
            'tab' => $tab,
            'sites' => $sites->map(fn ($c) => ['slug' => $c->slug, 'name' => $c->name, 'logo' => $c->logoUrl(), 'count' => $c->users_count])->all(),
            'counts' => ['all' => User::count(), 'staff' => User::where('is_super_admin', true)->orWhere('is_assistant', true)->count()],
            'users' => $users->through(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
                'role' => $u->platformRole(),
                // where this person is an owner / editor (a per-client role, set on the client's page)
                'memberships' => $u->clients->map(fn ($c) => ['id' => $c->id, 'slug' => $c->slug, 'name' => $c->name, 'role' => $c->pivot->role])->all(),
                'is_me' => $u->id === $request->user()->id,
            ]),
        ]);
    }

    /** Give someone a platform role: upgrades an existing user, or creates a new one. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', Rule::in(User::PLATFORM_ROLES)],
        ]);
        $role = $data['role'] ?? 'admin';

        $password = null;
        $user = User::where('email', $data['email'])->first();
        if ($user && $error = $this->lastAdminError($user, $role)) {
            return back()->withErrors(['email' => $error]);
        }
        if (! $user) {
            $password = Str::password(12, symbols: false);
            $user = User::create(['name' => ($data['name'] ?? null) ?: Str::before($data['email'], '@'), 'email' => $data['email'], 'password' => $password]);
        }
        $user->setPlatformRole($role);

        return back()->with('status', $password
            ? __(':email is now :role. Temporary password (shown once): :password', ['email' => $user->email, 'role' => $this->roleName($role), 'password' => $password])
            : __(':email is now :role.', ['email' => $user->email, 'role' => $this->roleName($role)]));
    }

    /** Change a user's platform role: admin, assistant, or none (back to an ordinary client user). */
    public function role(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['nullable', Rule::in(User::PLATFORM_ROLES)]]);
        $role = $data['role'] ?? null;

        if ($error = $this->lastAdminError($user, $role)) {
            return back()->withErrors(['admin' => $error]);
        }
        $user->setPlatformRole($role);

        return back()->with('status', $role
            ? __(':email is now :role.', ['email' => $user->email, 'role' => $this->roleName($role)])
            : __(':email no longer has platform access.', ['email' => $user->email]));
    }

    /**
     * Give someone a new random password and show it ONCE to the admin, to pass on. Passwords are only ever
     * stored as a one-way hash, so an existing one can't be looked up — only replaced.
     */
    public function resetPassword(Request $request, User $user)
    {
        $password = Str::password(12, symbols: false);
        $user->update(['password' => $password]);
        Log::notice('Password reset by an admin', ['by' => $request->user()->email, 'for' => $user->email]);

        return back()->with('password', ['email' => $user->email, 'password' => $password]);
    }

    /** The platform can never be left without an admin. */
    private function lastAdminError(User $user, ?string $newRole): ?string
    {
        return $user->is_super_admin && $newRole !== 'admin' && User::where('is_super_admin', true)->count() <= 1
            ? __('There must always be at least one super admin.')
            : null;
    }

    private function roleName(string $role): string
    {
        return $role === 'admin' ? __('an admin') : __('an assistant');
    }
}
