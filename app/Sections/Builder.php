<?php

namespace App\Sections;

use App\Models\Client;
use App\Models\Section;
use Illuminate\Support\Str;
use InvalidArgumentException;

/** Creates sections (used by the admin, presets and seeders). */
class Builder
{
    public function __construct(private Registry $registry)
    {
    }

    public function add(Client $client, string $typeKey, ?string $name = null, array $plain = [], array $config = [], ?string $anchor = null): Section
    {
        $type = $this->registry->get($typeKey) ?? throw new InvalidArgumentException("Unknown section type [$typeKey]");
        $locale = $client->default_locale;
        $name = $name ?: $type->label();

        return $client->sections()->create([
            'type' => $typeKey,
            'name' => $name,
            'anchor' => $this->uniqueAnchor($client, $anchor ?: $name),
            'position' => ((int) $client->sections()->max('position')) + 1,
            'is_enabled' => true,
            'content' => Schema::localize($type->fields(), array_replace($type->defaultContent(), $plain), $locale),
            'config' => Schema::localize($type->configFields(), $config, $locale),
        ]);
    }

    /** Add a translation for another locale on top of existing content. */
    public function translate(Section $section, string $locale, array $plain, array $config = []): Section
    {
        $type = $this->registry->get($section->type);
        $section->content = array_replace_recursive($section->content ?? [], Schema::localizeTranslatable($type->fields(), $plain, $locale));
        $section->config = array_replace_recursive($section->config ?? [], Schema::localizeTranslatable($type->configFields(), $config, $locale));
        $section->save();

        return $section;
    }

    /** Seed a new client from a preset in config/platform.php. Types the client can't use are skipped. */
    public function preset(Client $client, string $preset): void
    {
        $allowed = $client->allowedSectionTypes();
        foreach (config("platform.presets.$preset.sections", []) as $s) {
            if (isset($allowed[$s['type']])) {
                $this->add($client, $s['type'], $s['name'] ?? null, $s['content'] ?? [], $s['config'] ?? []);
            }
        }
    }

    public function uniqueAnchor(Client $client, string $wanted, ?int $ignoreId = null): string
    {
        $base = Str::slug($wanted) ?: 'section';
        $anchor = $base;
        $i = 2;
        while ($client->sections()->where('anchor', $anchor)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $anchor = $base . '-' . $i++;
        }

        return $anchor;
    }
}
