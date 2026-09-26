{{-- nova header: a floating glass pill navbar, sitting with a visible gap from the very top of the
     page rather than flush against it — the sticky wrapper stays transparent, the ".bar" itself is the
     glass pill (rounded, blurred, bordered). Hook classes/includes are unchanged. --}}
<header class="site-header">
    <div class="wrap bar">
        <a class="logo" href="#top">
            @if($client->logo_path)<img src="{{ asset('uploads/' . $client->logo_path) }}" alt="">@endif
            <span>{{ $client->name }}</span>
        </a>
        <div class="menu-tools">
            @include('site.partials.lang-switch')
            <button class="menu-btn" type="button" aria-label="Menu" aria-expanded="false" data-menu-btn><span></span><span></span><span></span></button>
        </div>
        <nav class="nav">
            @foreach($nav as $item)<a href="#{{ $item['anchor'] }}">{{ $item['label'] }}</a>@endforeach
            @include('site.partials.lang-switch')
            @include('site.partials.nav-cta')
        </nav>
    </div>
</header>
