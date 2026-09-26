<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * The owner's own team page (route guarded by the `team.manage` ability): see everyone, add and remove EDITORS.
 * Owners are never added, changed or removed here — that stays with the platform admin (Admin\ClientController),
 * so an owner can't lock a co-owner out, and nobody can promote themselves.
 */
class TeamController extends Controller
{
    public function index(Client $client)
    {
        return Inertia::render('Manage/Team', [
            'members' => $client->users()->orderBy('name')->get()->map(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->pivot->role,
            ])->all(),
        ]);
    }

    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if ($user && $client->users()->whereKey($user->id)->exists()) {
            return back()->withErrors(['email' => __(':email is already on the team.', ['email' => $user->email])]);
        }

        $password = null;
        if (! $user) {
            $password = Str::password(12, symbols: false);
            $user = User::create(['name' => ($data['name'] ?? null) ?: Str::before($data['email'], '@'), 'email' => $data['email'], 'password' => $password]);
        }
        $client->users()->attach($user->id, ['role' => 'editor']);

        return back()->with('status', $password
            ? __('Added :email. Temporary password (shown once): :password', ['email' => $user->email, 'password' => $password])
            : __('Added existing user :email.', ['email' => $user->email]));
    }

    public function destroy(Client $client, int $user)
    {
        $member = $client->users()->whereKey($user)->firstOrFail();
        abort_if($member->pivot->role !== 'editor', 403, 'Owners are managed by the platform administrator.');

        $client->users()->detach($member->id);

        return back()->with('status', __('Member removed.'));
    }
}
