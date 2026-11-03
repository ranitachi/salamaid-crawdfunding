@extends('admin.layouts.app', ['title' => 'Donatur Lembaga'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Donatur Lembaga']]" />
    <x-admin.page-header title="Donatur lembaga" description="Daftar seluruh donasi pada campaign yang berada dalam cakupan akses Anda." />

    <x-admin.filter-bar method="GET" :action="route('admin.donors.index')">
        <div class="admin-field"><label for="donor-q">Cari donatur/campaign</label><input id="donor-q" name="q" value="{{ request('q') }}" placeholder="Nama, email, nomor HP, campaign"></div>
        <div class="admin-field"><label for="donor-status">Status</label><select id="donor-status" name="status"><option value="">Semua status</option>@foreach (config('admin.statuses.donation') as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
        <button class="admin-button admin-button--primary" type="submit">Filter</button>
        <a class="admin-button admin-button--quiet" href="{{ route('admin.donors.index') }}">Reset</a>
    </x-admin.filter-bar>

    <div style="height:20px"></div>
    <x-admin.data-table title="Daftar donatur" description="{{ $donations->total() }} transaksi donasi ditemukan.">
        <thead><tr><th>Nama</th><th>Email</th><th>No HP</th><th>Campaign</th><th>Nominal</th><th>Status</th><th>Waktu</th></tr></thead>
        <tbody>
        @forelse ($donations as $donation)
            <tr>
                <td>{{ $donation->anonymous ? 'Donatur anonim' : ($donation->user?->name ?? 'Guest donor') }}</td>
                <td>{{ $donation->anonymous ? '—' : ($donation->user?->email ?? '—') }}</td>
                <td>{{ $donation->anonymous ? '—' : ($donation->user?->mobile ?? '—') }}</td>
                <td>{{ $donation->campaign?->title ?? '—' }}</td>
                <td>Rp {{ number_format((int) $donation->gross_amount, 0, ',', '.') }}</td>
                <td><x-admin.status-badge :status="$donation->status" /></td>
                <td>{{ $donation->created_at?->format('d M Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="7"><x-admin.empty-state title="Belum ada donatur" description="Belum ada donasi yang sesuai cakupan dan filter." /></td></tr>
        @endforelse
        </tbody>
    </x-admin.data-table>
    <x-admin.pagination :current="$donations->currentPage()" :total="$donations->lastPage()" :label="$donations->total().' transaksi'" />
@endsection
