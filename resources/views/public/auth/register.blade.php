@extends('public.layouts.app', ['title' => 'Daftar akun'])

@section('content')
    <section class="public-auth">
        <div class="public-auth__card">
            <span class="public-eyebrow">Akun Salamaid</span>
            <h1>Daftar akun Salamaid</h1>
            <p class="public-muted">Bergabung untuk berdonasi lebih mudah, menyimpan riwayat donasi, dan mengikuti kabar campaign pilihan Anda.</p>
            <form method="POST" action="{{ route('public.register.store') }}">
                @csrf
                <label class="public-field"><span>Nama lengkap <i>*</i></span><input name="name" value="{{ old('name') }}" autocomplete="name" placeholder="Masukkan nama lengkap" required></label>
                @error('name')<small class="public-error">{{ $message }}</small>@enderror
                <label class="public-field"><span>Alamat email <i>*</i></span><input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="Masukkan alamat email" required></label>
                @error('email')<small class="public-error">{{ $message }}</small>@enderror
                <label class="public-field"><span>Nomor WhatsApp <em>(opsional)</em></span><input type="tel" name="mobile" value="{{ old('mobile') }}" autocomplete="tel" placeholder="Contoh: 081234567890"></label>
                @error('mobile')<small class="public-error">{{ $message }}</small>@enderror
                <label class="public-field"><span>Password <i>*</i></span><span class="public-password-field"><input type="password" name="password" autocomplete="new-password" placeholder="Buat password minimal 8 karakter" required><button type="button" data-toggle-password aria-label="Tampilkan password"><span class="material-symbols-outlined">visibility</span></button></span></label>
                @error('password')<small class="public-error">{{ $message }}</small>@enderror
                <label class="public-field"><span>Konfirmasi password <i>*</i></span><span class="public-password-field"><input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Masukkan kembali password" required><button type="button" data-toggle-password aria-label="Tampilkan konfirmasi password"><span class="material-symbols-outlined">visibility</span></button></span></label>
                <button class="public-button" type="submit">Daftar</button>
            </form>
            <p class="public-auth__footer">Sudah punya akun? <a class="public-link" href="{{ route('public.login') }}">Masuk sekarang</a></p>
        </div>
    </section>
@endsection
