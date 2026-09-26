<section id="{{ $anchor }}" class="sec bg-{{ $config['background'] }}">
    <div class="wrap">
        @include('sections._head')
        <div class="items items-{{ $config['variant'] }} items-n{{ count($c['items']) }}">
            @foreach($c['items'] as $item)
                <article class="item reveal">
                    @if($config['variant'] === 'numbered')<span class="num">{{ $loop->iteration }}</span>
                    @elseif($item['icon'])<span class="icon">{{ $item['icon'] }}</span>@endif
                    @if($item['image'])<img src="{{ $item['image'] }}" alt="" loading="lazy">@endif
                    @if($item['title'])<h3>{{ $item['title'] }}</h3>@endif
                    @if($item['text'])<p>{!! nl2br(e($item['text'])) !!}</p>@endif
                    @if($item['link'])<a class="item-link" href="{{ $item['link'] }}">{{ $item['link_label'] ?: '' }}</a>@endif
                </article>
            @endforeach
        </div>
    </div>
</section>
