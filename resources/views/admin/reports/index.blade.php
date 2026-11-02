@extends('admin.layouts.app', ['title' => 'Laporan'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Laporan']]" />
    <x-admin.page-header title="Laporan operasional" description="Ringkasan agregat aktivitas admin pada rentang waktu terpilih.">
        <a class="admin-button admin-button--quiet" href="{{ route('admin.reports.export', request()->query()) }}">Export CSV</a>
    </x-admin.page-header>

    <x-admin.filter-bar method="GET" :action="route('admin.reports.index')">
        <div class="admin-field"><label for="report-from">Dari</label><input id="report-from" type="date" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="admin-field"><label for="report-to">Sampai</label><input id="report-to" type="date" name="date_to" value="{{ request('date_to') }}"></div>
        <button class="admin-button admin-button--primary" type="submit">Terapkan</button>
        <a class="admin-button admin-button--quiet" href="{{ route('admin.reports.index') }}">30 hari terakhir</a>
    </x-admin.filter-bar>

    <p class="admin-table__secondary">Periode: {{ $report['period']['from']->format('d M Y') }} — {{ $report['period']['to']->format('d M Y') }} ({{ config('app.timezone') }})</p>

    <div class="admin-grid admin-grid--4">
        <x-admin.stat-card label="Campaign dibuat" :value="$report['campaigns']['total']" />
        <x-admin.stat-card label="Donasi" :value="$report['donations']['total']" />
        <x-admin.stat-card label="Payment" :value="$report['payments']['total']" />
        <x-admin.stat-card label="Payout" :value="$report['payouts']['total']" />
    </div>

    <div class="admin-grid admin-grid--2" style="margin-top: 20px;">
        @foreach ([
            'Campaign' => $report['campaigns']['by_status'],
            'Donasi' => $report['donations']['by_status'],
            'Payment' => $report['payments']['by_status'],
            'Payout' => $report['payouts']['by_status'],
        ] as $title => $items)
            <x-admin.data-table :title="$title.' berdasarkan status'">
                <thead><tr><th>Status</th><th class="text-end">Jumlah</th></tr></thead>
                <tbody>
                    @forelse ($items as $status => $total)
                        <tr><td><x-admin.status-badge :status="$status" /></td><td class="text-end">{{ number_format($total) }}</td></tr>
                    @empty
                        <tr><td colspan="2"><x-admin.empty-state title="Belum ada data" description="Tidak ada aktivitas pada periode ini." /></td></tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        @endforeach
    </div>

    <div style="height: 20px;"></div>
    <x-admin.data-table title="Ringkasan fee" description="Fee agregat dari donasi pada periode terpilih.">
        <thead><tr><th>Komponen</th><th class="text-end">Nominal</th></tr></thead>
        <tbody>
            <tr><td>Fee platform</td><td class="text-end">Rp {{ number_format($report['fees']['platform_amount'], 0, ',', '.') }}</td></tr>
            <tr><td>Fee channel</td><td class="text-end">Rp {{ number_format($report['fees']['channel_amount'], 0, ',', '.') }}</td></tr>
            <tr><td><strong>Total fee</strong></td><td class="text-end"><strong>Rp {{ number_format($report['fees']['total_amount'], 0, ',', '.') }}</strong></td></tr>
            @foreach ($report['fees']['by_channel'] as $channel => $total)
                <tr><td>Channel: {{ $channel }}</td><td class="text-end">Rp {{ number_format($total, 0, ',', '.') }}</td></tr>
            @endforeach
        </tbody>
    </x-admin.data-table>
@endsection
