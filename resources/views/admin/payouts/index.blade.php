@extends('admin.layouts.app', ['title' => 'Payout'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Payout']]" />
    <x-admin.page-header title="Payout operations" description="Kelola pengajuan pencairan dengan validasi saldo, approval, dan bukti transfer.">
        @can('viewReconciliation', \Modules\Operations\Entities\Payout::class)<a class="admin-button admin-button--quiet" href="{{ route('admin.payouts.reconciliation') }}">Rekonsiliasi</a>@endcan
    </x-admin.page-header>

    <div class="admin-stat-grid">
        <x-admin.stat-card label="Total payout" :value="number_format($summary['total'], 0, ',', '.')" icon="⇢" tone="blue" />
        <x-admin.stat-card label="Menunggu approval" :value="number_format($summary['requested'], 0, ',', '.')" icon="…" tone="yellow" />
        <x-admin.stat-card label="Sudah disetujui" :value="number_format($summary['approved'], 0, ',', '.')" icon="✓" tone="green" />
        <x-admin.stat-card label="Dana reserved" :value="'Rp '.number_format($summary['pending_amount'], 0, ',', '.')" icon="$" tone="coral" />
    </div>

    <x-admin.filter-bar method="GET" action="/admin/payouts">
        <div class="admin-field"><label for="payout-q">Cari</label><input id="payout-q" name="q" value="{{ request('q') }}" placeholder="Organisasi atau ID..."></div>
        <div class="admin-field"><label for="payout-status">Status</label><select id="payout-status" name="status"><option value="">Semua status</option>@foreach (config('admin.statuses.payout') as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="admin-field"><label for="payout-from">Dari</label><input id="payout-from" type="date" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="admin-field"><label for="payout-to">Sampai</label><input id="payout-to" type="date" name="date_to" value="{{ request('date_to') }}"></div>
        <button class="admin-button admin-button--primary" type="submit">Filter</button>
        <a class="admin-button admin-button--quiet" href="/admin/payouts">Reset</a>
    </x-admin.filter-bar>

    <div style="height: 20px;"></div>
    <x-admin.data-table title="Daftar payout" description="{{ $payouts->total() }} pengajuan ditemukan.">
        <thead><tr><th>Organisasi</th><th>Nominal</th><th>Pengaju</th><th>Status</th><th>Waktu</th><th></th></tr></thead>
        <tbody>
            @forelse ($payouts as $payout)
                <tr>
                    <td><span class="admin-table__primary">{{ $payout->organization?->name ?? 'Organisasi tidak tersedia' }}</span><span class="admin-table__secondary">{{ $payout->note ?: 'Tanpa catatan' }}</span></td>
                    <td>Rp {{ number_format($payout->amount, 0, ',', '.') }}</td>
                    <td>{{ $payout->requester?->name ?? 'Tidak tersedia' }}</td>
                    <td><x-admin.status-badge :status="$payout->status" /></td>
                    <td>{{ $payout->created_at?->format('d M Y H:i') }}</td>
                    <td><a class="admin-button admin-button--quiet" href="/admin/payouts/{{ $payout->id }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-admin.empty-state title="Belum ada payout" description="Belum ada pengajuan pencairan dana." /></td></tr>
            @endforelse
        </tbody>
    </x-admin.data-table>
    <x-admin.pagination :current="$payouts->currentPage()" :total="$payouts->lastPage()" :label="$payouts->total().' payout'" />
@endsection
