<!doctype html>
@php($brand = $brand ?? app(\App\Services\AppSettingsService::class)->all())
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ $brand['app_name'] ?? 'Salamaid' }} Admin</title>
    @if (!empty($brand['favicon_url']))<link rel="icon" href="{{ $brand['favicon_url'] }}?v=salamaid-20261104">@endif
    @vite(['resources/css/admin.css'])
</head>
<body class="admin-body" style="--admin-primary: {{ $brand['primary_color'] ?? '#D92D20' }}; --admin-primary-soft: {{ $brand['primary_soft_color'] ?? '#FEE4E2' }}; --admin-accent: {{ $brand['accent_color'] ?? '#FF806F' }};">
    <main style="display: grid; min-height: 100vh; place-items: center; padding: 16px;">
        <section class="admin-panel" style="width: 100%; max-width: 380px;">
            <div class="admin-panel__header">
                <div>
                        <h2>{{ $brand['app_name'] ?? 'Salamaid' }} Admin</h2>
                        <p>{{ $brand['app_tagline'] ?? 'Masuk untuk mengelola panel operasional.' }}</p>
                </div>
                @if (!empty($brand['logo_url']))
                    <img class="admin-brand-logo" src="{{ $brand['logo_url'] }}" alt="{{ $brand['app_name'] }}">
                @else
                    <span class="admin-brand-mark" aria-hidden="true">{{ $brand['brand_icon'] ?? 'S' }}</span>
                @endif
            </div>

            <form class="admin-panel__body" method="POST" action="{{ route('admin.login.store') }}" style="display: grid; gap: 14px;" novalidate>
                @csrf

                @if ($errors->any())
                    <div class="admin-alert" role="alert">
                        <span class="admin-alert__icon">!</span>
                        <div><p style="margin: 0;">{{ $errors->first() }}</p></div>
                    </div>
                @endif

                <div class="admin-field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                </div>

                <div class="admin-field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>

                <label style="display: flex; align-items: center; gap: 8px; font-size: 12px;">
                    <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
                    Ingat saya
                </label>

                <button class="admin-button admin-button--primary" type="submit">Masuk</button>
            </form>
        </section>
    </main>
</body>
</html>
