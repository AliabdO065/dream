@php
    $addr = collect([$client->address_line1, $client->address_line2, trim($client->postal_code . ' ' . $client->city), $client->region, $client->country])->filter()->join(', ');
@endphp
<section id="{{ $anchor }}" class="sec bg-{{ $config['background'] }}">
    <div class="wrap">
        @include('sections._head', ['c' => ['title' => $c['title'], 'subtitle' => $c['text']]])
        <ul class="contact-cards">
            @if($client->phone)<li class="reveal"><span class="ci">📞</span><a href="tel:{{ preg_replace('/[^0-9+]/', '', $client->phone) }}" dir="ltr">{{ $client->phone }}</a></li>@endif
            @if($client->email)<li class="reveal"><span class="ci">✉️</span><a href="mailto:{{ $client->email }}">{{ $client->email }}</a></li>@endif
            @if($client->website)<li class="reveal"><span class="ci">🌐</span><a href="{{ $client->website }}" rel="noopener">{{ preg_replace('~^https?://~', '', $client->website) }}</a></li>@endif
            @if($addr)<li class="reveal"><span class="ci">📍</span><span>{{ $addr }}</span></li>@endif
        </ul>
    </div>
</section>
