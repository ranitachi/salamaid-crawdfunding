@extends('public.layouts.app', ['title' => 'Pusat Bantuan'])

@section('content')
<section class="public-utility-page">
    <a class="public-link" href="{{ route('public.account') }}">← Kembali ke akun</a>
    <header class="public-utility-heading"><span class="public-profile-heading__icon material-symbols-outlined">help</span><div><span class="public-eyebrow">Bantuan Salamaid</span><h1>Pusat bantuan</h1><p>Temukan jawaban tentang donasi, pembayaran, dan akun Anda.</p></div></header>
    <section class="public-utility-card public-card"><div class="public-utility-card__title"><span class="material-symbols-outlined">volunteer_activism</span><h2>Donasi dan pembayaran</h2></div><details open><summary>Bagaimana cara berdonasi?</summary><p>Pilih campaign, tekan tombol Donasi Sekarang, isi nominal dan data donatur, lalu lanjutkan pembayaran melalui Mayar.</p></details><details><summary>Bagaimana jika pembayaran belum selesai?</summary><p>Buka menu Donasi Saya, pilih receipt yang berstatus Pending, lalu gunakan tombol Lanjutkan pembayaran atau Buat ulang link pembayaran.</p></details><details><summary>Di mana melihat riwayat donasi?</summary><p>Setelah login, buka menu Akun lalu pilih Donasi Saya. Setiap donasi memiliki receipt dan status pembayaran.</p></details></section>
    <section class="public-utility-card public-card"><div class="public-utility-card__title"><span class="material-symbols-outlined">manage_accounts</span><h2>Akun dan profil</h2></div><details><summary>Bagaimana mengubah data pribadi?</summary><p>Buka Akun, gulir ke bagian Data Pribadi, ubah nama, email, atau nomor WhatsApp, lalu tekan Simpan perubahan.</p></details><details><summary>Bagaimana mengatur ulang password?</summary><p>Gunakan menu Lupa password pada halaman login. Tautan reset akan dikirim jika email terdaftar.</p></details></section>
    <section class="public-utility-contact public-card"><span class="material-symbols-outlined">support_agent</span><div><h2>Masih membutuhkan bantuan?</h2><p>Hubungi tim Salamaid melalui kanal bantuan yang tersedia.</p></div><a href="mailto:{{ config('mail.from.address', 'support@salamaid.test') }}">Hubungi kami</a></section>
</section>
@endsection
