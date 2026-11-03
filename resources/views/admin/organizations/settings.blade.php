@extends(view()->exists('admin.layouts.app') ? 'admin.layouts.app' : 'admin.review.layout', ['title' => 'Profil '.$organization->name])

@section('title', 'Profil '.$organization->name)

@section('content')
    <x-admin.breadcrumb :items="[
        ['label' => 'Organisasi', 'url' => route('admin.organizations.index')],
        ['label' => $organization->name, 'url' => route('admin.organizations.show', $organization)],
        ['label' => 'Profil publik'],
    ]" />

    <x-admin.page-header title="Profil publik lembaga" :description="$organization->name">
        <a href="{{ route('admin.organizations.show', $organization) }}" class="admin-button admin-button--quiet">Kembali ke detail</a>
    </x-admin.page-header>

    @if(session('success'))<div class="admin-alert admin-alert--success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="admin-alert admin-alert--danger" role="alert">Periksa kembali data yang diisi.</div>@endif

    <form method="POST" action="{{ route('admin.organizations.settings.update', $organization) }}" enctype="multipart/form-data" class="admin-grid">
        @csrf @method('PUT')
        <section class="admin-panel">
            <div class="admin-panel__header"><div><h2>Identitas publik</h2><p>Informasi ini akan tampil pada halaman profil dan campaign lembaga.</p></div></div>
            <div class="admin-panel__body" style="display:grid; gap:14px;">
                <div class="admin-field"><label for="tagline">Tagline</label><input id="tagline" name="tagline" value="{{ old('tagline', $organization->tagline) }}" maxlength="160" placeholder="Contoh: Bersama menghadirkan dampak nyata">@error('tagline')<small class="admin-field__hint" style="color:var(--admin-coral)">{{ $message }}</small>@enderror</div>
                <div class="admin-field"><label for="description">Deskripsi lembaga</label><textarea id="description" name="description" rows="6" maxlength="10000">{{ old('description', $organization->description) }}</textarea>@error('description')<small class="admin-field__hint" style="color:var(--admin-coral)">{{ $message }}</small>@enderror</div>
                <div class="admin-field"><label for="address">Alamat</label><textarea id="address" name="address" rows="3" maxlength="1000">{{ old('address', $organization->address) }}</textarea>@error('address')<small class="admin-field__hint" style="color:var(--admin-coral)">{{ $message }}</small>@enderror</div>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__header"><div><h2>Branding lembaga</h2><p>Gunakan gambar yang jelas dan warna dengan kontras yang baik.</p></div></div>
            <div class="admin-panel__body" style="display:grid; gap:14px;">
                <div class="admin-field"><label for="logo">Logo</label><input id="logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.webp,.svg">@if($organization->logo_url)<img src="{{ $organization->logo_url }}" alt="Logo {{ $organization->name }}" style="display:block; width:72px; height:72px; margin-top:8px; border-radius:12px; object-fit:cover;">@endif @error('logo')<small class="admin-field__hint" style="color:var(--admin-coral)">{{ $message }}</small>@enderror</div>
                <div class="admin-field"><label for="banner">Banner profil</label><input id="banner" name="banner" type="file" accept=".png,.jpg,.jpeg,.webp">@if($organization->banner_url)<img src="{{ $organization->banner_url }}" alt="Banner {{ $organization->name }}" style="display:block; width:100%; max-height:120px; margin-top:8px; border-radius:12px; object-fit:cover;">@endif @error('banner')<small class="admin-field__hint" style="color:var(--admin-coral)">{{ $message }}</small>@enderror</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;"><div class="admin-field"><label for="primary_color">Warna utama</label><input id="primary_color" name="primary_color" type="color" value="{{ old('primary_color', $organization->primary_color ?: '#BC0004') }}"></div><div class="admin-field"><label for="secondary_color">Warna sekunder</label><input id="secondary_color" name="secondary_color" type="color" value="{{ old('secondary_color', $organization->secondary_color ?: '#FFF0EF') }}"></div></div>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__header"><div><h2>Kontak dan kanal</h2><p>Kontak yang dapat dilihat publik.</p></div></div>
            <div class="admin-panel__body" style="display:grid; gap:14px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;"><div class="admin-field"><label for="contact_email">Email</label><input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $organization->contact_email) }}"></div><div class="admin-field"><label for="contact_phone">Nomor telepon/WhatsApp</label><input id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $organization->contact_phone) }}"></div></div>
                <div class="admin-field"><label for="website_url">Website</label><input id="website_url" name="website_url" type="url" value="{{ old('website_url', $organization->website_url) }}" placeholder="https://"></div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;"><div class="admin-field"><label for="instagram_url">Instagram</label><input id="instagram_url" name="instagram_url" type="url" value="{{ old('instagram_url', $organization->instagram_url) }}" placeholder="https://instagram.com/"></div><div class="admin-field"><label for="facebook_url">Facebook</label><input id="facebook_url" name="facebook_url" type="url" value="{{ old('facebook_url', $organization->facebook_url) }}" placeholder="https://facebook.com/"></div></div>
                <div class="admin-field"><label for="whatsapp_url">WhatsApp link</label><input id="whatsapp_url" name="whatsapp_url" type="url" value="{{ old('whatsapp_url', $organization->whatsapp_url) }}" placeholder="https://wa.me/"></div>
                <div><button type="submit" class="admin-button admin-button--primary">Simpan profil publik</button></div>
            </div>
        </section>
    </form>
@endsection
