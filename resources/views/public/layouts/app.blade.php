@php($brand = app(\App\Services\AppSettingsService::class)->all())
@php($navigation = app(\App\Services\AppSettingsService::class)->publicNavigation())
{{-- Keep Salamaid branding global; organization colors apply only on its profile page. --}}
@php($publicOrganization = $organization ?? null)
@php($publicPrimary = filled($publicOrganization?->primary_color) ? $publicOrganization->primary_color : ($brand['primary_color'] ?? '#BC0004'))
@php($publicSecondary = filled($publicOrganization?->secondary_color) ? $publicOrganization->secondary_color : ($brand['primary_soft_color'] ?? '#FFF0EF'))
@php($metaDescription = $description ?? 'Salamaid — platform gotong royong dan donasi terpercaya.')
@php($canonical = $canonical ?? url()->current())
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $brand['primary_color'] ?? '#BC0004' }}">
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:title" content="{{ $title ?? 'Salamaid' }} · {{ $brand['app_name'] ?? 'Salamaid' }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if (!empty($ogImage))<meta property="og:image" content="{{ $ogImage }}">@endif
    @stack('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,1,0&display=swap" rel="stylesheet">
    <title>{{ $title ?? 'Salamaid' }} · {{ $brand['app_name'] ?? 'Salamaid' }}</title>
    @if (!empty($brand['favicon_url']))<link rel="icon" type="image/png" href="{{ $brand['favicon_url'] }}">@endif
    @vite(['resources/css/public.css', 'resources/css/public-responsive.css', 'resources/css/public-home.css', 'resources/css/public-partner.css', 'resources/css/public-partner-form.css', 'resources/css/public-partner-status.css', 'resources/css/public-program-overrides.css', 'resources/css/public-detail.css', 'resources/css/public-campaign-media.css', 'resources/css/public-organization-profile.css', 'resources/css/public-checkout.css', 'resources/css/public-payment.css', 'resources/css/public-account.css', 'resources/js/public.js'])
</head>
<body class="{{ request()->routeIs('public.campaigns.show') ? 'public-campaign-route' : '' }}" style="--public-primary: {{ $publicPrimary }}; --public-primary-soft: {{ $publicSecondary }}; --public-brand-primary: {{ $brand['primary_color'] ?? '#BC0004' }}; --public-brand-primary-soft: {{ $brand['primary_soft_color'] ?? '#FFF0EF' }};">
    <div class="public-shell">
        <header class="public-header">
            <a class="public-brand" href="{{ route('public.home') }}">
                @if (!empty($brand['logo_url']))<img class="public-brand__mark" src="{{ $brand['logo_url'] }}" alt="{{ $brand['app_name'] }}">@else<span class="public-brand__mark">{{ $brand['brand_icon'] ?? 'S' }}</span>@endif
                <span><strong class="public-brand__name">{{ $brand['app_name'] ?? 'Salamaid' }}</strong><small class="public-brand__tagline">{{ $brand['public_tagline'] ?? 'Jembatan Kebaikan' }}</small></span>
            </a>
            <label class="public-search" aria-label="Cari campaign"><span aria-hidden="true">⌕</span><input data-public-search type="search" placeholder="Cari campaign atau kebaikan..."></label>
            <a class="public-icon-button" href="{{ auth()->check() ? route('public.inbox') : route('public.login') }}" aria-label="Inbox">♡</a>
        </header>
        <main class="public-main">@yield('content')</main>
        <footer class="public-footer">© {{ date('Y') }} {{ $brand['app_name'] ?? 'Salamaid' }} · {{ $brand['contact_email'] ?: 'Jembatan kebaikan Indonesia' }}</footer>
    </div>
    <nav class="public-bottom-nav" aria-label="Navigasi publik">
        @foreach ($navigation as $item)
            <a class="{{ request()->routeIs($item['route']) ? 'is-active' : '' }}" href="{{ route($item['route']) }}"><span class="public-bottom-nav__icon material-symbols-outlined">{{ $item['icon'] }}</span>{{ $item['label'] }}</a>
        @endforeach
    </nav>
    @stack('scripts')
</body>
</html>
