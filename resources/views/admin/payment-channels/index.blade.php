@extends('admin.layouts.app', ['title' => 'Payment Channel'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Payment Channel']]" />
    <x-admin.page-header title="Payment channel & fee" description="Kelola channel yang digunakan provider dan tarif berdasarkan periode efektif." />
    @if (session('success'))<x-admin.toast :message="session('success')" />@endif
    @if (session('error'))<x-admin.toast :message="session('error')" type="error" />@endif
    <x-admin.data-table title="Channel aktif dan nonaktif" description="{{ $channels->count() }} channel terdaftar.">
        <thead><tr><th>Provider</th><th>Code</th><th>Method</th><th>Status</th><th>Fee aktif</th><th>Aksi</th></tr></thead>
        <tbody>
        @forelse ($channels as $channel)
            <tr><td>{{ $channel->provider }}</td><td>{{ $channel->code }}</td><td>{{ $channel->label }} ({{ $channel->method }})</td><td><x-admin.status-badge :status="$channel->active ? 'paid' : 'disabled'" :label="$channel->active ? 'Aktif' : 'Nonaktif'" /></td><td>{{ $channel->fees->count() }} periode</td><td><form method="POST" action="{{ route($channel->active ? 'admin.payment-channels.deactivate' : 'admin.payment-channels.activate', $channel) }}" style="display:inline">@csrf<button class="admin-button admin-button--quiet" type="submit">{{ $channel->active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></td></tr>
        @empty
            <tr><td colspan="6"><x-admin.empty-state title="Belum ada payment channel" description="Tambahkan channel melalui seeder atau konfigurasi admin." /></td></tr>
        @endforelse
        </tbody>
    </x-admin.data-table>
@endsection
