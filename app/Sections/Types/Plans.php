<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

/** Pricing / packages as cards — one of them can be highlighted. */
class Plans extends SectionType
{
    public function key(): string { return 'plans'; }
    public function label(): string { return __('Pricing plans'); }
    public function description(): string { return __('Package cards with a price, a feature list and a button. One can be highlighted.'); }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading'), 'translatable' => true],
            'subtitle' => ['type' => 'textarea', 'label' => __('Intro text'), 'translatable' => true],
            'items' => ['type' => 'list', 'label' => __('Plans'), 'max' => 6, 'fields' => [
                'name' => ['type' => 'text', 'label' => __('Plan name'), 'translatable' => true],
                'price' => ['type' => 'text', 'label' => __('Price (e.g. €29)'), 'max' => 30],
                'period' => ['type' => 'text', 'label' => __('Period (e.g. / month)'), 'translatable' => true, 'max' => 30],
                'description' => ['type' => 'text', 'label' => __('Short description'), 'translatable' => true],
                'features' => ['type' => 'textarea', 'label' => __('Features (one per line)'), 'translatable' => true],
                'button_label' => ['type' => 'text', 'label' => __('Button text'), 'translatable' => true],
                'button_url' => ['type' => 'url', 'label' => __('Button link')],
                'highlighted' => ['type' => 'checkbox', 'label' => __('Highlight this plan ("most popular")')],
            ]],
        ];
    }

    public function defaultContent(): array
    {
        return [
            'title' => 'Simple pricing',
            'items' => [
                ['name' => 'Basic', 'price' => '10', 'period' => '/ month', 'features' => "First feature\nSecond feature", 'button_label' => 'Choose', 'button_url' => '#contact'],
                ['name' => 'Pro', 'price' => '25', 'period' => '/ month', 'features' => "Everything in Basic\nThird feature", 'button_label' => 'Choose', 'button_url' => '#contact', 'highlighted' => true],
            ],
        ];
    }
}
