<section id="{{ $anchor }}" class="sec bg-{{ $config['background'] }}">
    <div class="wrap">
        @include('sections._head')
        <div class="gallery">
            @foreach($c['images'] as $img)
                @if($img['image'])
                    <figure class="reveal">
                        <img src="{{ $img['image'] }}" alt="{{ $img['caption'] }}" loading="lazy">
                        @if($img['caption'])<figcaption>{{ $img['caption'] }}</figcaption>@endif
                    </figure>
                @endif
            @endforeach
        </div>
    </div>
</section>
