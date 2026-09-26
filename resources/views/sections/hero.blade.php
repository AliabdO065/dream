@php
    $points = array_values(array_filter($c['highlights'], fn ($h) => $h['text'] !== ''));
    $slides = array_values(array_filter($c['slides'], fn ($s) => $s['image'] !== ''));
    $n = count($slides);
@endphp
<section id="{{ $anchor }}" class="sec hero hero-{{ $config['variant'] }} bg-{{ $config['background'] }}">
    <div class="wrap hero-grid">
        <div class="hero-text reveal">
            @if($c['eyebrow'])<span class="eyebrow">{{ $c['eyebrow'] }}</span>@endif
            <h1>{{ $c['headline'] }}</h1>
            @if($c['subheadline'])<p class="lead">{!! nl2br(e($c['subheadline'])) !!}</p>@endif
            <div class="btns">
                @if($c['cta_label'] && $c['cta_url'])<a class="btn btn-lg" href="{{ $c['cta_url'] }}">{{ $c['cta_label'] }}</a>@endif
                @if($c['secondary_label'] && $c['secondary_url'])<a class="btn btn-lg btn-ghost" href="{{ $c['secondary_url'] }}">{{ $c['secondary_label'] }}</a>@endif
            </div>
            @if($points)
                <ul class="highlights">@foreach($points as $h)<li>{{ $h['text'] }}</li>@endforeach</ul>
            @endif
        </div>
        @if($n >= 2)
            <div class="hero-img hero-slider reveal">
                <div class="hero-slides">
                    @foreach($slides as $i => $s)
                        <img class="hero-slide" src="{{ $s['image'] }}" alt="" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" style="animation-delay:{{ $i * 5 }}s">
                    @endforeach
                </div>
            </div>
            @php $seg = 100 / $n; $fadeIn = round($seg * 0.12, 2); $holdEnd = round($seg * 0.88, 2); @endphp
            <style>
                #{{ $anchor }} .hero-slide{animation-name:hero-fade-{{ $anchor }};animation-duration:{{ $n * 5 }}s;animation-iteration-count:infinite;animation-timing-function:ease-in-out}
                @keyframes hero-fade-{{ $anchor }}{0%{opacity:0}{{ $fadeIn }}%{opacity:1}{{ $holdEnd }}%{opacity:1}{{ $seg }}%{opacity:0}100%{opacity:0}}
            </style>
        @elseif($n === 1)
            <div class="hero-img reveal"><img src="{{ $slides[0]['image'] }}" alt="" loading="eager"></div>
        @elseif($c['image'])
            <div class="hero-img reveal"><img src="{{ $c['image'] }}" alt="" loading="eager"></div>
        @endif
    </div>
</section>
