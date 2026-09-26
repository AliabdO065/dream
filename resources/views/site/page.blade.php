@php
    $rtl = in_array($locale, ['ar', 'he', 'fa', 'ur'], true);
    $home = $client->isHome();
    $base = $client->publicUrl();
    // The few words the page itself prints (menu, footer) — see config/ui.php. Section content is the client's own.
    $tr = fn ($k) => config("ui.$k.$locale") ?? config("ui.$k.en");
    $addr = collect([$client->address_line1, $client->address_line2, trim($client->postal_code . ' ' . $client->city), $client->region, $client->country])->filter()->join(', ');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}" data-theme="nova">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $client->name }}@if($client->tagline) — {{ $client->tagline }}@endif</title>
    @if($client->tagline)<meta name="description" content="{{ $client->tagline }}">@endif
    <meta property="og:title" content="{{ $client->name }}">
    @if($client->tagline)<meta property="og:description" content="{{ $client->tagline }}">@endif
    <meta property="og:type" content="website">
    <meta name="theme-color" content="{{ $brand }}">
    <link rel="canonical" href="{{ $base }}">
    @if(count($locales) > 1)
        @foreach($locales as $l)<link rel="alternate" hreflang="{{ $l }}" href="{{ $base }}{{ $l === $client->default_locale ? '' : '?lang=' . $l }}">@endforeach
    @endif
    @if($client->logo_path)<link rel="icon" href="{{ asset('uploads/' . $client->logo_path) }}">@endif
    <script>document.documentElement.classList.add('js')</script>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
    <link rel="stylesheet" href="{{ asset('css/themes/nova.css') }}">
    <style>:root{--brand:{{ $brand }};--on-brand:{{ $onBrand }};--accent:{{ $accent }};--on-accent:{{ $onAccent }};--font:{!! $font !!}}</style>
    @php
        $ld = array_filter([
            '@context' => 'https://schema.org', '@type' => 'LocalBusiness',
            'name' => $client->name, 'description' => $client->tagline, 'telephone' => $client->phone,
            'email' => $client->email, 'url' => $base,
            'address' => array_filter([
                '@type' => 'PostalAddress', 'streetAddress' => $client->address_line1,
                'postalCode' => $client->postal_code, 'addressLocality' => $client->city, 'addressCountry' => $client->country,
            ]),
        ]);
    @endphp
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
</head>
<body>
@include('site.headers.nova')

<main id="top">
    @foreach($blocks as $block){!! $block !!}@endforeach
</main>

@include('site.footers.nova')
<script src="{{ asset('js/site.js') }}"></script>
</body>
</html>
