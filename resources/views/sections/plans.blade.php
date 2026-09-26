<section id="{{ $anchor }}" class="sec bg-{{ $config['background'] }}">
    <div class="wrap">
        @include('sections._head')
        <div class="plans plans-n{{ count($c['items']) }}">
            @foreach($c['items'] as $p)
                <article class="plan reveal {{ $p['highlighted'] ? 'hot' : '' }}">
                    @if($p['highlighted'])<span class="badge-hot">★</span>@endif
                    <h3>{{ $p['name'] }}</h3>
                    @if($p['description'])<p class="desc">{{ $p['description'] }}</p>@endif
                    @if($p['price'])<div class="price">{{ $p['price'] }}@if($p['period']) <small>{{ $p['period'] }}</small>@endif</div>@endif
                    @php $features = array_values(array_filter(preg_split('/\R/u', $p['features']) ?: [], fn ($f) => trim($f) !== '')); @endphp
                    @if($features)<ul>@foreach($features as $f)<li>{{ trim($f) }}</li>@endforeach</ul>@endif
                    @if($p['button_label'] && $p['button_url'])<a class="btn {{ $p['highlighted'] ? '' : 'btn-ghost' }}" href="{{ $p['button_url'] }}">{{ $p['button_label'] }}</a>@endif
                </article>
            @endforeach
        </div>
    </div>
</section>
