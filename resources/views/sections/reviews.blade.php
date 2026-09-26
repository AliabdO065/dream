<section id="{{ $anchor }}" class="sec bg-{{ $config['background'] }}">
    <div class="wrap">
        @include('sections._head')
        <div class="items items-cards">
            @foreach($c['items'] as $r)
                <blockquote class="item review reveal">
                    <span class="stars" aria-label="{{ $r['rating'] }} / 5">{{ str_repeat('★', (int) $r['rating']) }}<span class="off">{{ str_repeat('★', 5 - (int) $r['rating']) }}</span></span>
                    <p>{!! nl2br(e($r['text'])) !!}</p>
                    @if($r['author'])<footer>— {{ $r['author'] }}</footer>@endif
                </blockquote>
            @endforeach
        </div>
    </div>
</section>
