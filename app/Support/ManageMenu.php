<?php

namespace App\Support;

use App\Models\Client;
use Closure;

/** Sidebar entries of the client dashboard. Core adds a few; feature modules add their own. */
class ManageMenu
{
    private static array $items = [];

    /**
     * @param  string|null  $feature   hide the entry when this feature is off for the client (null = always shown)
     * @param  Closure|null $badge     fn (Client): int — a count shown next to the label when > 0
     * @param  string       $icon      a lucide icon name in kebab-case (e.g. "inbox")
     * @param  int          $order     lower comes first
     * @param  string|null  $ability   hide the entry from people without this ability (see Access::ABILITIES)
     */
    public static function add(string $label, string $route, ?string $feature = null, ?Closure $badge = null, string $icon = 'circle', int $order = 50, ?string $ability = null): void
    {
        self::$items[$route] = compact('label', 'route', 'feature', 'badge', 'icon', 'order', 'ability');
    }

    /**
     * Entries visible for a client, in display order (a module's entry disappears when its feature is off,
     * an ability-bound one disappears for whoever lacks it). $can is Access::map() for the viewing user.
     */
    public static function for(Client $client, array $can): array
    {
        $items = array_filter(self::$items, fn ($i) => ($i['feature'] === null || $client->feature($i['feature']))
            && ($i['ability'] === null || ($can[$i['ability']] ?? false)));
        usort($items, fn ($a, $b) => $a['order'] <=> $b['order']);

        return array_values($items);
    }
}
