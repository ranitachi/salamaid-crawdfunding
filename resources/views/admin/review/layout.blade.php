<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — Salamaid</title>
    <style>
        :root {
            --sa-primary: #ff7600;
            --sa-primary-soft: #fff3eb;
            --sa-text: #1f2937;
            --sa-muted: #6b7280;
            --sa-border: #e5e7eb;
            --sa-bg: #f6f7f9;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: var(--sa-text); background: var(--sa-bg); }
        a { color: #b45309; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .sa-shell { display: flex; min-height: 100vh; }
        .sa-sidebar { width: 240px; background: #111827; color: #e5e7eb; padding: 20px 14px; flex-shrink: 0; }
        .sa-brand { font-weight: 700; font-size: 18px; margin-bottom: 24px; color: #fff; }
        .sa-brand span { color: var(--sa-primary); }
        .sa-nav a { display: block; color: #d1d5db; padding: 9px 12px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; }
        .sa-nav a:hover, .sa-nav a.active { background: #1f2937; color: #fff; text-decoration: none; }
        .sa-main { flex: 1; min-width: 0; }
        .sa-topbar { background: #fff; border-bottom: 1px solid var(--sa-border); padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        .sa-content { padding: 24px; max-width: 1280px; }
        .sa-page-header { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; margin-bottom: 18px; flex-wrap: wrap; }
        .sa-page-header h1 { margin: 0 0 4px; font-size: 24px; }
        .sa-muted { color: var(--sa-muted); font-size: 13px; }
        .sa-breadcrumb { font-size: 13px; color: var(--sa-muted); margin-bottom: 10px; }
        .card { background: #fff; border: 1px solid var(--sa-border); border-radius: 12px; padding: 18px; margin-bottom: 18px; }
        .card h2 { margin: 0 0 12px; font-size: 16px; }
        .grid { display: grid; gap: 14px; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        .grid-4 { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
        .stat-label { color: var(--sa-muted); font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        .stat-value { font-size: 20px; font-weight: 700; margin-top: 4px; }
        .table-wrap { overflow-x: auto; }
        table.table { width: 100%; border-collapse: collapse; font-size: 14px; }
        table.table th, table.table td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--sa-border); vertical-align: top; }
        table.table th { color: var(--sa-muted); font-size: 12px; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
        table.table tr:last-child td { border-bottom: 0; }
        .badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-muted { background: #f3f4f6; color: #4b5563; }
        .btn { display: inline-block; border: 1px solid transparent; border-radius: 8px; padding: 8px 13px; font-size: 13px; font-weight: 600; cursor: pointer; background: var(--sa-primary); color: #fff; }
        .btn:hover { text-decoration: none; filter: brightness(.95); }
        .btn-secondary { background: #fff; border-color: var(--sa-border); color: var(--sa-text); }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-small { padding: 5px 9px; font-size: 12px; }
        .filter-bar { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
        .filter-bar .field { display: flex; flex-direction: column; gap: 4px; min-width: 140px; }
        .filter-bar label { font-size: 12px; color: var(--sa-muted); }
        input, select, textarea { border: 1px solid var(--sa-border); border-radius: 8px; padding: 8px 10px; font-size: 14px; font-family: inherit; background: #fff; }
        textarea { min-height: 80px; width: 100%; }
        .alert { padding: 11px 14px; border-radius: 9px; margin-bottom: 16px; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .empty-state { text-align: center; padding: 34px 16px; color: var(--sa-muted); }
        .empty-state strong { display: block; color: var(--sa-text); margin-bottom: 4px; }
        .pagination { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 16px; }
        .pagination a, .pagination span { display: inline-block; min-width: 32px; text-align: center; padding: 6px 9px; border: 1px solid var(--sa-border); border-radius: 7px; font-size: 13px; background: #fff; }
        .pagination .active { background: var(--sa-primary); border-color: var(--sa-primary); color: #fff; }
        .pagination .disabled { color: #9ca3af; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .action-form { display: inline-flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        details.action-panel { border: 1px solid var(--sa-border); border-radius: 9px; padding: 10px 12px; background: #fff; margin-top: 8px; }
        details.action-panel summary { cursor: pointer; font-weight: 600; font-size: 13px; }
        .muted-box { background: #f9fafb; border: 1px solid var(--sa-border); border-radius: 8px; padding: 10px; font-size: 13px; }
        .progress { height: 9px; background: #f3f4f6; border-radius: 999px; overflow: hidden; }
        .progress > span { display: block; height: 100%; background: var(--sa-primary); }
        @media (max-width: 800px) {
            .sa-shell { display: block; }
            .sa-sidebar { width: auto; }
            .sa-content { padding: 16px; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="sa-shell">
    <aside class="sa-sidebar">
        <div class="sa-brand">Salamaid<span>.</span></div>
        <nav class="sa-nav">
            <a href="{{ url('/admin') }}" class="{{ request()->is('admin') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.organizations.index') }}" class="{{ request()->is('admin/organizations*') ? 'active' : '' }}">Organisasi</a>
            <a href="{{ route('admin.campaigns.index') }}" class="{{ request()->is('admin/campaigns*') ? 'active' : '' }}">Campaign</a>
        </nav>
    </aside>
    <div class="sa-main">
        <header class="sa-topbar">
            <div>
                <strong>Admin Review</strong>
                <div class="sa-muted">A4 Organization Review &amp; A5 Campaign Moderation</div>
            </div>
            <div class="sa-muted">
                @auth
                    {{ auth()->user()->name }} — {{ auth()->user()->role }}
                @else
                    Guest
                @endauth
            </div>
        </header>
        <main class="sa-content">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error">
                    <strong>Periksa input berikut:</strong>
                    <ul style="margin:6px 0 0 18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
