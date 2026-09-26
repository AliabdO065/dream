<?php

namespace App\Models;

use App\Sections\Registry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Client extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'content_version'];

    protected $casts = [
        'locales' => 'array',
        'theme' => 'array',
        'features' => 'array',
        'profile_extra' => 'array',
    ];

    protected static function booted(): void
    {
        // Any change to the client invalidates its cached public page.
        static::saved(fn (Client $c) => $c->bumpVersion());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('position')->orderBy('id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** The platform's own marketing site is just a client, served at "/" instead of "/{slug}". */
    public function isHome(): bool
    {
        return $this->slug === config('platform.home_client');
    }

    /** The public address of this client's site. Clients live at /{slug} — there are no custom domains. */
    public function publicUrl(): string
    {
        return $this->isHome() ? url('/') : route('site.show', $this);
    }

    /** The uploaded logo's URL, or null (the dashboard then shows the name's first letter). */
    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('uploads/' . $this->logo_path) : null;
    }

    /** Locales this client publishes in; the default locale is always first. */
    public function siteLocales(): array
    {
        $default = $this->default_locale ?: 'en';

        return array_values(array_unique([$default, ...($this->locales ?? [])]));
    }

    /** Is a feature module switched on for this client (and installed on the platform)? */
    public function feature(string $key): bool
    {
        return array_key_exists($key, config('platform.modules', []))
            && (bool) (($this->features ?? [])[$key] ?? true);
    }

    /** Who is told about new leads: the owners; if there is none, the business email from the profile. */
    public function ownerEmails(): array
    {
        $emails = $this->users()->wherePivot('role', 'owner')->pluck('users.email')->all();

        return $emails ?: array_filter([$this->email]);
    }

    /** Section types this client may use: registered, on the client's allow-list, module enabled. */
    public function allowedSectionTypes(): array
    {
        $allow = ($this->features ?? [])['types'] ?? null;

        return array_filter(app(Registry::class)->all(), function ($type) use ($allow) {
            return ($allow === null || in_array($type->key(), $allow, true))
                && ($type->module() === null || $this->feature($type->module()));
        });
    }

    public function bumpVersion(): void
    {
        if ($this->exists) {
            DB::table('clients')->where('id', $this->id)->increment('content_version');
            $this->content_version = ($this->content_version ?? 1) + 1;
        }
    }
}
