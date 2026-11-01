@extends('admin.layouts.app', ['title' => 'Detail Payment'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Pembayaran', 'url' => '/admin/payments'], ['label' => 'Detail']]" />
    <x-admin.page-header title="Detail payment" :description="'Order ID: '.$payment->order_id">
        <a class="admin-button admin-button--quiet" href="/admin/payments">← Kembali</a>
    </x-admin.page-header>

    <div class="admin-grid">
        <x-admin.data-table title="Informasi payment" description="Credential dan secret tidak ditampilkan.">
            <tbody>
                <tr><th>Payment ID</th><td>{{ $payment->id }}</td></tr>
                <tr><th>Order ID</th><td>{{ $payment->order_id }}</td></tr>
                <tr><th>Provider</th><td>{{ $payment->provider }}</td></tr>
                <tr><th>Channel</th><td>{{ $payment->channel_code }}</td></tr>
                <tr><th>Status</th><td><x-admin.status-badge :status="$payment->status" /></td></tr>
                <tr><th>VA number</th><td>{{ $payment->va_number ?: '—' }}</td></tr>
                <tr><th>Signature</th><td>{{ $payment->signature ? substr($payment->signature, 0, 8).'••••' : '—' }}</td></tr>
                <tr><th>Expired</th><td>{{ $payment->expired_at?->format('d M Y H:i') ?? '—' }}</td></tr>
                <tr><th>Paid</th><td>{{ $payment->paid_at?->format('d M Y H:i') ?? '—' }}</td></tr>
            </tbody>
        </x-admin.data-table>

        <x-admin.data-table title="Donation terkait" description="Hubungan payment dengan transaksi donor.">
            <tbody>
                <tr><th>Donation ID</th><td>{{ $payment->donation?->id ?? 'Tidak tersedia' }}</td></tr>
                <tr><th>Campaign</th><td>{{ $payment->donation?->campaign?->title ?? 'Tidak tersedia' }}</td></tr>
                <tr><th>Donor</th><td>{{ $payment->donation?->anonymous ? 'Donor anonim' : ($payment->donation?->user?->name ?? 'Guest donor') }}</td></tr>
                <tr><th>Gross amount</th><td>Rp {{ number_format($payment->donation?->gross_amount ?? 0, 0, ',', '.') }}</td></tr>
                <tr><th>Donation status</th><td>@if ($payment->donation)<x-admin.status-badge :status="$payment->donation->status" />@else—@endif</td></tr>
            </tbody>
        </x-admin.data-table>
    </div>

    <div style="height: 20px;"></div>
    <x-admin.data-table title="Webhook timeline" description="Callback provider yang tersimpan untuk order ini.">
        <thead><tr><th>Provider</th><th>Event</th><th>Signature</th><th>Processed</th><th>Waktu</th></tr></thead>
        <tbody>
            @forelse ($payment->webhooks as $webhook)
                <tr><td>{{ $webhook->provider }}</td><td>{{ $webhook->event ?: '—' }}</td><td>{{ $webhook->signature ? substr($webhook->signature, 0, 8).'••••' : '—' }}</td><td>{{ $webhook->processed_at?->format('d M Y H:i') ?? 'Belum diproses' }}</td><td>{{ $webhook->created_at?->format('d M Y H:i') }}</td></tr>
            @empty
                <tr><td colspan="5"><x-admin.empty-state title="Belum ada webhook" description="Belum ada callback tersimpan untuk payment ini." /></td></tr>
            @endforelse
        </tbody>
    </x-admin.data-table>
@endsection
