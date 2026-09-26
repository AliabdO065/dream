{{-- nova footer: a big rounded gradient "ready to get started" panel sitting above the column grid,
     its own card with a large border-radius (styled in themes/nova.css as .nova-cta-panel). --}}
<footer class="site-footer">
    @if($client->tagline || $client->phone || $client->email)
        <div class="wrap nova-cta">
            <div class="nova-cta-panel">
                <div>
                    <h3>{{ $client->tagline ?: $tr('contact') }}</h3>
                    <p>{{ $client->name }}@if($addr) · {{ $addr }}@endif</p>
                </div>
                <div class="btns">
                    @if($client->phone)<a class="btn" href="tel:{{ preg_replace('/[^0-9+]/', '', $client->phone) }}" dir="ltr">📞 {{ $client->phone }}</a>@endif
                    @if($client->email)<a class="btn btn-ghost" href="mailto:{{ $client->email }}">{{ $client->email }}</a>@endif
                </div>
            </div>
        </div>
    @endif
    <div class="wrap footer-grid">
        <div>
            <strong class="f-brand">{{ $client->name }}</strong>
            @if($client->tagline)<p>{{ $client->tagline }}</p>@endif
        </div>
        @if($nav)
            <div><h4>{{ $tr('menu') }}</h4><ul>@foreach($nav as $item)<li><a href="#{{ $item['anchor'] }}">{{ $item['label'] }}</a></li>@endforeach</ul></div>
        @endif
        @if($client->email || $addr)
            <div><h4>{{ $tr('contact') }}</h4><ul>
                @if($client->email)<li><a href="mailto:{{ $client->email }}">{{ $client->email }}</a></li>@endif
                @if($addr)<li>{{ $addr }}</li>@endif
            </ul></div>
        @endif
    </div>
    <div class="wrap footer-bottom">
        <span>© {{ now()->year }} {{ $client->legal_name ?: $client->name }}@if($client->registration_no) · {{ $client->registration_no }}@endif @if($client->tax_id) · {{ $client->tax_id }}@endif</span>
        @if($poweredBy)<span>{{ $tr('made') }} <a href="{{ url('/') }}">{{ config('app.name') }}</a></span>@endif
    </div>
</footer>
