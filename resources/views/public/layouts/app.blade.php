@php($brand = app(\App\Services\AppSettingsService::class)->all())
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $brand['primary_color'] ?? '#BC0004' }}">
    <meta name="description" content="Salamaid — platform gotong royong dan donasi terpercaya.">
    <title>{{ $title ?? 'Salamaid' }} · {{ $brand['app_name'] ?? 'Salamaid' }}</title>
    @if (!empty($brand['favicon_url']))<link rel="icon" href="{{ $brand['favicon_url'] }}">@endif
    @vite(['resources/css/public.css', 'resources/css/public-detail.css', 'resources/css/public-checkout.css', 'resources/css/public-payment.css', 'resources/css/public-account.css', 'resources/js/public.js'])
</head>
<body style="--public-primary: {{ $brand['primary_color'] ?? '#BC0004' }}; --public-primary-soft: {{ $brand['primary_soft_color'] ?? '#FFF0EF' }};">
    <div class="public-shell">
        <header class="public-header">
            <a class="public-brand" href="{{ route('public.home') }}">
                @if (!empty($brand['logo_url']))<img class="public-brand__mark" src="{{ $brand['logo_url'] }}" alt="{{ $brand['app_name'] }}">@else<span class="public-brand__mark">{{ $brand['brand_icon'] ?? 'S' }}</span>@endif
                <span><strong class="public-brand__name">{{ $brand['app_name'] ?? 'Salamaid' }}</strong><small class="public-brand__tagline">Jembatan Kebaikan</small></span>
            </a>
            <label class="public-search" aria-label="Cari campaign"><span aria-hidden="true">⌕</span><input data-public-search type="search" placeholder="Cari campaign atau kebaikan..."></label>
            <a class="public-icon-button" href="{{ auth()->check() ? route('public.inbox') : route('public.login') }}" aria-label="Inbox">♡</a>
        </header>
        <main class="public-main">@yield('content')</main>
        <footer class="public-footer">© {{ date('Y') }} {{ $brand['app_name'] ?? 'Salamaid' }} · {{ $brand['contact_email'] ?: 'Jembatan kebaikan Indonesia' }}</footer>
    </div>
    <nav class="public-bottom-nav" aria-label="Navigasi publik">
        <a class="{{ request()->routeIs('public.home') ? 'is-active' : '' }}" href="{{ route('public.home') }}"><span class="public-bottom-nav__icon">⌂</span>Beranda</a>
        <a class="{{ request()->routeIs('public.program') ? 'is-active' : '' }}" href="{{ route('public.program') }}"><span class="public-bottom-nav__icon">▦</span>Program</a>
        <a href="{{ route('public.donations') }}"><span class="public-bottom-nav__icon">♡</span>Donasi Saya</a>
        <a class="{{ request()->routeIs('public.inbox') ? 'is-active' : '' }}" href="{{ route('public.inbox') }}"><span class="public-bottom-nav__icon">✉</span>Inbox</a>
        <a href="{{ route('public.account') }}"><span class="public-bottom-nav__icon">◉</span>Akun</a>
    </nav>
</body>
</html>
