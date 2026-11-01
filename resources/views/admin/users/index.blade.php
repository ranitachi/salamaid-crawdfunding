@extends('admin.layouts.app', ['title' => 'Pengguna & Role'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Pengguna & Role']]" />

    <x-admin.page-header
        title="Pengguna & Role"
        description="Kelola akun admin, role, status aktif, dan pengiriman tautan reset password."
    >
        <a class="admin-button admin-button--quiet" href="{{ route('admin.roles.index') }}">Lihat Role</a>
    </x-admin.page-header>

    @if (session('success'))<x-admin.toast :message="session('success')" />@endif
    @if (session('error'))<x-admin.toast :message="session('error')" type="error" />@endif

    <x-admin.filter-bar method="GET" action="{{ route('admin.users.index') }}">
        <div class="admin-field">
            <label for="search">Cari</label>
            <input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama atau email">
        </div>
        <div class="admin-field">
            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="">Semua role</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
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
        <a href="{{ route('admin.users.index') }}" class="admin-button admin-button--quiet">Reset</a>
    </x-admin.filter-bar>

    <div style="height:20px;"></div>

    <x-admin.data-table
        title="Daftar pengguna"
        description="{{ $users->total() }} pengguna ditemukan."
    >
        @if ($users->isEmpty())
            <tbody>
            <tr>
                <td colspan="5">
                    <x-admin.empty-state
                        title="Belum ada pengguna"
                        description="Pengguna yang sesuai dengan filter belum tersedia."
                    />
                </td>
            </tr>
            </tbody>
        @else
            <thead>
            <tr>
                <th>Pengguna</th>
                <th>Role</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>
                        <a href="{{ route('admin.users.show', $user) }}" class="admin-table__primary">{{ $user->name }}</a>
                        <span class="admin-table__secondary">{{ $user->email }}</span>
                    </td>
                    <td>{{ $roles[$user->role] ?? $user->role }}</td>
                    <td>
                        @if ($user->is_active)
                            <x-admin.status-badge status="verified" label="Aktif" />
                        @else
                            <x-admin.status-badge status="suspended" label="Nonaktif" />
                        @endif
                    </td>
                    <td>{{ $user->created_at?->format('d M Y') ?? '—' }}</td>
                    <td>
                        <a href="{{ route('admin.users.show', $user) }}" class="admin-button admin-button--quiet">Detail</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        @endif
    </x-admin.data-table>

    {{ $users->links('admin.review.pagination') }}
@endsection
