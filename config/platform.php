<?php

use App\Sections\Types;

/*
|--------------------------------------------------------------------------
| Platform configuration — the single place that decides what exists.
|--------------------------------------------------------------------------
| Remove a line from `section_types` or `modules` and that capability is gone
| for every client, without touching anything else.
*/
return [

    // Slugs that can never be a client site (they would collide with platform URLs).
    'reserved_slugs' => [
        'admin', 'manage', 'login', 'logout', 'register', 'password', 'home', 'api', 'up',
        'storage', 'uploads', 'build', 'assets', 'css', 'js', 'images', 'img', 'static',
        'public', 'vendor', 'robots', 'sitemap', 'favicon', 'health', 'app', 'dashboard',
        'settings', 'support', 'help', 'www', 'mail', 'index', 'submit',
    ],

    // The client whose site is shown at "/" — the platform's own marketing page. Edited in the dashboard
    // like any other client. If no such client exists, "/" simply sends visitors to the login page.
    'home_client' => env('PLATFORM_HOME_CLIENT', 'erp'),


    // Small "Made with …" link in the footer of every client site (not on the home client itself).
    'powered_by' => env('PLATFORM_POWERED_BY', true),

    // Core section types. Each is one class + one view in resources/views/sections.
    'section_types' => [
        Types\Hero::class,
        Types\Stats::class,
        Types\TextBlock::class,
        Types\ItemList::class,
        Types\Plans::class,
        Types\PricedList::class,
        Types\Gallery::class,
        Types\Reviews::class,
        Types\ComparisonTable::class,
        Types\TeamMembers::class,
        Types\Faq::class,
        Types\Hours::class,
        Types\Cta::class,
        Types\ContactInfo::class,
    ],

    // Optional feature modules: key => service provider. Delete a line to remove that module.
    // (Forms / leads are NOT listed here: they are core and always on — see AppServiceProvider.)
    // A client can switch a module off with features[key] = false; Client::feature() answers that.
    'modules' => [],

    // Languages offered in the client profile (code => native name).
    'languages' => [
        'en' => 'English', 'de' => 'Deutsch', 'ar' => 'العربية', 'fr' => 'Français',
        'es' => 'Español', 'it' => 'Italiano', 'tr' => 'Türkçe', 'nl' => 'Nederlands',
        'pl' => 'Polski', 'ru' => 'Русский',
    ],

    // Kinds of business offered as big tiles when a client is created: picking one fills the free-text
    // "business type" and picks the starting layout, so nobody has to think about presets.
    // icon = a lucide icon name (resources/js/Composables/icons.js). The labels double as the profile's type suggestions.
    'business_kinds' => [
        'trades' => ['label' => 'Trades & home services', 'icon' => 'wrench', 'preset' => 'service'],
        'restaurant' => ['label' => 'Restaurant / café', 'icon' => 'utensils', 'preset' => 'venue'],
        'health' => ['label' => 'Health & wellness', 'icon' => 'stethoscope', 'preset' => 'service'],
        'legal' => ['label' => 'Legal / consulting', 'icon' => 'scale', 'preset' => 'professional'],
        'beauty' => ['label' => 'Beauty & salon', 'icon' => 'scissors', 'preset' => 'service'],
        'fitness' => ['label' => 'Fitness / studio', 'icon' => 'dumbbell', 'preset' => 'service'],
        'creative' => ['label' => 'Photography / creative', 'icon' => 'camera', 'preset' => 'portfolio'],
        'retail' => ['label' => 'Retail / shop', 'icon' => 'shopping-bag', 'preset' => 'portfolio'],
        'education' => ['label' => 'Education / coaching', 'icon' => 'graduation-cap', 'preset' => 'professional'],
        'nonprofit' => ['label' => 'Non-profit / community', 'icon' => 'heart-handshake', 'preset' => 'professional'],
        'other' => ['label' => 'Something else', 'icon' => 'sparkles', 'preset' => 'blank'],
    ],

    // Starter layouts applied when a client is created. Purely optional: "blank" is always available.
    'presets' => [
        'blank' => ['label' => 'Blank (hero + contact)', 'sections' => [
            ['type' => 'hero', 'name' => 'Hero', 'config' => ['background' => 'brand']],
            ['type' => 'contact_info', 'name' => 'Contact', 'config' => ['show_in_nav' => true]],
        ]],
        'service' => ['label' => 'Service business', 'sections' => [
            ['type' => 'hero', 'name' => 'Hero', 'config' => ['variant' => 'split', 'background' => 'brand']],
            ['type' => 'list', 'name' => 'Services', 'config' => ['show_in_nav' => true]],
            ['type' => 'list', 'name' => 'How it works', 'config' => ['variant' => 'numbered', 'background' => 'alt', 'show_in_nav' => true],
                'content' => ['title' => 'How it works']],
            ['type' => 'reviews', 'name' => 'Reviews', 'config' => ['show_in_nav' => true]],
            ['type' => 'faq', 'name' => 'FAQ', 'config' => ['background' => 'alt', 'show_in_nav' => true]],
            ['type' => 'contact_form', 'name' => 'Contact form', 'config' => ['show_in_nav' => true]],
            ['type' => 'contact_info', 'name' => 'Contact details'],
        ]],
        'venue' => ['label' => 'Restaurant / venue', 'sections' => [
            ['type' => 'hero', 'name' => 'Hero', 'config' => ['background' => 'brand']],
            ['type' => 'text', 'name' => 'About', 'config' => ['show_in_nav' => true]],
            ['type' => 'priced_list', 'name' => 'Menu', 'config' => ['background' => 'alt', 'show_in_nav' => true],
                'content' => ['title' => 'Menu']],
            ['type' => 'gallery', 'name' => 'Gallery', 'config' => ['show_in_nav' => true]],
            ['type' => 'hours', 'name' => 'Opening hours', 'config' => ['background' => 'alt']],
            ['type' => 'contact_info', 'name' => 'Contact', 'config' => ['show_in_nav' => true]],
        ]],
        'portfolio' => ['label' => 'Portfolio / shop window', 'sections' => [
            ['type' => 'hero', 'name' => 'Hero', 'config' => ['background' => 'brand']],
            ['type' => 'text', 'name' => 'About', 'config' => ['show_in_nav' => true]],
            ['type' => 'gallery', 'name' => 'Gallery', 'config' => ['background' => 'alt', 'show_in_nav' => true]],
            ['type' => 'reviews', 'name' => 'Reviews', 'config' => ['show_in_nav' => true]],
            ['type' => 'contact_form', 'name' => 'Contact form', 'config' => ['background' => 'alt', 'show_in_nav' => true]],
            ['type' => 'contact_info', 'name' => 'Contact details'],
        ]],
        'professional' => ['label' => 'Professional / consultant', 'sections' => [
            ['type' => 'hero', 'name' => 'Hero', 'config' => ['background' => 'brand']],
            ['type' => 'text', 'name' => 'About', 'config' => ['show_in_nav' => true]],
            ['type' => 'list', 'name' => 'Areas of work', 'config' => ['background' => 'alt', 'show_in_nav' => true],
                'content' => ['title' => 'Areas of work']],
            ['type' => 'faq', 'name' => 'FAQ', 'config' => ['show_in_nav' => true]],
            ['type' => 'contact_form', 'name' => 'Contact form', 'config' => ['show_in_nav' => true]],
        ]],
    ],
];
