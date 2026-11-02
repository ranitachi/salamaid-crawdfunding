@extends('admin.layouts.app', ['title' => 'Pengaturan'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Pengaturan']]" />

    <x-admin.page-header title="Pengaturan aplikasi" description="Kelola identitas, warna, logo, dan informasi kontak yang digunakan pada panel Salamaid.">
        <span class="admin-status admin-status--approved">Hanya Super Admin</span>
    </x-admin.page-header>

    @if (session('success'))<x-admin.toast :message="session('success')" />@endif
    @if ($errors->any())<x-admin.toast :message="$errors->first()" type="error" />@endif

    <form class="admin-panel" method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')
        <div class="admin-panel__header"><div><h2>Identitas &amp; tampilan</h2><p>Nilai disimpan di database dan digunakan pada login serta seluruh shell admin.</p></div></div>
        <div class="admin-panel__body admin-settings-grid">
            <div class="admin-field"><label for="app_name">Nama aplikasi</label><input id="app_name" name="app_name" value="{{ old('app_name', $settings['app_name']) }}" required></div>
            <div class="admin-field"><label for="app_tagline">Tagline</label><input id="app_tagline" name="app_tagline" value="{{ old('app_tagline', $settings['app_tagline']) }}"></div>
            <div class="admin-field"><label for="brand_icon">Icon teks</label><input id="brand_icon" name="brand_icon" value="{{ old('brand_icon', $settings['brand_icon']) }}" maxlength="10" required><small class="admin-field__hint">Dipakai jika logo URL kosong.</small></div>
            <div class="admin-field"><label for="logo_url">URL logo</label><input id="logo_url" name="logo_url" type="url" value="{{ old('logo_url', $settings['logo_url']) }}" placeholder="https://..."><small class="admin-field__hint">Referensi URL gambar, tidak menyimpan binary di database.</small></div>
            <div class="admin-field"><label for="favicon_url">URL favicon</label><input id="favicon_url" name="favicon_url" type="url" value="{{ old('favicon_url', $settings['favicon_url']) }}" placeholder="https://..."></div>
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
        <div class="admin-panel__body" style="display:flex; justify-content:flex-end; border-top:1px solid var(--admin-line);"><button class="admin-button admin-button--primary" type="submit">Simpan pengaturan</button></div>
    </form>
@endsection
