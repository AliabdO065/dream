<?php

namespace App\Sections;

use App\Models\Client;

/**
 * Is a client's site really available in each of its languages?
 *
 * A language is only offered to visitors once EVERY text on the live page exists in it — otherwise the page
 * would silently fall back to the main language here and there and show a mix (Italian menu, Arabic page).
 * The dashboard uses the same numbers to show what is still missing.
 */
class Translations
{
    /**
     * Per extra language (the main one is always complete):
     *   ['it' => ['complete' => false, 'sections' => 10, 'missing' => [['id' => 3, 'name' => 'Hero', 'texts' => 4], …]], …]
     */
    public static function status(Client $client): array
    {
        $types = $client->allowedSectionTypes();
        $sections = $client->sections()->where('is_enabled', true)->get()->filter(fn ($s) => isset($types[$s->type]));

        $out = [];
        foreach (array_diff($client->siteLocales(), [$client->default_locale]) as $locale) {
            $missing = [];
            foreach ($sections as $s) {
                $type = $types[$s->type];
                [, $a] = Schema::coverage($type->fields(), $s->content ?? [], $locale);
                [, $b] = Schema::coverage($type->configFields(), $s->config ?? [], $locale);
                if ($a + $b > 0) {
                    $missing[] = ['id' => $s->id, 'name' => $s->name, 'texts' => $a + $b];
                }
            }
            $out[$locale] = ['complete' => ! $missing, 'sections' => $sections->count(), 'missing' => $missing];
        }

        return $out;
    }

    /** The languages visitors can choose: the main one, plus every fully translated one (in the client's order). */
    public static function publicLocales(Client $client): array
    {
        $status = self::status($client);

        return array_values(array_filter($client->siteLocales(), fn ($l) => $l === $client->default_locale || ($status[$l]['complete'] ?? false)));
    }
}
