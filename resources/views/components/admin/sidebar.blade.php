@php
    $items = [
        ['label' => 'Dashboard', 'icon' => '⌂', 'path' => '/admin', 'match' => 'admin', 'permissions' => ['dashboard.view']],
        ['label' => 'Campaign', 'icon' => '✦', 'path' => '/admin/campaigns', 'match' => 'admin/campaigns*', 'permissions' => ['campaigns.view', 'campaigns.review', 'campaigns.publish']],
        ['label' => 'Organisasi', 'icon' => '◉', 'path' => '/admin/organizations', 'match' => 'admin/organizations*', 'permissions' => ['organizations.view', 'organizations.review']],
        ['label' => 'Donasi', 'icon' => '↗', 'path' => '/admin/donations', 'match' => 'admin/donations*', 'permissions' => ['donations.view']],
        ['label' => 'Pembayaran', 'icon' => '▣', 'path' => '/admin/payments', 'match' => 'admin/payments*', 'permissions' => ['payments.view', 'payments.manage']],
        ['label' => 'Transaksi Mayar', 'icon' => '↗', 'path' => '/admin/mayar-transactions', 'match' => 'admin/mayar-transactions*', 'permissions' => ['payments.view', 'payments.manage']],
        ['label' => 'Payout', 'icon' => '⇢', 'path' => '/admin/payouts', 'match' => 'admin/payouts*', 'permissions' => ['payouts.view', 'payouts.manage']],
        ['label' => 'Rekonsiliasi payout', 'icon' => '≋', 'path' => '/admin/payouts-reconciliation', 'match' => 'admin/payouts-reconciliation', 'permissions' => ['payouts.view', 'payouts.manage']],
    ];
    $managementItems = [
        ['label' => 'Transparansi', 'icon' => '◌', 'path' => '/admin/transparency', 'match' => 'admin/transparency*', 'permissions' => ['transparency.view', 'transparency.review']],
        ['label' => 'Kategori', 'icon' => '#', 'path' => '/admin/categories', 'match' => 'admin/categories*', 'permissions' => ['categories.manage']],
        ['label' => 'Pengguna & Role', 'icon' => '♙', 'path' => '/admin/users', 'match' => 'admin/users*', 'permissions' => ['users.roles.manage']],
        ['label' => 'Laporan', 'icon' => '▥', 'path' => '/admin/reports', 'match' => 'admin/reports*', 'permissions' => ['reports.view']],
        ['label' => 'Audit Log', 'icon' => '⌁', 'path' => '/admin/audit-logs', 'match' => 'admin/audit-logs*', 'permissions' => ['audit.view']],
        ['label' => 'Pengaturan', 'icon' => '⚙', 'path' => '/admin/settings', 'match' => 'admin/settings*', 'permissions' => ['settings.manage']],
    ];
    $canSee = fn (array $item): bool => auth()->user()?->hasAnyAdminPermission(...$item['permissions']) ?? false;
@endphp

<aside class="admin-sidebar" data-admin-sidebar data-admin-close-sidebar>
    <a class="admin-sidebar__brand" href="/admin">
        @if (!empty($brand['logo_url']))
            <img class="admin-brand-logo" src="{{ $brand['logo_url'] }}" alt="{{ $brand['app_name'] }}">
        @else
            <span class="admin-brand-mark">{{ $brand['brand_icon'] ?? 'S' }}</span>
        @endif
        <span>
            <strong>{{ $brand['app_name'] ?? 'Salamaid' }}</strong>
            <small>{{ $brand['app_tagline'] ?? 'Admin Console' }}</small>
        </span>
    </a>

    <div class="admin-sidebar__section">Workspace</div>
    <nav class="admin-sidebar__nav" aria-label="Menu utama">
        @foreach ($items as $item)
            @if ($canSee($item))
                <a class="admin-nav-link {{ request()->is($item['match']) ? 'is-active' : '' }}" href="{{ $item['path'] }}">
                    <span class="admin-nav-link__icon" aria-hidden="true">{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    <div class="admin-sidebar__section">Management</div>
    <nav class="admin-sidebar__nav" aria-label="Menu pengelolaan">
        @foreach ($managementItems as $item)
            @if ($canSee($item))
                <a class="admin-nav-link {{ request()->is($item['match']) ? 'is-active' : '' }}" href="{{ $item['path'] }}">
                    <span class="admin-nav-link__icon" aria-hidden="true">{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    <div class="admin-sidebar__footer">
        <strong>{{ $brand['app_name'] ?? 'Salamaid' }}</strong><br>
        {{ $brand['contact_email'] ?: 'Internal operations panel' }}
        @if (!empty($brand['contact_city']))<br>{{ $brand['contact_city'] }}@endif
    </div>
</aside>
