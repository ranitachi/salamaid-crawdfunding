@extends('public.layouts.app', ['title' => 'Daftar sebagai Lembaga'])

@section('content')
<div class="public-partner-page public-partner-page--narrow">
    @php($isRepair = $organization?->isRejected())
    <section class="public-partner-form-card">
        <span class="public-eyebrow"><span class="material-symbols-outlined">{{ $isRepair ? 'edit_note' : 'domain_add' }}</span> Mitra Salamaid</span>
        <h1>{{ $isRepair ? 'Perbaiki pengajuan lembaga' : 'Daftar sebagai lembaga' }}</h1>
        <p class="public-muted">{{ $isRepair ? 'Perbarui data sesuai catatan reviewer. Pengajuan akan kembali ditinjau setelah dikirim.' : 'Isi data awal lembaga dengan lengkap. Pengajuan akan ditinjau Admin Salamaid sebelum akun dapat digunakan.' }}</p>
        <form method="POST" action="{{ route('public.partner.register.store') }}" enctype="multipart/form-data">
            @csrf
            @if ($isRepair)<input type="hidden" name="application_reference" value="{{ $organization->application_reference }}">@endif
            <h2>Informasi lembaga</h2>
            <label class="public-field"><span>Nama lembaga <i>*</i></span><input name="name" value="{{ old('name', $organization?->name) }}" placeholder="Contoh: Yayasan Peduli Sesama" required></label>
            @error('name')<small class="public-error">{{ $message }}</small>@enderror
            <label class="public-field"><span>Jenis lembaga <i>*</i></span><span class="public-select-wrap"><select name="organization_type" required><option value="">Pilih jenis lembaga</option>@foreach(['yayasan' => 'Yayasan', 'lembaga_sosial' => 'Lembaga sosial', 'komunitas' => 'Komunitas', 'organisasi' => 'Organisasi'] as $value => $label)<option value="{{ $value }}" @selected(old('organization_type', $organization?->organization_type) === $value)>{{ $label }}</option>@endforeach</select><span class="material-symbols-outlined">expand_more</span></span></label>
            @error('organization_type')<small class="public-error">{{ $message }}</small>@enderror
            <label class="public-field"><span>Nama penanggung jawab <i>*</i></span><input name="responsible_name" value="{{ old('responsible_name', $organization?->responsible_name) }}" autocomplete="name" placeholder="Nama lengkap penanggung jawab" required></label>
            @error('responsible_name')<small class="public-error">{{ $message }}</small>@enderror
            <div class="public-partner-form-grid">
                <div><label class="public-field"><span>Provinsi <i>*</i></span><span class="public-select-wrap"><select id="partner-province" name="province" data-cities-url="{{ route('public.partner.locations.cities', ['province' => '__PROVINCE__']) }}" required><option value="">Pilih provinsi</option>@foreach($provinces as $province)<option value="{{ $province->name }}" data-province-id="{{ $province->id }}" @selected(old('province', $organization?->province) === $province->name)>{{ $province->name }}</option>@endforeach</select><span class="material-symbols-outlined">expand_more</span></span></label>@error('province')<small class="public-error">{{ $message }}</small>@enderror</div>
                <div><label class="public-field"><span>Kabupaten/kota <i>*</i></span><span class="public-select-wrap"><select id="partner-city" name="city" data-old-city="{{ old('city', $organization?->city) }}" required disabled><option value="">Pilih provinsi terlebih dahulu</option></select><span class="material-symbols-outlined">expand_more</span></span></label>@error('city')<small class="public-error">{{ $message }}</small>@enderror</div>
            </div>
            <label class="public-field"><span>Alamat lengkap <i>*</i></span><textarea name="address" rows="3" placeholder="Alamat kantor atau sekretariat lembaga" required>{{ old('address', $organization?->address) }}</textarea></label>
            @error('address')<small class="public-error">{{ $message }}</small>@enderror
            <label class="public-field"><span>Deskripsi lembaga <i>*</i></span><textarea name="description" rows="4" placeholder="Ceritakan fokus kegiatan dan penerima manfaat lembaga" required>{{ old('description', $organization?->description) }}</textarea></label>
            @error('description')<small class="public-error">{{ $message }}</small>@enderror

            <h2>Kontak dan akun admin</h2>
            <label class="public-field"><span>Email akun <i>*</i></span><input type="email" name="email" value="{{ old('email', $organization?->contact_email) }}" autocomplete="email" placeholder="nama@lembaga.org" required></label>
            @error('email')<small class="public-error">{{ $message }}</small>@enderror
            <label class="public-field"><span>Nomor WhatsApp <i>*</i></span><input type="tel" name="mobile" value="{{ old('mobile', $organization?->contact_phone) }}" autocomplete="tel" placeholder="Contoh: 081234567890" required></label>
            @error('mobile')<small class="public-error">{{ $message }}</small>@enderror
            <div class="public-partner-form-grid">
                <div><label class="public-field"><span>Password <i>*</i></span><input type="password" name="password" autocomplete="new-password" placeholder="Minimal 8 karakter" required></label>@error('password')<small class="public-error">{{ $message }}</small>@enderror</div>
                <div><label class="public-field"><span>Konfirmasi password <i>*</i></span><input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi password" required></label></div>
            </div>

            <h2>Informasi tambahan <em>(opsional)</em></h2>
            <label class="public-field"><span>Logo lembaga</span><input type="file" name="logo" accept="image/png,image/jpeg,image/webp"><small>PNG, JPG, atau WEBP. Maksimal 2 MB.</small></label>
            @error('logo')<small class="public-error">{{ $message }}</small>@enderror
            <label class="public-field"><span>Dokumen pendukung</span><input type="file" name="verification_document" accept="application/pdf,image/png,image/jpeg"><small>PDF, PNG, atau JPG. Maksimal 5 MB. Disimpan secara privat untuk proses verifikasi.</small></label>
            @error('verification_document')<small class="public-error">{{ $message }}</small>@enderror
            <label class="public-field"><span>Website</span><input type="url" name="website_url" value="{{ old('website_url', $organization?->website_url) }}" placeholder="https://contoh.org"></label>
            @error('website_url')<small class="public-error">{{ $message }}</small>@enderror
            <label class="public-field"><span>Instagram</span><input type="url" name="instagram_url" value="{{ old('instagram_url', $organization?->instagram_url) }}" placeholder="https://instagram.com/namalembaga"></label>
            @error('instagram_url')<small class="public-error">{{ $message }}</small>@enderror
            <label class="public-field"><span>Facebook</span><input type="url" name="facebook_url" value="{{ old('facebook_url', $organization?->facebook_url) }}" placeholder="https://facebook.com/namalembaga"></label>
            @error('facebook_url')<small class="public-error">{{ $message }}</small>@enderror
            <button class="public-button" type="submit">{{ $isRepair ? 'Kirim perbaikan' : 'Kirim pengajuan lembaga' }} <span class="material-symbols-outlined">arrow_forward</span></button>
        </form>
        <p class="public-auth__footer">Sudah pernah mengajukan? <a class="public-link" href="{{ route('public.partner.status') }}">Cek status pengajuan</a></p>
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const province = document.querySelector('#partner-province');
    const city = document.querySelector('#partner-city');
    if (!province || !city) return;
    const oldCity = city.dataset.oldCity || '';
    const loadCities = async () => {
        const selected = province.options[province.selectedIndex];
        const id = selected?.dataset.provinceId;
        city.innerHTML = '<option value="">Memuat kabupaten/kota...</option>';
        city.disabled = true;
        if (!id) {
            city.innerHTML = '<option value="">Pilih provinsi terlebih dahulu</option>';
            return;
        }
        try {
            const url = province.dataset.citiesUrl.replace('__PROVINCE__', id);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('location request failed');
            const cities = await response.json();
            city.innerHTML = '<option value="">Pilih kabupaten/kota</option>';
            cities.forEach((item) => {
                const option = new Option(`${item.type} ${item.name}`, item.name, false, item.name === oldCity);
                city.add(option);
            });
            city.disabled = cities.length === 0;
            if (cities.length === 0) city.innerHTML = '<option value="">Data kabupaten/kota belum tersedia</option>';
        } catch (_) {
            city.innerHTML = '<option value="">Gagal memuat data lokasi</option>';
        }
    };
    province.addEventListener('change', loadCities);
    if (province.value) loadCities();
})();
</script>
@endpush
