<section id="{{ $anchor }}" class="sec bg-{{ $config['background'] }}">
    <div class="wrap narrow">
        @include('sections._head')
        @php $lastGroup = null; @endphp
        <div class="priced reveal">
            @foreach($c['items'] as $item)
                @if($item['group'] && $item['group'] !== $lastGroup)
                    <h3 class="group">{{ $item['group'] }}</h3>
                    @php $lastGroup = $item['group']; @endphp
                @endif
                <div class="price-row">
                    <div>
                        <strong>{{ $item['name'] }}</strong>
                        @if($item['description'])<span class="muted">{{ $item['description'] }}</span>@endif
                    </div>
                    @if($item['price'])<span class="price">{{ $item['price'] }}</span>@endif
                </div>
            @endforeach
        </div>
    </div>
</section>
