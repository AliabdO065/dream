<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // is_super_admin / is_assistant are deliberately NOT fillable: they are only ever set explicitly (setPlatformRole).
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    /** Platform roles, strongest first. null = an ordinary client user. */
    public const PLATFORM_ROLES = ['admin', 'assistant'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_assistant' => 'boolean',
        ];
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class)->withPivot('role')->withTimestamps();
    }

    /** 'admin', 'assistant' or null. */
    public function platformRole(): ?string
    {
        return $this->is_super_admin ? 'admin' : ($this->is_assistant ? 'assistant' : null);
    }

    /** Platform staff (admin or assistant) work in /admin and on every client's site. */
    public function isStaff(): bool
    {
        return $this->platformRole() !== null;
    }

    /** Admin or assistant, never both. Saves. */
    public function setPlatformRole(?string $role): void
    {
        $this->forceFill(['is_super_admin' => $role === 'admin', 'is_assistant' => $role === 'assistant'])->save();
    }

    public function canManage(Client $client): bool
    {
        return $this->isStaff() || $this->clients()->whereKey($client->id)->exists();
    }

    /** This user's role on that client ('owner'/'editor'), or null if they aren't a member (e.g. a super admin visiting). */
    public function roleFor(Client $client): ?string
    {
        return $this->clients()->whereKey($client->id)->first()?->pivot->role;
    }
}
