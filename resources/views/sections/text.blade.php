<section id="{{ $anchor }}" class="sec bg-{{ $config['background'] }}">
    <div class="wrap text-block {{ $c['image'] ? $config['variant'] : 'left' }}">
        <div class="reveal">
            @if($c['title'])<h2>{{ $c['title'] }}</h2>@endif
            @if($c['body'])<p>{!! nl2br(e($c['body'])) !!}</p>@endif
        </div>
        @if($c['image'])<img class="reveal" src="{{ $c['image'] }}" alt="" loading="lazy">@endif
    </div>
</section>
