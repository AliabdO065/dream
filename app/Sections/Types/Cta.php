<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

class Cta extends SectionType
{
    public function key(): string { return 'cta'; }
    public function label(): string { return __('Call to action'); }
    public function description(): string { return __('A short message with one button — call now, book, buy.'); }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading'), 'translatable' => true],
            'text' => ['type' => 'text', 'label' => __('Short text'), 'translatable' => true],
            'button_label' => ['type' => 'text', 'label' => __('Button text'), 'translatable' => true],
            'button_url' => ['type' => 'url', 'label' => __('Button link')],
        ];
    }

    public function defaultContent(): array
    {
        return ['title' => 'Ready to get started?', 'button_label' => 'Get in touch', 'button_url' => '#contact'];
    }
}
