@extends('admin.layouts.app', ['title' => 'Kategori Bantuan'])

@section('content')
<x-admin.breadcrumb :items="[['label' => 'Pusat Bantuan', 'url' => route('admin.help.index')], ['label' => 'Kategori']]" />
<x-admin.page-header title="Master Kategori Bantuan" description="Gunakan kategori yang konsisten pada seluruh artikel bantuan publik.">
    <a class="admin-button admin-button--quiet" href="{{ route('admin.help.index') }}">Kembali ke Artikel</a>
</x-admin.page-header>
@if(session('success'))<x-admin.toast :message="session('success')" />@endif
@if(session('error'))<div class="admin-alert admin-alert--danger">{{ session('error') }}</div>@endif
<div class="admin-help-category-layout">
    <form class="admin-panel" method="POST" action="{{ route('admin.help.categories.store') }}">
        @csrf
        <div class="admin-panel__header"><div><h2>Tambah kategori</h2><p>Tambahkan pilihan kategori untuk artikel bantuan.</p></div></div>
        <div class="admin-panel__body admin-help-category-form"><div class="admin-field"><label for="name">Nama kategori</label><input id="name" name="name" value="{{ old('name') }}" maxlength="80" required>@error('name')<small class="admin-field__hint" style="color:var(--admin-coral)">{{ $message }}</small>@enderror</div><div class="admin-field"><label for="sort_order">Urutan tampilan</label><input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', 0) }}"></div><label class="admin-checkbox"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked> Aktif digunakan</label></div>
        <div class="admin-panel__footer"><button class="admin-button admin-button--primary" type="submit">Tambah Kategori</button></div>
    </form>
    <x-admin.data-table title="Daftar kategori" description="{{ $categories->count() }} kategori terdaftar.">
        <thead><tr><th>Kategori</th><th>Artikel</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @foreach($categories as $category)<tr><td><form method="POST" action="{{ route('admin.help.categories.update', $category) }}" class="admin-help-category-inline">@csrf @method('PATCH')<label class="admin-sr-only" for="category-name-{{ $category->id }}">Nama kategori</label><input id="category-name-{{ $category->id }}" class="admin-help-category-inline__name" name="name" value="{{ $category->name }}" maxlength="80" required><label class="admin-sr-only" for="category-order-{{ $category->id }}">Urutan</label><input id="category-order-{{ $category->id }}" class="admin-help-category-inline__order" name="sort_order" type="number" min="0" max="9999" value="{{ $category->sort_order }}"><label class="admin-help-category-inline__active"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Aktif</label><button class="admin-button admin-button--quiet" type="submit">Simpan</button></form></td><td>{{ $category->articles_count }}</td><td>@if($category->is_active)<x-admin.status-badge status="verified" label="Aktif" />@else<x-admin.status-badge status="suspended" label="Nonaktif" />@endif</td><td>@if($category->articles_count === 0)<form method="POST" action="{{ route('admin.help.categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini?');">@csrf @method('DELETE')<button class="admin-button admin-button--danger" type="submit">Hapus</button></form>@else<span class="admin-table__secondary">Sedang digunakan</span>@endif</td></tr>@endforeach
        </tbody>
    </x-admin.data-table>
</div>
@endsection
