<?php

namespace App\Support;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Who may do what inside a client's dashboard — the ONE place that decides it.
 *
 * Every member (owner or editor) can open the dashboard, add/edit/show/hide/reorder sections and read leads.
 * The abilities below are the exceptions: each lists the roles that have it. Platform staff (admins and
 * assistants) have all of them on every client. (Platform-level limits for assistants are the `super` routes.)
 * Each ability is a Gate (routes use `can:<ability>,client`) and is shared with Vue as `auth.membership.can`.
 */
final class Access
{
    public const ABILITIES = [
        'sections.delete' => ['owner'], // deleting also deletes the section's images; editors can switch it off instead
        'leads.delete' => ['owner'],
        'profile.edit' => ['owner'],    // business details, branding, languages, logo
        'team.manage' => ['owner'],     // add / remove editors
    ];

    public static function allows(User $user, Client $client, string $ability): bool
    {
        return $user->isStaff() || in_array($user->roleFor($client), self::ABILITIES[$ability] ?? [], true);
    }

    /** ['sections.delete' => true, ...] for this user on this client (one role lookup for all of them). */
    public static function map(User $user, Client $client): array
    {
        $staff = $user->isStaff();
        $role = $staff ? null : $user->roleFor($client);

        return array_map(fn ($roles) => $staff || in_array($role, $roles, true), self::ABILITIES);
    }

    public static function defineGates(): void
    {
        foreach (array_keys(self::ABILITIES) as $ability) {
            Gate::define($ability, fn (User $user, Client $client) => self::allows($user, $client, $ability));
        }
    }
}
