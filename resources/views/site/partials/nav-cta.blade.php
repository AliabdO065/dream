{{-- The header's call-to-action link: "Log in" on the platform's own home site, else the client's phone.
     The page HTML is cached for every visitor, so the login link sits between markers that
     SiteController swaps for the signed-in user's dashboard link (site.partials.nav-account) per request. --}}
@if($home)
    <!--nav-account--><a class="btn btn-sm nav-cta" href="{{ route('login') }}">{{ $tr('login') }}</a><!--/nav-account-->
@elseif($client->phone)
    <a class="btn btn-sm nav-cta" href="tel:{{ preg_replace('/[^0-9+]/', '', $client->phone) }}" dir="ltr">📞 {{ $client->phone }}</a>
@endif
