<section id="{{ $anchor }}" class="sec bg-{{ $config['background'] }}">
    <div class="wrap">
        @include('sections._head')
        <div class="items items-cards">
            @foreach($c['items'] as $m)
                <article class="item team-card reveal">
                    @if($m['photo'])<img class="avatar" src="{{ $m['photo'] }}" alt="" loading="lazy">@endif
                    @if($m['name'])<h3>{{ $m['name'] }}</h3>@endif
                    @if($m['role'])<p class="role">{{ $m['role'] }}</p>@endif
                    @if($m['bio'])<p>{!! nl2br(e($m['bio'])) !!}</p>@endif
                </article>
            @endforeach
        </div>
    </div>
</section>
