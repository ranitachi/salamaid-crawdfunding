@extends('admin.layouts.app', ['title' => 'Transparansi'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Transparansi']]" />

    <x-admin.page-header
        title="Transparansi & laporan"
        description="Review update campaign dan laporan penggunaan dana sebelum tampil ke publik."
    >
        @can('create', \Modules\Transparency\Entities\CampaignUpdate::class)
            <a class="admin-button admin-button--primary" href="{{ route('admin.transparency.create') }}">+ Catat update</a>
        @endcan
    </x-admin.page-header>

    <div class="admin-stat-grid">
        <x-admin.stat-card label="Menunggu review" :value="$counts['under_review']" icon="!" tone="yellow" />
        <x-admin.stat-card label="Disetujui, belum publik" :value="$counts['approved']" icon="✓" tone="blue" />
        <x-admin.stat-card label="Perlu revisi" :value="$counts['rejected']" icon="↺" tone="coral" />
        <x-admin.stat-card label="Dipublikasikan" :value="$counts['published']" icon="✦" tone="lime" />
    </div>

    <x-admin.filter-bar method="GET" action="{{ route('admin.transparency.index') }}">
        <div class="admin-field">
            <label for="search">Cari</label>
            <input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Judul update atau campaign">
        </div>
        <div class="admin-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="admin-field">
            <label for="type">Jenis</label>
            <select id="type" name="type">
                <option value="">Semua jenis</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="admin-field"><label for="date_from">Dari</label><input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
        <div class="admin-field"><label for="date_to">Sampai</label><input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
        <button class="admin-button admin-button--primary" type="submit">Filter</button>
        <a class="admin-button admin-button--quiet" href="{{ route('admin.transparency.index') }}">Reset</a>
    </x-admin.filter-bar>

    <div style="height: 20px;"></div>

    <x-admin.data-table title="Daftar update" description="{{ $updates->total() }} update ditemukan.">
        <thead>
            <tr><th>Update</th><th>Campaign</th><th>Jenis</th><th>Bukti</th><th>Status</th><th>Dibuat</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($updates as $update)
                <tr>
                    <td><span class="admin-table__primary">{{ $update->title }}</span>
                        @if ($update->amount_used)<span class="admin-table__secondary">Rp {{ number_format($update->amount_used, 0, ',', '.') }}</span>@endif</td>
                    <td><span class="admin-table__primary">{{ $update->campaign?->title ?? 'Campaign tidak tersedia' }}</span>
                        <span class="admin-table__secondary">{{ $update->campaign?->organization?->name ?? '—' }}</span></td>
                    <td>{{ $update->typeEnum()?->label() ?? $update->type }}</td>
                    <td>{{ $update->attachments_count }}</td>
                    <td><x-admin.status-badge :status="$update->status" :label="$update->statusEnum()?->label()" /></td>
                    <td>{{ $update->created_at?->format('d M Y H:i') }}</td>
                    <td><a class="admin-button admin-button--quiet" href="{{ route('admin.transparency.show', $update) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-admin.empty-state title="Belum ada update" description="Tidak ada update atau laporan yang sesuai filter." /></td></tr>
            @endforelse
        </tbody>
    </x-admin.data-table>

    {{ $updates->links('admin.review.pagination') }}
@endsection
