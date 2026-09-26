<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

class TextBlock extends SectionType
{
    public function key(): string { return 'text'; }
    public function label(): string { return __('Text'); }
    public function description(): string { return __('A heading and a paragraph — about us, a story, any free text.'); }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading'), 'translatable' => true],
            'body' => ['type' => 'textarea', 'label' => __('Text'), 'translatable' => true],
            'image' => ['type' => 'image', 'label' => __('Image (optional)')],
        ];
    }

    protected function settings(): array
    {
        return ['variant' => ['type' => 'select', 'label' => __('Image position'), 'options' => ['left' => __('Text only'), 'image-right' => __('Image on the right'), 'image-left' => __('Image on the left')]]];
    }

    public function defaultContent(): array
    {
        return ['title' => 'About us', 'body' => 'Tell your story here.'];
    }
}
