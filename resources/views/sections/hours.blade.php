<section id="{{ $anchor }}" class="sec bg-{{ $config['background'] }}">
    <div class="wrap narrow">
        @include('sections._head')
        <div class="priced reveal">
            @foreach($c['rows'] as $row)
                <div class="price-row"><strong>{{ $row['label'] }}</strong><span class="price">{{ $row['hours'] }}</span></div>
            @endforeach
        </div>
        @if($c['note'])<p class="muted center" style="margin-top:1rem">{!! nl2br(e($c['note'])) !!}</p>@endif
    </div>
</section>
