@extends('admin.layouts.app', ['title' => 'Pembayaran'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Pembayaran']]" />
    <x-admin.page-header title="Payment operations" description="Pantau payment intent, callback provider, dan transaksi yang perlu direkonsiliasi.">
        <a class="admin-button admin-button--primary" href="{{ route('admin.payments.export', request()->query()) }}">Export CSV</a>
        <a class="admin-button admin-button--quiet" href="/admin/webhooks">Webhook log</a>
        <a class="admin-button admin-button--primary" href="/admin/payments-reconciliation">Rekonsiliasi</a>
    </x-admin.page-header>

    <div class="admin-stat-grid">
        <x-admin.stat-card label="Total payment" :value="number_format($summary['total'], 0, ',', '.')" icon="▣" tone="blue" />
        <x-admin.stat-card label="Paid" :value="number_format($summary['paid'], 0, ',', '.')" icon="✓" tone="green" />
        <x-admin.stat-card label="Pending" :value="number_format($summary['pending'], 0, ',', '.')" icon="…" tone="yellow" />
        <x-admin.stat-card label="Mismatch" :value="number_format($mismatchCount, 0, ',', '.')" icon="!" tone="coral" />
    </div>

    <x-admin.filter-bar method="GET" action="/admin/payments">
        <div class="admin-field"><label for="payment-q">Cari</label><input id="payment-q" name="q" value="{{ request('q') }}" placeholder="Order ID, payment ID..."></div>
        <div class="admin-field"><label for="payment-status">Status</label><select id="payment-status" name="status"><option value="">Semua status</option>@foreach (config('admin.statuses.payment') as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="admin-field"><label for="payment-provider">Provider</label><input id="payment-provider" name="provider" value="{{ request('provider') }}" placeholder="faspay..."></div>
        <button class="admin-button admin-button--primary" type="submit">Filter</button>
        <a class="admin-button admin-button--quiet" href="/admin/payments">Reset</a>
    </x-admin.filter-bar>

    <div style="height: 20px;"></div>
    <x-admin.data-table title="Daftar payment" description="{{ $payments->total() }} payment ditemukan.">
        <thead><tr><th>Order ID / Donasi</th><th>Provider</th><th>Channel</th><th>Nominal</th><th>Status</th><th>Waktu</th><th></th></tr></thead>
        <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td><span class="admin-table__primary">{{ $payment->order_id }}</span><span class="admin-table__secondary">{{ $payment->donation?->id ?? 'Donation tidak tersedia' }}</span></td>
                    <td>{{ $payment->provider }}</td>
                    <td>{{ $payment->channel_code }}</td>
                    <td>Rp {{ number_format($payment->donation?->gross_amount ?? 0, 0, ',', '.') }}</td>
                    <td><x-admin.status-badge :status="$payment->status" /></td>
                    <td>{{ $payment->created_at?->format('d M Y H:i') }}</td>
                    <td><a class="admin-button admin-button--quiet" href="/admin/payments/{{ $payment->id }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-admin.empty-state title="Belum ada payment" description="Coba ubah filter atau jalankan seed data development." /></td></tr>
            @endforelse
        </tbody>
    </x-admin.data-table>
    <x-admin.pagination :current="$payments->currentPage()" :total="$payments->lastPage()" :label="$payments->total().' payment'" />
@endsection
