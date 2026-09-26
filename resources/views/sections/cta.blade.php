<section id="{{ $anchor }}" class="sec cta bg-{{ $config['background'] }}">
    <div class="wrap narrow center reveal">
        @if($c['title'])<h2>{{ $c['title'] }}</h2>@endif
        @if($c['text'])<p class="lead">{{ $c['text'] }}</p>@endif
        @if($c['button_label'] && $c['button_url'])<a class="btn btn-lg" href="{{ $c['button_url'] }}">{{ $c['button_label'] }}</a>@endif
    </div>
</section>
