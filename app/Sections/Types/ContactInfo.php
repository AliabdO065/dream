<?php

namespace App\Sections\Types;

use App\Sections\SectionType;

/** Shows the phone/email/address from the client's profile, so it is entered once. */
class ContactInfo extends SectionType
{
    public function key(): string { return 'contact_info'; }
    public function label(): string { return __('Contact details'); }
    public function description(): string { return __('Phone, email and address taken from the business profile.'); }

    public function fields(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => __('Heading'), 'translatable' => true],
            'text' => ['type' => 'textarea', 'label' => __('Extra text'), 'translatable' => true],
        ];
    }

    public function defaultContent(): array
    {
        return ['title' => 'Contact'];
    }
}
