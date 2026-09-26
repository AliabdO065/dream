<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

class Stats extends SectionType
{
    public function key(): string { return 'stats'; }
    public function label(): string { return __('Numbers / highlights'); }
    public function description(): string { return __('A row of big numbers with labels: years in business, customers served, languages…'); }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading (optional)'), 'translatable' => true],
            'subtitle' => ['type' => 'textarea', 'label' => __('Intro text (optional)'), 'translatable' => true],
            'items' => ['type' => 'list', 'label' => __('Numbers'), 'max' => 8, 'fields' => [
                'value' => ['type' => 'text', 'label' => __('Number (e.g. 25+ or 24/7)'), 'max' => 20],
                'label' => ['type' => 'text', 'label' => __('Label'), 'translatable' => true],
            ]],
        ];
    }

    public function defaultContent(): array
    {
        return ['items' => [
            ['value' => '10+', 'label' => 'Years of experience'],
            ['value' => '24/7', 'label' => 'Always available'],
            ['value' => '100%', 'label' => 'Satisfaction'],
        ]];
    }
}
