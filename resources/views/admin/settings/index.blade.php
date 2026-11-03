@extends('admin.layouts.app', ['title' => 'Pengaturan'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Pengaturan']]" />

    <x-admin.page-header title="Pengaturan aplikasi" description="Kelola identitas, warna, logo, dan informasi kontak yang digunakan pada panel Salamaid.">
        <span class="admin-status admin-status--approved">Hanya Super Admin</span>
    </x-admin.page-header>

    @if (session('success'))<x-admin.toast :message="session('success')" />@endif
    @if ($errors->any())<x-admin.toast :message="$errors->first()" type="error" />@endif

    <form class="admin-panel" method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="admin-panel__header"><div><h2>Identitas &amp; tampilan</h2><p>Nilai disimpan di database dan digunakan pada login serta seluruh shell admin.</p></div></div>
        <div class="admin-panel__body admin-settings-grid">
            <div class="admin-field"><label for="app_name">Nama aplikasi</label><input id="app_name" name="app_name" value="{{ old('app_name', $settings['app_name']) }}" required></div>
            <div class="admin-field"><label for="app_tagline">Tagline</label><input id="app_tagline" name="app_tagline" value="{{ old('app_tagline', $settings['app_tagline']) }}"></div>
            <div class="admin-field"><label for="public_tagline">Tagline publik</label><input id="public_tagline" name="public_tagline" value="{{ old('public_tagline', $settings['public_tagline'] ?? 'Jembatan Kebaikan') }}"><small class="admin-field__hint">Digunakan pada header halaman publik.</small></div>
            <div class="admin-field"><label for="brand_icon">Icon teks</label><input id="brand_icon" name="brand_icon" value="{{ old('brand_icon', $settings['brand_icon']) }}" maxlength="10" required><small class="admin-field__hint">Dipakai jika logo URL kosong.</small></div>
            <div class="admin-field"><label for="logo">Upload logo</label><input id="logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.webp,.svg"><small class="admin-field__hint">PNG, JPG, WEBP, atau SVG; maksimal 2 MB. @if(!empty($settings['logo_url']))Logo saat ini tersimpan.@elseBelum ada logo, gunakan icon teks.@endif</small></div>
            <div class="admin-field"><label for="favicon">Upload favicon</label><input id="favicon" name="favicon" type="file" accept=".png,.jpg,.jpeg,.webp,.ico,.svg"><small class="admin-field__hint">PNG, JPG, WEBP, ICO, atau SVG; maksimal 512 KB.</small></div>
            <div class="admin-field"><label for="primary_color">Warna utama</label><input id="primary_color" name="primary_color" value="{{ old('primary_color', $settings['primary_color']) }}" pattern="#[0-9A-Fa-f]{6}" required></div>
            <div class="admin-field"><label for="primary_soft_color">Warna lembut</label><input id="primary_soft_color" name="primary_soft_color" value="{{ old('primary_soft_color', $settings['primary_soft_color']) }}" pattern="#[0-9A-Fa-f]{6}" required></div>
            <div class="admin-field"><label for="accent_color">Warna aksen</label><input id="accent_color" name="accent_color" value="{{ old('accent_color', $settings['accent_color']) }}" pattern="#[0-9A-Fa-f]{6}" required></div>
        </div>
        <div class="admin-panel__header"><div><h2>Kontak aplikasi</h2><p>Informasi ini dapat digunakan oleh halaman admin dan halaman publik berikutnya.</p></div></div>
        <div class="admin-panel__body admin-settings-grid">
            <div class="admin-field"><label for="contact_email">Email</label><input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $settings['contact_email']) }}"></div>
            <div class="admin-field"><label for="contact_phone">Telepon</label><input id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone']) }}"></div>
            <div class="admin-field"><label for="contact_address">Alamat</label><input id="contact_address" name="contact_address" value="{{ old('contact_address', $settings['contact_address']) }}"></div>
            <div class="admin-field"><label for="contact_city">Kota</label><input id="contact_city" name="contact_city" value="{{ old('contact_city', $settings['contact_city']) }}"></div>
            <div class="admin-field"><label for="contact_country">Negara</label><input id="contact_country" name="contact_country" value="{{ old('contact_country', $settings['contact_country']) }}"></div>
        </div>
        <div class="admin-panel__header"><div><h2>Konten publik</h2><p>Konten ini ditampilkan pada halaman Tentang Kami dan Kontak Kami.</p></div></div>
        <div class="admin-panel__body admin-settings-grid">
            <div class="admin-field"><label for="about_title">Judul Tentang Kami</label><input id="about_title" name="about_title" value="{{ old('about_title', $settings['about_title']) }}" required></div>
            <div class="admin-field"><label for="contact_description">Deskripsi Kontak Kami</label><textarea id="contact_description" name="contact_description" rows="4" required>{{ old('contact_description', $settings['contact_description']) }}</textarea></div>
            <div class="admin-field" style="grid-column:1 / -1;"><label for="about_content">Isi Tentang Kami</label><textarea id="about_content" name="about_content" rows="8" required>{{ old('about_content', $settings['about_content']) }}</textarea></div>
        </div>
        <div class="admin-panel__body" style="display:flex; justify-content:flex-end; border-top:1px solid var(--admin-line);"><button class="admin-button admin-button--primary" type="submit">Simpan pengaturan</button></div>
    </form>
@endsection
