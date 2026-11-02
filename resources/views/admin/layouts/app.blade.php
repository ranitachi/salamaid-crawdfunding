@props(['title' => 'Dashboard'])
@php($brand = $brand ?? app(\App\Services\AppSettingsService::class)->all())
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ $brand['app_name'] ?? 'Salamaid' }} Admin</title>
    @if (!empty($brand['favicon_url']))<link rel="icon" href="{{ $brand['favicon_url'] }}">@endif
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="admin-body" style="--admin-primary: {{ $brand['primary_color'] ?? '#D92D20' }}; --admin-primary-soft: {{ $brand['primary_soft_color'] ?? '#FEE4E2' }}; --admin-accent: {{ $brand['accent_color'] ?? '#FF806F' }};">
    <div class="admin-app">
        <x-admin.sidebar :brand="$brand" />

        <main class="admin-main">
            <x-admin.topbar :title="$title" :brand="$brand" />

            <div class="admin-content">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('modals')
</body>
</html>
