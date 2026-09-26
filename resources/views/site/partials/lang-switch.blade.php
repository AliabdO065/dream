{{-- Globe icon + dropdown of languages. A native <details>, so it works with zero JS; site.js only
     makes it behave like a normal dropdown (closes on outside click / Escape). Shared by every header. --}}
@if(count($locales) > 1)
    <details class="lang-switch" data-lang-switch>
        <summary aria-label="{{ $tr('language') }}">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
            <span>{{ strtoupper($locale) }}</span>
        </summary>
        <div class="lang-menu">
            @foreach($locales as $l)
                <a href="?lang={{ $l }}" class="{{ $l === $locale ? 'on' : '' }}" hreflang="{{ $l }}">{{ $languageNames[$l] ?? strtoupper($l) }}</a>
            @endforeach
        </div>
    </details>
@endif
