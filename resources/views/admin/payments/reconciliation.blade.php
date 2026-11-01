@extends('admin.layouts.app', ['title' => 'Rekonsiliasi Payment'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Pembayaran', 'url' => '/admin/payments'], ['label' => 'Rekonsiliasi']]" />
    <x-admin.page-header title="Rekonsiliasi payment" description="Temukan perbedaan status antara payment dan donation sebelum dilakukan tindakan operasional." />

    <div class="admin-alert"><span class="admin-alert__icon">!</span><div><strong>Rekonsiliasi bersifat read-only</strong><p>Status finansial tidak diubah dari halaman ini. Perbaikan harus berasal dari webhook tervalidasi atau proses inquiry provider.</p></div></div>
    <div style="height: 20px;"></div>

    <div class="admin-grid">
        <x-admin.data-table title="Payment tanpa donation" description="Payment yang tidak memiliki transaksi donation terkait.">
            <thead><tr><th>Order ID</th><th>Provider</th><th>Status</th></tr></thead>
            <tbody>@forelse ($mismatches['payments_without_donation'] as $payment)<tr><td>{{ $payment->order_id }}</td><td>{{ $payment->provider }}</td><td><x-admin.status-badge :status="$payment->status" /></td></tr>@empty<tr><td colspan="3"><x-admin.empty-state title="Tidak ada mismatch" description="Semua payment memiliki donation terkait." /></td></tr>@endforelse</tbody>
        </x-admin.data-table>
        <x-admin.data-table title="Payment dan donation berbeda" description="Kedua sisi memiliki status yang tidak sinkron.">
            <thead><tr><th>Order ID</th><th>Payment</th><th>Donation</th></tr></thead>
            <tbody>@forelse ($mismatches['paid_payment_not_paid_donation'] as $payment)<tr><td>{{ $payment->order_id }}</td><td><x-admin.status-badge :status="$payment->status" /></td><td><x-admin.status-badge :status="$payment->donation?->status ?? 'failed'" /></td></tr>@empty<tr><td colspan="3"><x-admin.empty-state title="Tidak ada mismatch" description="Status payment dan donation sinkron." /></td></tr>@endforelse</tbody>
        </x-admin.data-table>
    </div>

    <div style="height: 20px;"></div>
    <x-admin.data-table title="Donation paid tanpa payment paid" description="Donation paid yang payment-nya belum berstatus paid atau belum tersedia.">
        <thead><tr><th>Donation ID</th><th>Campaign</th><th>Donation</th><th>Payment</th></tr></thead>
        <tbody>@forelse ($mismatches['paid_donation_without_paid_payment'] as $donation)<tr><td>{{ $donation->id }}</td><td>{{ $donation->campaign?->title ?? '—' }}</td><td><x-admin.status-badge status="paid" /></td><td>{{ $donation->payment?->status ?? 'Tidak tersedia' }}</td></tr>@empty<tr><td colspan="4"><x-admin.empty-state title="Tidak ada mismatch" description="Semua donation paid memiliki payment paid." /></td></tr>@endforelse</tbody>
    </x-admin.data-table>
@endsection
