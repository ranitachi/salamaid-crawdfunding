@extends('admin.layouts.app', ['title' => 'Rekonsiliasi payout'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Payout', 'url' => route('admin.payouts.index')], ['label' => 'Rekonsiliasi']]" />
    <x-admin.page-header title="Rekonsiliasi settlement" description="Bandingkan donasi settled dengan dana payout yang direservasi dan sudah dibayar." />
    @if (session('success'))<x-admin.toast :message="session('success')" />@endif
    <x-admin.data-table title="Saldo per organisasi" description="Baris merah membutuhkan pemeriksaan finance.">
        <thead><tr><th>Organisasi</th><th>Status</th><th>Settled</th><th>Reserved</th><th>Paid payout</th><th>Tersedia</th><th>Flag</th></tr></thead>
        <tbody>
        @forelse($organizations as $organization)
            <tr>
                <td><span class="admin-table__primary">{{ $organization->name }}</span><span class="admin-table__secondary">{{ $organization->id }}</span></td>
                <td><x-admin.status-badge :status="$organization->status" /></td>
                <td>Rp {{ number_format($organization->settled_amount, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($organization->reserved_amount, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($organization->paid_payout_amount, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($organization->available_amount, 0, ',', '.') }}</td>
                <td>
                    @if($organization->has_mismatch || $organization->has_verification_issue)
                        <x-admin.status-badge status="failed" />
                    @else
                        <x-admin.status-badge status="paid" />
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7"><x-admin.empty-state title="Belum ada organisasi" description="Tidak ada data untuk direkonsiliasi." /></td></tr>
        @endforelse
        </tbody>
    </x-admin.data-table>
@endsection
