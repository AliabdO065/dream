<?php

namespace App\Sections;

/**
 * A section type = one small class + one Blade view. Adding a type means adding both;
 * removing one means deleting both (and running `php artisan sections:prune {type}`).
 */
abstract class SectionType
{
    abstract public function key(): string;

    abstract public function label(): string;

    /** Field schema for the content (see Schema). */
    abstract public function fields(): array;

    public function description(): string
    {
        return '';
    }

    /** Key of the feature module this type belongs to, or null for core types. */
    public function module(): ?string
    {
        return null;
    }

    public function view(): string
    {
        return 'sections.' . $this->key();
    }

    /** Plain default values (English placeholders) used when a section is first added. */
    public function defaultContent(): array
    {
        return [];
    }

    /** Extra type-specific settings (e.g. layout variant). */
    protected function settings(): array
    {
        return [];
    }

    /** Layout/behaviour fields stored in sections.config. */
    public function configFields(): array
    {
        return $this->settings() + [
            'background' => [
                'type' => 'select', 'label' => __('Background'),
                'options' => ['light' => __('Light'), 'alt' => __('Soft grey'), 'dark' => __('Dark'), 'brand' => __('Brand color')],
            ],
            'show_in_nav' => ['type' => 'checkbox', 'label' => __('Show in the navigation menu')],
            'nav_label' => ['type' => 'text', 'label' => __('Menu label (optional — a short word; defaults to the heading)'), 'translatable' => true, 'max' => 40],
        ];
    }
}
