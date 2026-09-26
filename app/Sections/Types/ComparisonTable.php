<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

/** "Us vs anonymous/other providers" trust table — checkmarks in two columns. */
class ComparisonTable extends SectionType
{
    public function key(): string { return 'comparison_table'; }
    public function label(): string { return __('Comparison table'); }
    public function description(): string { return __('A trust table: you against generic competitors, feature by feature.'); }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading'), 'translatable' => true],
            'subtitle' => ['type' => 'textarea', 'label' => __('Intro text'), 'translatable' => true],
            'our_label' => ['type' => 'text', 'label' => __('Your column header'), 'translatable' => true, 'max' => 40],
            'competitor_label' => ['type' => 'text', 'label' => __('The other column header'), 'translatable' => true, 'max' => 40],
            'rows' => ['type' => 'list', 'label' => __('Rows'), 'max' => 12, 'fields' => [
                'feature' => ['type' => 'text', 'label' => __('Feature'), 'translatable' => true, 'max' => 120],
                'ours' => ['type' => 'checkbox', 'label' => __('You have this')],
                'competitor' => ['type' => 'checkbox', 'label' => __('They have this')],
            ]],
        ];
    }

    public function defaultContent(): array
    {
        return [
            'title' => 'Why choose us',
            'our_label' => 'Us',
            'competitor_label' => 'Others',
            'rows' => [
                ['feature' => 'Vetted, qualified staff', 'ours' => true, 'competitor' => false],
                ['feature' => 'Price agreed before work starts', 'ours' => true, 'competitor' => false],
                ['feature' => 'One dedicated contact person', 'ours' => true, 'competitor' => false],
                ['feature' => 'Public reviews you can check', 'ours' => true, 'competitor' => true],
            ],
        ];
    }
}
