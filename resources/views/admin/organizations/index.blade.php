@extends(view()->exists('admin.layouts.app') ? 'admin.layouts.app' : 'admin.review.layout', ['title' => 'Organisasi'])

@section('title', 'Organisasi')

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Organisasi']]" />

    <x-admin.page-header
        title="Review Organisasi"
        description="Verifikasi lembaga sebelum dapat mengelola dan mempublikasikan campaign."
    >
        <span class="admin-pagination" style="padding:0; border:0;">{{ $organizations->total() }} organisasi ditemukan</span>
    </x-admin.page-header>

    <x-admin.filter-bar method="GET" action="{{ route('admin.organizations.index') }}">
        <div class="admin-field">
            <label for="search">Cari</label>
            <input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama, slug, email, telepon">
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
            <label for="location">Lokasi</label>
            <input id="location" type="text" name="location" value="{{ $filters['location'] ?? '' }}" placeholder="Alamat">
        </div>
        <div class="admin-field">
            <label for="date_from">Diajukan dari</label>
            <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="admin-field">
            <label for="date_to">Sampai</label>
            <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="admin-field">
            <label for="per_page">Per halaman</label>
            <select id="per_page" name="per_page">
                @foreach ([15, 25, 50, 100] as $size)
                    <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 15) === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="admin-button admin-button--primary">Terapkan</button>
        <a href="{{ route('admin.organizations.index') }}" class="admin-button admin-button--quiet">Reset</a>
    </x-admin.filter-bar>

    <div style="height: 20px;"></div>

    <x-admin.data-table
        title="Daftar organisasi"
        description="{{ $organizations->total() }} organisasi ditemukan."
    >
        @if ($organizations->isEmpty())
            <tbody>
            <tr>
                <td colspan="7">
                    <x-admin-review.empty-state
                        title="Belum ada organisasi"
                        description="Organisasi yang mengajukan verifikasi akan muncul di sini."
                    />
                </td>
            </tr>
            </tbody>
        @else
            <thead>
            <tr>
                <th>Organisasi</th>
                <th>Kontak</th>
                <th>Status</th>
                <th>Anggota</th>
                <th>Campaign</th>
                <th>Diajukan</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($organizations as $organization)
                <tr>
                    <td>
                        <a href="{{ route('admin.organizations.show', $organization) }}" class="admin-table__primary">{{ $organization->name }}</a>
                        <span class="admin-table__secondary">{{ $organization->slug }}</span>
                    </td>
                    <td>
                        <span class="admin-table__primary">{{ $organization->contact_email ?: '—' }}</span>
                        <span class="admin-table__secondary">{{ $organization->contact_phone ?: '—' }}</span>
                    </td>
                    <td><x-admin-review.status-badge :status="$organization->status" /></td>
                    <td>{{ $organization->members_count }}</td>
                    <td>{{ $organization->campaigns_count }}</td>
                    <td>{{ $organization->created_at?->format('d M Y') ?? '—' }}</td>
                    <td>
                        <a href="{{ route('admin.organizations.show', $organization) }}" class="admin-button admin-button--quiet">Detail</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        @endif
    </x-admin.data-table>

    {{ $organizations->links('admin.review.pagination') }}
@endsection
