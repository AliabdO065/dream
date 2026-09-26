<section id="{{ $anchor }}" class="sec bg-{{ $config['background'] }}">
    <div class="wrap narrow">
        @include('sections._head')
        <div class="faq">
            @foreach($c['items'] as $q)
                <details class="reveal">
                    <summary>{{ $q['question'] }}</summary>
                    <p>{!! nl2br(e($q['answer'])) !!}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
