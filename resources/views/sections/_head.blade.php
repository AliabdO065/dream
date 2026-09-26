{{-- Shared section heading. Uses $c['title'] and $c['subtitle'] when present. --}}
@if(($c['title'] ?? '') !== '' || ($c['subtitle'] ?? '') !== '')
    <header class="sec-head reveal">
        @if(($c['title'] ?? '') !== '')<h2>{{ $c['title'] }}</h2>@endif
        @if(($c['subtitle'] ?? '') !== '')<p class="lead">{!! nl2br(e($c['subtitle'])) !!}</p>@endif
    </header>
@endif
