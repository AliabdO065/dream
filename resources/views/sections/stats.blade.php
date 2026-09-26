<section id="{{ $anchor }}" class="sec bg-{{ $config['background'] }}">
    <div class="wrap">
        @include('sections._head')
        <div class="stats">
            @foreach($c['items'] as $s)
                <div class="stat reveal"><b>{{ $s['value'] }}</b><span>{{ $s['label'] }}</span></div>
            @endforeach
        </div>
    </div>
</section>
