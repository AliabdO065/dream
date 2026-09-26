@php
    $ui = fn ($k) => config("ui.$k.$locale") ?? config("ui.$k.en");
    // A section that is only being previewed has no id yet — the form is inert there, but must still render.
    $sid = $section->id ?: 0;
@endphp
<section id="{{ $anchor }}" class="sec sec-compact bg-{{ $config['background'] }}">
    <div class="wrap narrow">
        @include('sections._head')

        {{-- The hooks public/js/site.js relies on (data-thanks / data-form, ?sent=<id>) live on the two elements below. --}}
        <div class="form-card reveal">
            <div class="form-thanks" data-thanks="{{ $sid }}" hidden>{{ $c['success_message'] }}</div>
            <form method="POST" action="{{ route('site.submit', [$client, $sid]) }}" class="lead-form" data-form="{{ $sid }}">
                <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                @if($c['show_phone'])
                    <div class="form-row">
                        <label>{{ $ui('name') }}<input type="text" name="name" required maxlength="120"></label>
                        <label>{{ $ui('phone') }}<input type="tel" name="phone" maxlength="50" dir="auto"></label>
                    </div>
                @else
                    <label>{{ $ui('name') }}<input type="text" name="name" required maxlength="120"></label>
                @endif
                @if($c['show_email'])<label>{{ $ui('email') }}<input type="email" name="email" maxlength="190" dir="auto"></label>@endif
                @if($c['show_message'])<label>{{ $ui('message') }}<textarea name="message" rows="2" maxlength="3000"></textarea></label>@endif
                <button type="submit" class="btn btn-lg">{{ $c['button_label'] ?: $ui('send') }}</button>
            </form>
        </div>
    </div>
</section>
