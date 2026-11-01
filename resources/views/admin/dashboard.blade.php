@extends('admin.layouts.app', ['title' => 'Dashboard'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Dashboard']]" />

    <x-admin.page-header title="Selamat datang, Admin." description="Pantau aktivitas crowdfunding Salamaid dan selesaikan pekerjaan operasional yang membutuhkan perhatian.">
        <button class="admin-button admin-button--quiet" type="button" data-admin-modal-open="admin-help-modal">⌘ Panduan</button>
        <a class="admin-button admin-button--primary" href="/admin/campaigns">Lihat campaign →</a>
    </x-admin.page-header>

    <form class="admin-filter-bar" method="GET" action="{{ route('admin.dashboard') }}" aria-label="Filter periode dashboard">
        <div class="admin-field"><label for="dashboard-period">Periode</label><select id="dashboard-period" name="period"><option value="today" @selected(request('period') === 'today')>Hari ini</option><option value="7d" @selected(request('period') === '7d')>7 hari terakhir</option><option value="30d" @selected(request('period') === '30d')>30 hari terakhir</option><option value="month" @selected(request('period', 'month') === 'month')>Bulan ini</option><option value="custom" @selected(request('period') === 'custom')>Kustom</option></select></div>
        <div class="admin-field"><label for="dashboard-date-from">Dari</label><input id="dashboard-date-from" type="date" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="admin-field"><label for="dashboard-date-to">Sampai</label><input id="dashboard-date-to" type="date" name="date_to" value="{{ request('date_to') }}"></div>
        <button class="admin-button admin-button--primary" type="submit">Terapkan</button>
        <a class="admin-button admin-button--quiet" href="{{ route('admin.dashboard') }}">Reset</a>
        <span class="admin-table__secondary">{{ $period['label'] }} · {{ $period['timezone'] }}</span>
    </form>

    @if (session('success'))<x-admin.toast :message="session('success')" />@endif
    @if (session('error'))<x-admin.toast :message="session('error')" type="error" />@endif

    <div class="admin-stat-grid">
        <x-admin.stat-card label="Campaign aktif" :value="number_format($summary['active_campaigns'], 0, ',', '.')" :trend="$summary['campaigns_review'].' menunggu review'" icon="✦" tone="lime" href="/admin/campaigns?status=published" />
        <x-admin.stat-card label="Organisasi pending" :value="number_format($summary['pending_organizations'], 0, ',', '.')" trend="Perlu verifikasi" icon="◉" tone="yellow" href="/admin/organizations?status=pending" />
        <x-admin.stat-card label="Donasi {{ strtolower($period['label']) }}" :value="'Rp '.number_format($summary['monthly_donations'], 0, ',', '.')" trend="Gross paid pada periode terpilih" icon="↗" tone="blue" href="/admin/donations?status=paid" />
        <x-admin.stat-card label="Payout pending" :value="'Rp '.number_format($summary['pending_payout_amount'], 0, ',', '.')" :trend="$summary['pending_payouts'].' pengajuan terbuka'" icon="⇢" tone="coral" href="/admin/payouts?status=requested" />
    </div>

    <div class="admin-grid">
        <div>
            <x-admin.data-table title="Campaign perlu perhatian" description="Campaign yang menunggu review admin.">
                <thead><tr><th>Campaign</th><th>Organisasi</th><th>Target</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td><span class="admin-table__primary">{{ $campaign->title }}</span><span class="admin-table__secondary">Diperbarui {{ $campaign->updated_at?->diffForHumans() }}</span></td>
                            <td>{{ $campaign->organization?->name ?? '—' }}</td>
                            <td>Rp {{ number_format($campaign->target_amount, 0, ',', '.') }}</td>
                            <td><x-admin.status-badge status="under_review" label="Review" /></td>
                            <td><a class="admin-button admin-button--quiet" href="/admin/campaigns/{{ $campaign->id }}">Buka</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty-state title="Tidak ada campaign pending" description="Semua campaign sudah diproses." /></td></tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>

            <div style="height: 20px;"></div>

            <div class="admin-alert">
                <span class="admin-alert__icon">!</span>
                <div>
                    <strong>Payment perlu diperiksa</strong>
                    <p>{{ $summary['failed_payments'] }} payment gagal/expired dan {{ $summary['unprocessed_webhooks'] }} webhook belum diproses.</p>
                </div>
                <a class="admin-button" href="/admin/payments-reconciliation">Periksa</a>
            </div>
        </div>

        <aside class="admin-panel">
            <div class="admin-panel__header">
                <div><h2>Aktivitas terbaru</h2><p>Data operasional terakhir</p></div>
                <a class="admin-button admin-button--quiet" href="/admin/audit-logs">Semua</a>
            </div>
            <div class="admin-panel__body">
                <div class="admin-activity">
                    @forelse ($activities as $activity)
                        <div class="admin-activity__item"><span class="admin-activity__dot"></span><div><strong><a href="{{ $activity['url'] ?? '#' }}">{{ $activity['title'] }}</a></strong><p>{{ $activity['description'] }}</p><time>{{ $activity['at']?->diffForHumans() }}</time></div></div>
                    @empty
                        <x-admin.empty-state title="Belum ada aktivitas" description="Aktivitas operasional akan muncul di sini." />
                    @endforelse
                </div>
            </div>
        </aside>
    </div>

    @push('modals')
        <x-admin.modal id="admin-help-modal" title="Panduan dashboard">
            Statistik dan aktivitas pada dashboard ini berasal dari database. Detail operasional dapat dibuka melalui menu terkait dan tetap dilindungi permission role.
        </x-admin.modal>
    @endpush
@endsection
