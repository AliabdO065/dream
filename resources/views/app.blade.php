@php
    // Set here (not just client-side) so the very first paint already has the right direction — no flash of the wrong layout.
    $dashboardLocale = in_array(session('dashboard_locale'), \App\Http\Middleware\SetDashboardLocale::LOCALES, true) ? session('dashboard_locale') : 'en';
    $dashboardDir = $dashboardLocale === 'ar' ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ $dashboardLocale }}" dir="{{ $dashboardDir }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow">
    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full bg-slate-50 font-sans text-slate-700 antialiased" dir="{{ $dashboardDir }}">
    @inertia
</body>
</html>
