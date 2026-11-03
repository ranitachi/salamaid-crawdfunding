@extends('admin.layouts.app', ['title' => 'Pusat Bantuan'])

@section('content')
<x-admin.breadcrumb :items="[['label' => 'Pusat Bantuan']]" />
<x-admin.page-header title="Pusat Bantuan Publik" description="Kelola pertanyaan dan jawaban yang tampil pada halaman bantuan publik.">
    <a class="admin-button admin-button--primary" href="{{ route('admin.help.create') }}">+ Tambah Artikel</a>
</x-admin.page-header>
@if (session('success'))<x-admin.toast :message="session('success')" />@endif
<x-admin.filter-bar method="GET" action="{{ route('admin.help.index') }}">
    <div class="admin-field"><label for="search">Cari</label><input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Pertanyaan atau kategori"></div>
    <div class="admin-field"><label for="status">Status</label><select id="status" name="status"><option value="">Semua status</option><option value="published" @selected(($filters['status'] ?? '') === 'published')>Publikasi</option><option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option></select></div>
    <button class="admin-button admin-button--primary" type="submit">Terapkan</button><a class="admin-button admin-button--quiet" href="{{ route('admin.help.index') }}">Reset</a>
</x-admin.filter-bar>
<div style="height:20px"></div>
<x-admin.data-table title="Artikel bantuan" description="{{ $articles->total() }} artikel ditemukan.">
    @if ($articles->isEmpty())<tbody><tr><td colspan="5"><x-admin.empty-state title="Belum ada artikel bantuan" description="Tambahkan artikel agar halaman bantuan publik memiliki informasi yang relevan."><a class="admin-button admin-button--primary" href="{{ route('admin.help.create') }}">Tambah Artikel</a></x-admin.empty-state></td></tr></tbody>
    @else
    <thead><tr><th>Pertanyaan</th><th>Kategori</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    @foreach ($articles as $article)<tr><td><a class="admin-table__primary" href="{{ route('admin.help.edit', $article) }}">{{ $article->question }}</a><span class="admin-table__secondary">{{ str($article->answer)->squish()->limit(100) }}</span></td><td>{{ $article->category }}</td><td>{{ $article->sort_order }}</td><td>@if($article->is_published)<x-admin.status-badge status="verified" label="Publik" />@else<x-admin.status-badge status="suspended" label="Draft" />@endif</td><td><div style="display:flex;gap:6px;flex-wrap:wrap"><a class="admin-button admin-button--quiet" href="{{ route('admin.help.edit', $article) }}">Edit</a><form method="POST" action="{{ route('admin.help.destroy', $article) }}" onsubmit="return confirm('Hapus artikel ini?');">@csrf @method('DELETE')<button class="admin-button admin-button--danger" type="submit">Hapus</button></form></div></td></tr>@endforeach
    </tbody>
    @endif
</x-admin.data-table>
{{ $articles->links('admin.review.pagination') }}
@endsection
