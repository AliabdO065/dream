<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

class Hero extends SectionType
{
    public function key(): string { return 'hero'; }
    public function label(): string { return __('Hero banner'); }
    public function description(): string { return __('Big headline at the top with an optional image and buttons.'); }

    public function fields(): array
    {
        return [
            'eyebrow' => ['type' => 'text', 'label' => __('Small label above the headline (optional)'), 'translatable' => true, 'max' => 80],
            'headline' => ['type' => 'text', 'label' => __('Headline'), 'translatable' => true],
            'subheadline' => ['type' => 'textarea', 'label' => __('Sub-headline'), 'translatable' => true],
            'image' => ['type' => 'image', 'label' => __('Image')],
            'slides' => ['type' => 'list', 'label' => __('Slideshow images (optional — add 2 or more to auto-rotate; replaces the single image above)'), 'max' => 6, 'fields' => [
                'image' => ['type' => 'image', 'label' => __('Image')],
            ]],
            'cta_label' => ['type' => 'text', 'label' => __('Main button text'), 'translatable' => true],
            'cta_url' => ['type' => 'url', 'label' => __('Main button link (e.g. #contact or tel:+49...)')],
            'secondary_label' => ['type' => 'text', 'label' => __('Second button text'), 'translatable' => true],
            'secondary_url' => ['type' => 'url', 'label' => __('Second button link')],
            'highlights' => ['type' => 'list', 'label' => __('Short reassurance points (✓ …)'), 'max' => 6, 'fields' => [
                'text' => ['type' => 'text', 'label' => __('Point'), 'translatable' => true, 'max' => 80],
            ]],
        ];
    }

    protected function settings(): array
    {
        return ['variant' => ['type' => 'select', 'label' => __('Layout'), 'options' => ['center' => __('Centered'), 'split' => __('Text + image side by side')]]];
    }

    public function defaultContent(): array
    {
        return ['headline' => 'Your headline goes here', 'subheadline' => 'Say in one or two sentences what you do and who it is for.'];
    }
}
