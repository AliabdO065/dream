<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

class Faq extends SectionType
{
    public function key(): string { return 'faq'; }
    public function label(): string { return __('FAQ'); }
    public function description(): string { return __('Questions and answers that expand when clicked.'); }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading'), 'translatable' => true],
            'items' => ['type' => 'list', 'label' => __('Questions'), 'max' => 50, 'fields' => [
                'question' => ['type' => 'text', 'label' => __('Question'), 'translatable' => true],
                'answer' => ['type' => 'textarea', 'label' => __('Answer'), 'translatable' => true],
            ]],
        ];
    }

    public function defaultContent(): array
    {
        return [
            'title' => 'Frequently asked questions',
            'items' => [['question' => 'Your first question?', 'answer' => 'Your answer.']],
        ];
    }
}
