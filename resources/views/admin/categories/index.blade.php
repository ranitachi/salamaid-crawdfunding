@extends('admin.layouts.app', ['title' => 'Kategori'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Kategori']]" />

    <x-admin.page-header
        title="Kategori Campaign"
        description="Kelola kategori, slug unik, dan status aktif untuk campaign."
    >
        <a class="admin-button admin-button--primary" href="{{ route('admin.categories.create') }}">+ Tambah Kategori</a>
    </x-admin.page-header>

    @if (session('success'))<x-admin.toast :message="session('success')" />@endif
    @if (session('error'))<x-admin.toast :message="session('error')" type="error" />@endif

    <x-admin.filter-bar method="GET" action="{{ route('admin.categories.index') }}">
        <div class="admin-field">
            <label for="search">Cari</label>
            <input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama atau slug">
        </div>
        <div class="admin-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua status</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
            </select>
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
        <a href="{{ route('admin.categories.index') }}" class="admin-button admin-button--quiet">Reset</a>
    </x-admin.filter-bar>

    <div style="height:20px;"></div>

    <x-admin.data-table
        title="Daftar kategori"
        description="{{ $categories->total() }} kategori ditemukan."
    >
        @if ($categories->isEmpty())
            <tbody>
            <tr>
                <td colspan="6">
                    <x-admin.empty-state
                        title="Belum ada kategori"
                        description="Tambahkan kategori pertama agar campaign dapat dikelompokkan."
                    >
                        <a class="admin-button admin-button--primary" href="{{ route('admin.categories.create') }}">Tambah Kategori</a>
                    </x-admin.empty-state>
                </td>
            </tr>
            </tbody>
        @else
            <thead>
            <tr>
                <th>Kategori</th>
                <th>Ikon</th>
                <th>Campaign</th>
                <th>Status</th>
                <th>Diperbarui</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($categories as $category)
                <tr>
                    <td>
                        <a href="{{ route('admin.categories.edit', $category) }}" class="admin-table__primary">{{ $category->name }}</a>
                        <span class="admin-table__secondary">{{ $category->slug }}</span>
                    </td>
                    <td>{{ $category->icon ?: '—' }}</td>
                    <td>{{ $category->campaigns_count }}</td>
                    <td>
                        @if ($category->is_active)
                            <x-admin.status-badge status="verified" label="Aktif" />
                        @else
                            <x-admin.status-badge status="suspended" label="Nonaktif" />
                        @endif
                    </td>
                    <td>{{ $category->updated_at?->format('d M Y') ?? '—' }}</td>
                    <td>
                        <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="admin-button admin-button--quiet">Edit</a>

                            @if ($category->is_active)
                                <form method="POST" action="{{ route('admin.categories.deactivate', $category) }}">
                                    @csrf
                                    <button type="submit" class="admin-button admin-button--quiet">Nonaktifkan</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.categories.activate', $category) }}">
                                    @csrf
                                    <button type="submit" class="admin-button admin-button--quiet">Aktifkan</button>
                                </form>
                            @endif

                            @if ($category->campaigns_count > 0)
                                <button type="button" class="admin-button admin-button--quiet" disabled title="Kategori masih dipakai campaign dan tidak dapat dihapus.">Hapus</button>
                            @else
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-button admin-button--danger">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        @endif
    </x-admin.data-table>

    {{ $categories->links('admin.review.pagination') }}
@endsection
