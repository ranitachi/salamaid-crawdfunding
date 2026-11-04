@extends('admin.layouts.app', ['title' => 'Master Lokasi'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Pengaturan', 'url' => route('admin.settings.index')], ['label' => 'Master lokasi']]" />
    <x-admin.page-header title="Master provinsi & kabupaten/kota" description="Kelola pilihan lokasi yang digunakan pada pendaftaran mitra Salamaid.">
        <a class="admin-button admin-button--quiet" href="{{ route('admin.settings.index') }}">Kembali ke pengaturan</a>
    </x-admin.page-header>

    @if (session('success'))<x-admin.toast :message="session('success')" />@endif
    @if ($errors->any())<x-admin.toast :message="$errors->first()" type="error" />@endif

    <div class="admin-grid">
        <form class="admin-panel" method="POST" action="{{ route('admin.settings.locations.provinces.store') }}">
            @csrf
            <div class="admin-panel__header"><div><h2>Tambah provinsi</h2><p>Provinsi nonaktif tidak muncul pada form publik.</p></div></div>
            <div class="admin-panel__body admin-settings-grid">
                <div class="admin-field"><label for="province-code">Kode</label><input id="province-code" name="code" placeholder="Contoh: 31" required></div>
                <div class="admin-field"><label for="province-name">Nama provinsi</label><input id="province-name" name="name" placeholder="Contoh: DKI Jakarta" required></div>
            </div>
            <div class="admin-panel__body" style="border-top:1px solid var(--admin-line);"><button class="admin-button admin-button--primary" type="submit">Tambah provinsi</button></div>
        </form>
        <form class="admin-panel" method="POST" action="{{ route('admin.settings.locations.cities.store') }}">
            @csrf
            <div class="admin-panel__header"><div><h2>Tambah kabupaten/kota</h2><p>Data akan tampil setelah provinsinya dipilih.</p></div></div>
            <div class="admin-panel__body admin-settings-grid">
                <div class="admin-field"><label for="city-province">Provinsi</label><select id="city-province" name="province_id" required><option value="">Pilih provinsi</option>@foreach($provinces as $province)<option value="{{ $province->id }}">{{ $province->name }}</option>@endforeach</select></div>
                <div class="admin-field"><label for="city-type">Jenis</label><select id="city-type" name="type" required><option value="Kabupaten">Kabupaten</option><option value="Kota">Kota</option></select></div>
                <div class="admin-field" style="grid-column:1 / -1;"><label for="city-name">Nama kabupaten/kota</label><input id="city-name" name="name" placeholder="Contoh: Jakarta Selatan" required></div>
            </div>
            <div class="admin-panel__body" style="border-top:1px solid var(--admin-line);"><button class="admin-button admin-button--primary" type="submit">Tambah kabupaten/kota</button></div>
        </form>
    </div>

    <div style="height:20px"></div>
    <x-admin.data-table title="Daftar master lokasi" description="{{ $provinces->count() }} provinsi terdaftar.">
        <thead><tr><th>Provinsi</th><th>Jumlah kota/kabupaten</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        @foreach ($provinces as $province)
            <tr><td><span class="admin-table__primary">{{ $province->name }}</span><span class="admin-table__secondary">Kode {{ $province->code }}</span></td><td>{{ $province->cities->count() }}</td><td><x-admin.status-badge :status="$province->is_active ? 'Aktif' : 'Nonaktif'" :label="$province->is_active ? 'Aktif' : 'Nonaktif'" /></td><td><form method="POST" action="{{ route('admin.settings.locations.provinces.toggle', $province) }}">@csrf @method('PATCH')<button class="admin-button admin-button--quiet" type="submit">{{ $province->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></td></tr>
            @foreach ($province->cities as $city)
                <tr><td style="padding-left:32px">{{ $city->type }} {{ $city->name }}</td><td>—</td><td><x-admin.status-badge :status="$city->is_active ? 'Aktif' : 'Nonaktif'" :label="$city->is_active ? 'Aktif' : 'Nonaktif'" /></td><td><form method="POST" action="{{ route('admin.settings.locations.cities.toggle', $city) }}">@csrf @method('PATCH')<button class="admin-button admin-button--quiet" type="submit">{{ $city->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></td></tr>
            @endforeach
        @endforeach
        </tbody>
    </x-admin.data-table>
@endsection
