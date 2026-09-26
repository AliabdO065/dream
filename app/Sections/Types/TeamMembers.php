<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

class TeamMembers extends SectionType
{
    public function key(): string { return 'team'; }
    public function label(): string { return __('Team'); }
    public function description(): string { return __('Photos, names and roles of the people behind the business.'); }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading'), 'translatable' => true],
            'subtitle' => ['type' => 'textarea', 'label' => __('Intro text'), 'translatable' => true],
            'items' => ['type' => 'list', 'label' => __('People'), 'max' => 12, 'fields' => [
                'photo' => ['type' => 'image', 'label' => __('Photo')],
                'name' => ['type' => 'text', 'label' => __('Name'), 'max' => 80],
                'role' => ['type' => 'text', 'label' => __('Role'), 'translatable' => true, 'max' => 80],
                'bio' => ['type' => 'textarea', 'label' => __('Short bio (optional)'), 'translatable' => true, 'max' => 400],
            ]],
        ];
    }

    public function defaultContent(): array
    {
        return [
            'title' => 'Meet the team',
            'items' => [
                ['name' => 'Full name', 'role' => 'Role'],
            ],
        ];
    }
}
