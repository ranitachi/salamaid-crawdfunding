@php
    $status = (int) ($status ?? 500);
    $isAdmin = request()->is('admin') || request()->is('admin/*');
    $labels = [
        400 => ['title' => 'Permintaan tidak valid', 'description' => 'Data yang dikirim belum dapat diproses.'],
        401 => ['title' => 'Sesi berakhir', 'description' => 'Silakan masuk kembali untuk melanjutkan.'],
        403 => ['title' => 'Akses ditolak', 'description' => 'Anda tidak memiliki izin untuk membuka halaman ini.'],
        404 => ['title' => 'Halaman tidak ditemukan', 'description' => 'Halaman yang Anda cari tidak tersedia atau sudah dipindahkan.'],
        405 => ['title' => 'Metode tidak diizinkan', 'description' => 'Aksi tersebut tidak tersedia untuk halaman ini.'],
        419 => ['title' => 'Sesi kedaluwarsa', 'description' => 'Muat ulang halaman, lalu coba kembali.'],
        429 => ['title' => 'Terlalu banyak permintaan', 'description' => 'Tunggu sebentar sebelum mencoba lagi.'],
        500 => ['title' => 'Terjadi gangguan', 'description' => 'Sistem sedang mengalami kendala. Silakan coba lagi.'],
        502 => ['title' => 'Layanan tidak tersedia', 'description' => 'Gateway sedang mengalami gangguan sementara.'],
        503 => ['title' => 'Layanan sedang dipelihara', 'description' => 'Silakan coba kembali beberapa saat lagi.'],
    ];
    $message = $labels[$status] ?? ['title' => 'Terjadi kesalahan', 'description' => 'Permintaan belum dapat diselesaikan.'];
    try {
        $brand = app(\App\Services\AppSettingsService::class)->all();
    } catch (\Throwable) {
        $brand = ['app_name' => 'Salamaid', 'brand_icon' => 'S', 'primary_color' => '#D92D20', 'primary_soft_color' => '#FEE4E2'];
    }
    $appName = $brand['app_name'] ?? 'Salamaid';
    $primary = $brand['primary_color'] ?? '#D92D20';
    $soft = $brand['primary_soft_color'] ?? '#FEE4E2';
    $backUrl = $isAdmin ? url('/admin/login') : url('/');
    $backLabel = $isAdmin ? 'Ke halaman login admin' : 'Kembali ke beranda';
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $status }} · {{ $appName }}</title>
    @if (!empty($brand['favicon_url']))<link rel="icon" type="image/png" href="{{ $brand['favicon_url'] }}">@endif
    <style>
        :root { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; color: #1c1b1b; background: #fcf9f8; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; background: #fcf9f8; }
        .error-shell { display: grid; min-height: 100vh; grid-template-rows: auto 1fr; max-width: 1120px; margin: 0 auto; }
        .error-header { display: flex; align-items: center; gap: 10px; min-height: 72px; padding: 12px 24px; border-bottom: 1px solid #eadfdd; background: #fcf9f8ee; }
        .error-brand { display: inline-flex; align-items: center; gap: 10px; color: #1c1b1b; text-decoration: none; }
        .error-brand img, .error-brand-mark { display: grid; width: 38px; height: 38px; place-items: center; border-radius: 12px; background: {{ $primary }}; color: #fff; font-size: 20px; font-weight: 800; object-fit: cover; }
        .error-brand strong, .error-brand small { display: block; }
        .error-brand strong { font-size: 17px; }
        .error-brand small { margin-top: 2px; color: #6f6563; font-size: 10px; }
        .error-main { display: grid; place-items: center; padding: 32px 20px 100px; }
        .error-card { width: min(100%, 560px); padding: 42px 32px; border: 1px solid #eadfdd; border-radius: 24px; background: #fff; box-shadow: 0 12px 32px #4b1b1812; text-align: center; }
        .error-code { display: inline-flex; padding: 8px 14px; border-radius: 999px; background: {{ $soft }}; color: {{ $primary }}; font-size: 14px; font-weight: 800; letter-spacing: .08em; }
        .error-card h1 { margin: 18px 0 8px; font-size: clamp(24px, 4vw, 34px); letter-spacing: -.8px; }
        .error-card p { margin: 0 auto; max-width: 420px; color: #6f6563; font-size: 14px; line-height: 1.7; }
        .error-actions { display: flex; justify-content: center; gap: 10px; margin-top: 26px; flex-wrap: wrap; }
        .error-actions a { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; padding: 10px 18px; border-radius: 999px; background: {{ $primary }}; color: #fff; font-size: 13px; font-weight: 800; text-decoration: none; }
        .error-actions a.secondary { border: 1px solid #eadfdd; background: #fff; color: #1c1b1b; }
        @media (max-width: 600px) { .error-header { padding: 10px 16px; } .error-card { padding: 32px 20px; } }
    </style>
</head>
<body>
    <div class="error-shell">
        <header class="error-header">
            <a class="error-brand" href="{{ $isAdmin ? $backUrl : url('/') }}">
                @if (!empty($brand['logo_url']))<img src="{{ $brand['logo_url'] }}" alt="{{ $appName }}">@else<span class="error-brand-mark">{{ $brand['brand_icon'] ?? 'S' }}</span>@endif
                <span><strong>{{ $appName }}</strong><small>{{ $brand['public_tagline'] ?? 'Jembatan Kebaikan' }}</small></span>
            </a>
        </header>
        <main class="error-main">
            <section class="error-card" role="alert">
                <span class="error-code">{{ $status }}</span>
                <h1>{{ $message['title'] }}</h1>
                <p>{{ $message['description'] }}</p>
                <div class="error-actions">
                    <a href="{{ $backUrl }}">{{ $backLabel }}</a>
                    @if ($isAdmin)<a class="secondary" href="{{ url('/admin') }}">Coba dashboard</a>@endif
                </div>
            </section>
        </main>
    </div>
</body>
</html>
