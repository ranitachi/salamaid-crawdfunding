@extends('admin.layouts.app', ['title' => 'Role & Permission'])

@section('content')
    <x-admin.breadcrumb :items="[
        ['label' => 'Pengguna & Role', 'url' => route('admin.users.index')],
        ['label' => 'Role'],
    ]" />

    <x-admin.page-header
        title="Role & Permission"
        description="Ringkasan role dari config/admin.php beserta jumlah pengguna dan permission-nya."
    >
        <a class="admin-button admin-button--quiet" href="{{ route('admin.users.index') }}">Kembali ke Pengguna</a>
    </x-admin.page-header>

    @if (session('success'))<x-admin.toast :message="session('success')" />@endif
    @if (session('error'))<x-admin.toast :message="session('error')" type="error" />@endif

    <div class="admin-alert" style="margin-bottom:20px;">
        <span class="admin-alert__icon">!</span>
        <div>
            <strong>Role dikelola melalui konfigurasi</strong>
            <p>Daftar role dan permission berasal dari <code>config/admin.php</code>. Panel tidak menyediakan penghapusan role; role yang masih dipakai tidak dapat dihapus. Perubahan role harus melalui deployment config dan review permission.</p>
        </div>
    </div>

    <x-admin.data-table
        title="Daftar role"
        description="{{ count($roles) }} role terdaftar."
    >
        <thead>
        <tr>
            <th>Role</th>
            <th>Label</th>
            <th>Pengguna</th>
            <th>Aktif</th>
            <th>Permission</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($roles as $role)
            <tr>
                <td><span class="admin-table__primary">{{ $role['role'] }}</span></td>
                <td>{{ $role['label'] }}</td>
                <td>{{ $role['users'] }}</td>
                <td>{{ $role['active'] }}</td>
                <td>
                    @if (in_array('*', $role['permissions'], true))
                        <span class="admin-status admin-status--verified">Semua permission</span>
                    @elseif (empty($role['permissions']))
                        <span class="admin-table__secondary">Tidak ada permission</span>
                    @else
                        <span class="admin-table__secondary">{{ implode(', ', $role['permissions']) }}</span>
                    @endif
                </td>
                <td>
                    @if ($role['users'] > 0)
                        <x-admin.status-badge status="verified" label="Dipakai" />
                    @else
                        <x-admin.status-badge status="draft" label="Tidak dipakai" />
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </x-admin.data-table>
@endsection
