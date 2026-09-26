<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

class Reviews extends SectionType
{
    public function key(): string { return 'reviews'; }
    public function label(): string { return __('Customer reviews'); }
    public function description(): string { return __('Testimonials with a star rating.'); }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading'), 'translatable' => true],
            'items' => ['type' => 'list', 'label' => __('Reviews'), 'max' => 30, 'fields' => [
                'author' => ['type' => 'text', 'label' => __('Name')],
                'rating' => ['type' => 'select', 'label' => __('Stars'), 'options' => ['5' => '5', '4' => '4', '3' => '3', '2' => '2', '1' => '1']],
                'text' => ['type' => 'textarea', 'label' => __('Review'), 'translatable' => true],
            ]],
        ];
    }

    public function defaultContent(): array
    {
        return ['title' => 'What customers say'];
    }
}
