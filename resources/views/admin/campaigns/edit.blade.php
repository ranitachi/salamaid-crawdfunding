@extends('admin.layouts.app', ['title' => 'Edit Campaign'])

@section('content')
<x-admin.breadcrumb :items="[['label' => 'Campaign', 'url' => route('admin.campaigns.index')], ['label' => $campaign->title, 'url' => route('admin.campaigns.show', $campaign)], ['label' => 'Edit'] ]" />
<x-admin.page-header title="Edit Campaign" :description="$campaign->status === 'published' ? 'Perubahan akan mengembalikan campaign ke antrean review.' : 'Perbarui data campaign.'">
    <a class="admin-button admin-button--quiet" href="{{ route('admin.campaigns.show', $campaign) }}">Kembali</a>
</x-admin.page-header>
<form class="admin-panel" method="POST" action="{{ route('admin.campaigns.update', $campaign) }}">
    @csrf @method('PUT')
    <div class="admin-panel__body" style="display:grid;gap:16px">
        <div class="admin-field"><label for="organization_id">Lembaga</label><select id="organization_id" name="organization_id" required>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected(old('organization_id', $campaign->organization_id) === $organization->id)>{{ $organization->name }}</option>@endforeach</select>@error('organization_id')<small class="admin-field__hint" style="color:var(--admin-coral)">{{ $message }}</small>@enderror</div>
        <div class="admin-field"><label for="category_id">Kategori</label><select id="category_id" name="category_id"><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $campaign->category_id) === $category->id)>{{ $category->name }}</option>@endforeach</select></div>
        <div class="admin-field"><label for="title">Judul campaign</label><input id="title" name="title" value="{{ old('title', $campaign->title) }}" required>@error('title')<small class="admin-field__hint" style="color:var(--admin-coral)">{{ $message }}</small>@enderror</div>
        <div class="admin-field"><label for="slug">Slug URL</label><input id="slug" name="slug" value="{{ old('slug', $campaign->slug) }}" required>@error('slug')<small class="admin-field__hint" style="color:var(--admin-coral)">{{ $message }}</small>@enderror</div>
        <div class="admin-field"><label for="description">Deskripsi</label><textarea id="description" name="description" rows="7" required>{{ old('description', $campaign->description) }}</textarea>@error('description')<small class="admin-field__hint" style="color:var(--admin-coral)">{{ $message }}</small>@enderror</div>
        <div class="admin-settings-grid"><div class="admin-field"><label for="target_amount">Target dana</label><input id="target_amount" name="target_amount" type="number" min="1" value="{{ old('target_amount', $campaign->target_amount) }}" required></div><div class="admin-field"><label for="deadline_at">Batas waktu</label><input id="deadline_at" name="deadline_at" type="datetime-local" value="{{ old('deadline_at', $campaign->deadline_at?->format('Y-m-d\\TH:i')) }}"></div></div>
        <div class="admin-settings-grid"><div class="admin-field"><label for="location_label">Lokasi</label><input id="location_label" name="location_label" value="{{ old('location_label', $campaign->location_label) }}"></div><div class="admin-field"><label for="cover_url">URL gambar cover</label><input id="cover_url" name="cover_url" type="url" value="{{ old('cover_url', $campaign->cover_url) }}"></div></div>
    </div>
    <div class="admin-panel__footer" style="display:flex;gap:8px"><button class="admin-button admin-button--primary" type="submit">Simpan Perubahan</button><a class="admin-button admin-button--quiet" href="{{ route('admin.campaigns.show', $campaign) }}">Batal</a></div>
</form>
@endsection
