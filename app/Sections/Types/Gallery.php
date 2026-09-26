<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

class Gallery extends SectionType
{
    public function key(): string { return 'gallery'; }
    public function label(): string { return __('Image gallery'); }
    public function description(): string { return __('A grid of photos with optional captions.'); }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading'), 'translatable' => true],
            'images' => ['type' => 'list', 'label' => __('Images'), 'max' => 40, 'fields' => [
                'image' => ['type' => 'image', 'label' => __('Image')],
                'caption' => ['type' => 'text', 'label' => __('Caption'), 'translatable' => true],
            ]],
        ];
    }

    public function defaultContent(): array
    {
        return ['title' => 'Gallery'];
    }
}
