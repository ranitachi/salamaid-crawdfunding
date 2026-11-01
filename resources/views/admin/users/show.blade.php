@extends('admin.layouts.app', ['title' => $user->name])

@section('content')
    <x-admin.breadcrumb :items="[
        ['label' => 'Pengguna & Role', 'url' => route('admin.users.index')],
        ['label' => $user->name],
    ]" />

    <x-admin.page-header :title="$user->name" :description="$user->email">
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            @if ($user->is_active)
                <x-admin.status-badge status="verified" label="Aktif" />
            @else
                <x-admin.status-badge status="suspended" label="Nonaktif" />
            @endif
            <span class="admin-status admin-status--draft">{{ $roles[$user->role] ?? $user->role }}</span>
        </div>
    </x-admin.page-header>

    @if (session('success'))<x-admin.toast :message="session('success')" />@endif
    @if (session('error'))<x-admin.toast :message="session('error')" type="error" />@endif

    <div class="admin-grid">
        <section class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h2>Informasi Akun</h2>
                    <p>Data dasar akun admin dan status aksesnya.</p>
                </div>
            </div>
            <div class="admin-panel__body">
                <dl class="admin-detail-list">
                    <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                    <div><dt>Role</dt><dd>{{ $roles[$user->role] ?? $user->role }}</dd></div>
                    <div><dt>Status</dt><dd>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</dd></div>
                    <div><dt>Email terverifikasi</dt><dd>{{ $user->email_verified_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                    <div><dt>Dibuat</dt><dd>{{ $user->created_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                    <div><dt>Diperbarui</dt><dd>{{ $user->updated_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                </dl>

                <div style="margin-top:20px;">
                    <div class="admin-table__secondary">Ubah role</div>
                    <form method="POST" action="{{ route('admin.users.role.update', $user) }}" style="display:flex; gap:8px; align-items:end; flex-wrap:wrap; margin-top:8px;">
                        @csrf
                        @method('PATCH')
                        <div class="admin-field">
                            <label for="role">Role baru</label>
                            <select id="role" name="role" @disabled($isSelf)>
                                @foreach ($roles as $value => $label)
                                    <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="admin-button admin-button--primary" @disabled($isSelf)>Simpan Role</button>
                    </form>
                    @error('role')
                        <div style="margin-top:6px; color:var(--admin-coral); font-size:11px;">{{ $message }}</div>
                    @enderror
                    @if ($isSelf)
                        <p class="admin-table__secondary" style="margin-top:8px;">Role akun sendiri tidak dapat diubah dari halaman ini.</p>
                    @endif
                </div>
            </div>
        </section>

        <aside class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h2>Aksi Akun</h2>
                    <p>Aktivasi, nonaktifasi, dan reset password.</p>
                </div>
            </div>
            <div class="admin-panel__body">
                <div style="display:grid; gap:14px;">
                    <div>
                        <div class="admin-table__secondary">Status akses</div>
                        @if ($user->is_active)
                            @if ($isSelf)
                                <p class="admin-table__secondary" style="margin-top:8px;">Akun sendiri tidak dapat dinonaktifkan.</p>
                            @else
                                <form method="POST" action="{{ route('admin.users.deactivate', $user) }}" onsubmit="return confirm('Nonaktifkan pengguna ini? Pengguna tidak dapat login sampai diaktifkan kembali.');" style="margin-top:8px;">
                                    @csrf
                                    <button type="submit" class="admin-button admin-button--danger">Nonaktifkan Pengguna</button>
                                </form>
                            @endif
                        @else
                            <form method="POST" action="{{ route('admin.users.activate', $user) }}" style="margin-top:8px;">
                                @csrf
                                <button type="submit" class="admin-button admin-button--primary">Aktifkan Pengguna</button>
                            </form>
                        @endif
                    </div>

                    <div>
                        <div class="admin-table__secondary">Reset password</div>
                        <p class="admin-table__secondary" style="margin-top:6px;">Kirim tautan reset ke email pengguna. Password tidak pernah ditampilkan atau diubah langsung dari panel.</p>
                        <form method="POST" action="{{ route('admin.users.password-reset', $user) }}" onsubmit="return confirm('Kirim tautan reset password ke {{ $user->email }}?');" style="margin-top:8px;">
                            @csrf
                            <button type="submit" class="admin-button admin-button--quiet">Kirim Tautan Reset</button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div style="height:20px;"></div>

    <x-admin.data-table
        title="Keanggotaan Organisasi"
        description="{{ $memberships->count() }} keanggotaan ditemukan."
    >
        @if ($memberships->isEmpty())
            <tbody>
            <tr>
                <td colspan="3">
                    <x-admin.empty-state
                        title="Belum ada keanggotaan organisasi"
                        description="Pengguna ini belum terhubung ke organisasi mana pun."
                    />
                </td>
            </tr>
            </tbody>
        @else
            <thead>
            <tr>
                <th>Organisasi</th>
                <th>Role</th>
                <th>Bergabung</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($memberships as $membership)
                <tr>
                    <td>
                        <span class="admin-table__primary">{{ $membership->organization?->name ?? '—' }}</span>
                        <span class="admin-table__secondary">{{ $membership->organization?->slug }}</span>
                    </td>
                    <td>{{ $membership->role }}</td>
                    <td>{{ $membership->created_at?->format('d M Y') ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        @endif
    </x-admin.data-table>
@endsection
