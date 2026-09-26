<?php

namespace App\Modules\Forms;

use App\Sections\SectionType;

class ContactFormType extends SectionType
{
    public function key(): string { return 'contact_form'; }
    public function label(): string { return __('Contact form'); }
    public function description(): string { return __('A form visitors fill in; requests appear in the Submissions inbox.'); }
    public function view(): string { return 'forms::section'; }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading'), 'translatable' => true],
            'subtitle' => ['type' => 'textarea', 'label' => __('Intro text'), 'translatable' => true],
            'show_phone' => ['type' => 'checkbox', 'label' => __('Ask for a phone number')],
            'show_email' => ['type' => 'checkbox', 'label' => __('Ask for an email address')],
            'show_message' => ['type' => 'checkbox', 'label' => __('Ask for a message')],
            'button_label' => ['type' => 'text', 'label' => __('Button text'), 'translatable' => true],
            'success_message' => ['type' => 'text', 'label' => __('Thank-you message'), 'translatable' => true],
        ];
    }

    public function defaultContent(): array
    {
        return [
            'title' => 'Get in touch',
            'subtitle' => 'Leave your details and we will get back to you.',
            'show_phone' => true, 'show_email' => true, 'show_message' => true,
            'button_label' => 'Send request',
            'success_message' => 'Thank you! We have received your request.',
        ];
    }
}
