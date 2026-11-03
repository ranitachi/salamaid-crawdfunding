@extends('public.layouts.app', ['title' => 'Akun'])

@section('content')
<section class="public-user-page">
    <header class="public-inbox-heading"><a href="{{ route('public.home') }}" aria-label="Kembali"><span class="material-symbols-outlined">arrow_back</span></a><h1>Akun</h1></header>
    <section class="public-user-login public-card">
        <span class="material-symbols-outlined">account_circle</span>
        <h2>Yuk Masuk untuk nikmati kemudahan berdonasi dan akses fitur lainnya</h2>
        <a href="{{ route('public.login') }}" class="public-user-login__button">Masuk Sekarang</a>
        <p>Belum punya akun? <a href="{{ route('public.register') }}">Yuk daftar sekarang</a></p>
    </section>
    <section class="public-user-links">
        <h2>Seputar {{ app(\App\Services\AppSettingsService::class)->all()['app_name'] ?? 'Salamaid' }}</h2>
        <a href="{{ route('public.about') }}"><span class="material-symbols-outlined">info</span><strong>Tentang Kami</strong><span class="material-symbols-outlined">chevron_right</span></a>
        <a href="{{ route('public.contact') }}"><span class="material-symbols-outlined">phone</span><strong>Kontak Kami</strong><span class="material-symbols-outlined">chevron_right</span></a>
        <a href="{{ route('public.terms') }}"><span class="material-symbols-outlined">description</span><strong>Syarat &amp; Ketentuan</strong><span class="material-symbols-outlined">chevron_right</span></a>
        <a href="{{ route('public.privacy') }}"><span class="material-symbols-outlined">shield</span><strong>Kebijakan Privasi</strong><span class="material-symbols-outlined">chevron_right</span></a>
        <a href="{{ route('public.help') }}"><span class="material-symbols-outlined">help</span><strong>Tanya Jawab</strong><span class="material-symbols-outlined">chevron_right</span></a>
    </section>
</section>
@endsection
