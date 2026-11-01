@extends('admin.layouts.app', ['title' => 'Webhook Log'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Pembayaran', 'url' => '/admin/payments'], ['label' => 'Webhook Log']]" />
    <x-admin.page-header title="Webhook log" description="Callback provider yang diterima dan status pemrosesannya." />

    <x-admin.filter-bar method="GET" action="/admin/webhooks">
        <div class="admin-field"><label for="webhook-q">Order ID</label><input id="webhook-q" name="q" value="{{ request('q') }}" placeholder="Order ID..."></div>
        <div class="admin-field"><label for="webhook-provider">Provider</label><input id="webhook-provider" name="provider" value="{{ request('provider') }}" placeholder="faspay..."></div>
        <button class="admin-button admin-button--primary" type="submit">Filter</button>
        <a class="admin-button admin-button--quiet" href="/admin/webhooks">Reset</a>
    </x-admin.filter-bar>

    <div style="height: 20px;"></div>
    <x-admin.data-table title="Callback masuk" description="{{ $webhooks->total() }} webhook ditemukan.">
        <thead><tr><th>Provider</th><th>Event</th><th>Order ID</th><th>Signature</th><th>Diproses</th><th>Waktu</th></tr></thead>
        <tbody>
            @forelse ($webhooks as $webhook)
                <tr><td>{{ $webhook->provider }}</td><td>{{ $webhook->event ?: '—' }}</td><td>{{ $webhook->order_id }}</td><td>{{ $webhook->signature ? substr($webhook->signature, 0, 8).'••••' : '—' }}</td><td>@if ($webhook->processed_at)<x-admin.status-badge status="paid" label="Processed" />@else<x-admin.status-badge status="pending" label="Pending" />@endif</td><td>{{ $webhook->created_at?->format('d M Y H:i') }}</td></tr>
            @empty
                <tr><td colspan="6"><x-admin.empty-state title="Belum ada webhook" description="Callback provider akan tampil di sini." /></td></tr>
            @endforelse
        </tbody>
    </x-admin.data-table>
    <x-admin.pagination :current="$webhooks->currentPage()" :total="$webhooks->lastPage()" :label="$webhooks->total().' webhook'" />
@endsection
