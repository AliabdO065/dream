<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

/** Services, features, "why us", how-it-works steps — one flexible list. */
class ItemList extends SectionType
{
    public function key(): string { return 'list'; }
    public function label(): string { return __('List of items'); }
    public function description(): string { return __('Cards or numbered steps: services, features, benefits, how it works.'); }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading'), 'translatable' => true],
            'subtitle' => ['type' => 'textarea', 'label' => __('Intro text'), 'translatable' => true],
            'items' => ['type' => 'list', 'label' => __('Items'), 'max' => 24, 'fields' => [
                'icon' => ['type' => 'text', 'label' => __('Icon or emoji (optional)'), 'max' => 8],
                'image' => ['type' => 'image', 'label' => __('Image (optional)')],
                'title' => ['type' => 'text', 'label' => __('Title'), 'translatable' => true],
                'text' => ['type' => 'textarea', 'label' => __('Description'), 'translatable' => true],
                'link' => ['type' => 'url', 'label' => __('Link (optional)')],
                'link_label' => ['type' => 'text', 'label' => __('Link text (optional)'), 'translatable' => true, 'max' => 60],
            ]],
        ];
    }

    protected function settings(): array
    {
        return ['variant' => ['type' => 'select', 'label' => __('Item layout'), 'options' => ['cards' => __('Cards'), 'numbered' => __('Numbered steps'), 'plain' => __('Plain list')]]];
    }

    public function defaultContent(): array
    {
        return [
            'title' => 'What we offer',
            'items' => [
                ['icon' => '★', 'title' => 'First item', 'text' => 'Describe it in a sentence.'],
                ['icon' => '★', 'title' => 'Second item', 'text' => 'Describe it in a sentence.'],
                ['icon' => '★', 'title' => 'Third item', 'text' => 'Describe it in a sentence.'],
            ],
        ];
    }
}
