@extends('admin.layouts.app', ['title' => 'Detail Donasi'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Donasi', 'url' => '/admin/donations'], ['label' => 'Detail']]" />
    <x-admin.page-header title="Detail donasi" :description="'ID: '.$donation->id">
        <a class="admin-button admin-button--primary" href="{{ route('admin.donations.receipt', $donation) }}" target="_blank" rel="noopener">Receipt</a>
        <a class="admin-button admin-button--quiet" href="/admin/donations">← Kembali</a>
    </x-admin.page-header>

    <div class="admin-grid">
        <div class="admin-panel">
            <div class="admin-panel__header"><div><h2>Ringkasan transaksi</h2><p>Informasi finansial dan status donasi.</p></div><x-admin.status-badge :status="$donation->status" /></div>
            <div class="admin-panel__body">
                <div class="admin-stat-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 0;">
                    <x-admin.stat-card label="Gross amount" :value="'Rp '.number_format($donation->gross_amount, 0, ',', '.')" icon="$" tone="blue" />
                    <x-admin.stat-card label="Net amount" :value="'Rp '.number_format($donation->net_amount, 0, ',', '.')" icon="↗" tone="green" />
                </div>
                <dl class="admin-detail-list">
                    <div><dt>Donation ID</dt><dd>{{ $donation->id }}</dd></div>
                    <div><dt>Method</dt><dd>{{ strtoupper($donation->method) }}</dd></div>
                    <div><dt>Platform fee</dt><dd>Rp {{ number_format($donation->fee_platform, 0, ',', '.') }}</dd></div>
                    <div><dt>Channel fee</dt><dd>Rp {{ number_format($donation->fee_channel, 0, ',', '.') }}</dd></div>
                    <div><dt>Anonymous</dt><dd>{{ $donation->anonymous ? 'Ya' : 'Tidak' }}</dd></div>
                    <div><dt>Dibuat</dt><dd>{{ $donation->created_at?->format('d M Y H:i') }}</dd></div>
                    <div><dt>Dibayar</dt><dd>{{ $donation->paid_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                    <div><dt>Pesan</dt><dd>{{ $donation->message ?: '—' }}</dd></div>
                </dl>
            </div>
        </div>

        <div>
            <section class="admin-panel">
                <div class="admin-panel__header"><div><h2>Donor</h2><p>Identitas ditampilkan sesuai status anonim.</p></div></div>
                <div class="admin-panel__body"><strong>{{ $donation->anonymous ? 'Donor anonim' : ($donation->user?->name ?? 'Guest donor') }}</strong><p style="color: var(--admin-muted); font-size: 12px;">{{ $donation->anonymous ? 'Identitas disembunyikan.' : ($donation->user?->email ?? 'Tidak ada email') }}</p></div>
            </section>
            <div style="height: 20px;"></div>
            <section class="admin-panel">
                <div class="admin-panel__header"><div><h2>Campaign</h2><p>Program penerima donasi.</p></div></div>
                <div class="admin-panel__body"><strong>{{ $donation->campaign?->title ?? 'Campaign tidak tersedia' }}</strong><p style="color: var(--admin-muted); font-size: 12px;">{{ $donation->campaign?->organization?->name ?? 'Organisasi tidak tersedia' }}</p></div>
            </section>
        </div>
    </div>

    <div style="height: 20px;"></div>
    <x-admin.data-table title="Payment terkait" description="Status payment berasal dari payment flow atau webhook tervalidasi.">
        <thead><tr><th>Order ID</th><th>Provider</th><th>Channel</th><th>Status</th><th>Dibayar</th></tr></thead>
        <tbody>
            @if ($donation->payment)
                <tr><td>{{ $donation->payment->order_id }}</td><td>{{ $donation->payment->provider }}</td><td>{{ $donation->payment->channel_code }}</td><td><x-admin.status-badge :status="$donation->payment->status" /></td><td>{{ $donation->payment->paid_at?->format('d M Y H:i') ?? '—' }}</td></tr>
            @else
                <tr><td colspan="5"><x-admin.empty-state title="Payment belum tersedia" description="Donasi ini belum memiliki payment intent." /></td></tr>
            @endif
        </tbody>
    </x-admin.data-table>
@endsection
