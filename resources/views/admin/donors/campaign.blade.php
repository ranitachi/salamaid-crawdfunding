@extends('admin.layouts.app', ['title' => 'Donatur Campaign'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Campaign', 'url' => route('admin.campaigns.index')], ['label' => 'Donatur']]" />
    <x-admin.page-header title="Donatur campaign" :description="$campaign->title.' · '.($campaign->organization?->name ?? 'Organisasi tidak tersedia')">
        <a class="admin-button admin-button--quiet" href="{{ route('admin.campaigns.index') }}">← Kembali</a>
    </x-admin.page-header>

    <x-admin.data-table title="Daftar donatur" description="{{ $donations->total() }} transaksi donasi ditemukan.">
        <thead><tr><th>Nama</th><th>Email</th><th>No HP</th><th>Nominal</th><th>Status</th><th>Waktu</th></tr></thead>
        <tbody>
        @forelse ($donations as $donation)
            <tr>
                <td>{{ $donation->anonymous ? 'Donatur anonim' : ($donation->user?->name ?? 'Guest donor') }}</td>
                <td>{{ $donation->anonymous ? '—' : ($donation->user?->email ?? '—') }}</td>
                <td>{{ $donation->anonymous ? '—' : ($donation->user?->mobile ?? '—') }}</td>
                <td>Rp {{ number_format((int) $donation->gross_amount, 0, ',', '.') }}</td>
                <td><x-admin.status-badge :status="$donation->status" /></td>
                <td>{{ $donation->created_at?->format('d M Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="6"><x-admin.empty-state title="Belum ada donatur" description="Campaign ini belum memiliki transaksi donasi." /></td></tr>
        @endforelse
        </tbody>
    </x-admin.data-table>
    <x-admin.pagination :current="$donations->currentPage()" :total="$donations->lastPage()" :label="$donations->total().' transaksi'" />
@endsection
