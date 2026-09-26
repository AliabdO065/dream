<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

/** A menu, price list, class timetable, package list — anything with names and prices. */
class PricedList extends SectionType
{
    public function key(): string { return 'priced_list'; }
    public function label(): string { return __('Price list'); }
    public function description(): string { return __('Names with descriptions and prices: a menu, packages, treatments, classes.'); }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading'), 'translatable' => true],
            'subtitle' => ['type' => 'textarea', 'label' => __('Intro text'), 'translatable' => true],
            'items' => ['type' => 'list', 'label' => __('Entries'), 'max' => 100, 'fields' => [
                'group' => ['type' => 'text', 'label' => __('Group heading (optional, shown when it changes)'), 'translatable' => true],
                'name' => ['type' => 'text', 'label' => __('Name'), 'translatable' => true],
                'description' => ['type' => 'text', 'label' => __('Description'), 'translatable' => true],
                'price' => ['type' => 'text', 'label' => __('Price (e.g. €12.50)'), 'max' => 40],
            ]],
        ];
    }

    public function defaultContent(): array
    {
        return [
            'title' => 'Prices',
            'items' => [['name' => 'First entry', 'description' => 'Short description', 'price' => '10']],
        ];
    }
}
