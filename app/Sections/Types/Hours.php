<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

class Hours extends SectionType
{
    public function key(): string { return 'hours'; }
    public function label(): string { return __('Opening hours'); }
    public function description(): string { return __('A list of days/times, e.g. opening hours or a schedule.'); }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading'), 'translatable' => true],
            'rows' => ['type' => 'list', 'label' => __('Rows'), 'max' => 14, 'fields' => [
                'label' => ['type' => 'text', 'label' => __('Day / label'), 'translatable' => true],
                'hours' => ['type' => 'text', 'label' => __('Hours'), 'max' => 60],
            ]],
            'note' => ['type' => 'textarea', 'label' => __('Note'), 'translatable' => true],
        ];
    }

    public function defaultContent(): array
    {
        return [
            'title' => 'Opening hours',
            'rows' => [['label' => 'Monday – Friday', 'hours' => '9:00 – 17:00']],
        ];
    }
}
