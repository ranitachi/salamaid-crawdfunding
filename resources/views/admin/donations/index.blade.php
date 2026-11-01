@extends('admin.layouts.app', ['title' => 'Donasi'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Donasi']]" />
    <x-admin.page-header title="Monitoring donasi" description="Telusuri donasi, status transaksi, dan hubungan donor dengan campaign.">
        <a class="admin-button admin-button--primary" href="{{ route('admin.donations.export', request()->query()) }}">Export CSV</a>
    </x-admin.page-header>

    <div class="admin-stat-grid">
        <x-admin.stat-card label="Total donasi" :value="number_format($summary['total'], 0, ',', '.')" icon="↗" tone="blue" />
        <x-admin.stat-card label="Berhasil" :value="number_format($summary['paid'], 0, ',', '.')" icon="✓" tone="green" />
        <x-admin.stat-card label="Pending" :value="number_format($summary['pending'], 0, ',', '.')" icon="…" tone="yellow" />
        <x-admin.stat-card label="Gross paid" :value="'Rp '.number_format($summary['gross_paid'], 0, ',', '.')" icon="$" tone="lime" />
    </div>

    <x-admin.filter-bar method="GET" action="/admin/donations">
        <div class="admin-field"><label for="donation-q">Cari</label><input id="donation-q" name="q" value="{{ request('q') }}" placeholder="ID, donor, campaign..."></div>
        <div class="admin-field"><label for="donation-status">Status</label><select id="donation-status" name="status"><option value="">Semua status</option>@foreach (config('admin.statuses.donation') as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
        <div class="admin-field"><label for="donation-method">Method</label><input id="donation-method" name="method" value="{{ request('method') }}" placeholder="qris, va..."></div>
        <div class="admin-field"><label for="donation-anonymous">Anonymous</label><select id="donation-anonymous" name="anonymous"><option value="">Semua</option><option value="1" @selected(request('anonymous') === '1')>Ya</option><option value="0" @selected(request('anonymous') === '0')>Tidak</option></select></div>
        <div class="admin-field"><label for="donation-amount-min">Nominal min.</label><input id="donation-amount-min" type="number" min="0" name="amount_min" value="{{ request('amount_min') }}" placeholder="0"></div>
        <div class="admin-field"><label for="donation-amount-max">Nominal max.</label><input id="donation-amount-max" type="number" min="0" name="amount_max" value="{{ request('amount_max') }}" placeholder="1000000"></div>
        <div class="admin-field"><label for="donation-from">Dari</label><input id="donation-from" type="date" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="admin-field"><label for="donation-to">Sampai</label><input id="donation-to" type="date" name="date_to" value="{{ request('date_to') }}"></div>
        <button class="admin-button admin-button--primary" type="submit">Filter</button>
        <a class="admin-button admin-button--quiet" href="/admin/donations">Reset</a>
    </x-admin.filter-bar>

    <div style="height: 20px;"></div>

    <x-admin.data-table title="Daftar donasi" description="{{ $donations->total() }} transaksi ditemukan.">
        <thead><tr><th>Donor / Campaign</th><th>Nominal</th><th>Method</th><th>Status</th><th>Waktu</th><th></th></tr></thead>
        <tbody>
            @forelse ($donations as $donation)
                <tr>
                    <td><span class="admin-table__primary">{{ $donation->anonymous ? 'Donor anonim' : ($donation->user?->name ?? 'Guest donor') }}</span><span class="admin-table__secondary">{{ $donation->campaign?->title ?? 'Campaign tidak tersedia' }}</span></td>
                    <td><span class="admin-table__primary">Rp {{ number_format($donation->gross_amount, 0, ',', '.') }}</span><span class="admin-table__secondary">Net Rp {{ number_format($donation->net_amount, 0, ',', '.') }}</span></td>
                    <td>{{ strtoupper($donation->method) }}</td>
                    <td><x-admin.status-badge :status="$donation->status" /></td>
                    <td>{{ $donation->created_at?->format('d M Y H:i') }}</td>
                    <td><a class="admin-button admin-button--quiet" href="/admin/donations/{{ $donation->id }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-admin.empty-state title="Belum ada donasi" description="Coba ubah filter atau jalankan seed data development." /></td></tr>
            @endforelse
        </tbody>
    </x-admin.data-table>
    <x-admin.pagination :current="$donations->currentPage()" :total="$donations->lastPage()" :label="$donations->total().' transaksi'" />
@endsection
