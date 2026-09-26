{{-- Replaces the "Log in" button on the home site when someone is signed in. --}}
<a class="btn btn-sm nav-cta nav-account" href="{{ route('home') }}" title="{{ $user->name }}">
    <span class="nav-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
    <span>{{ $label }}</span>
    <span class="nav-account-name">· {{ \Illuminate\Support\Str::before(trim($user->name), ' ') ?: $user->name }}</span>
</a>
