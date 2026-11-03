@extends('public.layouts.app', ['title' => 'Keamanan dan Privasi'])

@section('content')
<section class="public-utility-page">
    <a class="public-link" href="{{ route('public.account') }}">← Kembali ke akun</a>
    <header class="public-utility-heading"><span class="public-profile-heading__icon material-symbols-outlined">shield</span><div><span class="public-eyebrow">Salamaid amanah</span><h1>Keamanan dan privasi</h1><p>Informasi tentang cara Salamaid menjaga data dan aktivitas Anda.</p></div></header>
    <section class="public-utility-card public-card"><div class="public-utility-card__title"><span class="material-symbols-outlined">lock</span><h2>Data yang kami gunakan</h2></div><p>Salamaid menggunakan nama, email, nomor WhatsApp, dan informasi donasi untuk memproses pembayaran, menampilkan riwayat pada akun, serta membantu penyelesaian kendala transaksi.</p><p>Data tersebut tidak ditampilkan kepada pengguna lain tanpa kebutuhan layanan atau persetujuan yang sesuai.</p></section>
    <section class="public-utility-card public-card"><div class="public-utility-card__title"><span class="material-symbols-outlined">verified_user</span><h2>Keamanan pembayaran</h2></div><p>Pembayaran diproses melalui provider Mayar. Salamaid tidak menyimpan password, PIN, atau kredensial pembayaran Anda.</p><p>Status transaksi dapat diperbarui melalui webhook dan sinkronisasi provider untuk menjaga akurasi receipt.</p></section>
    <section class="public-utility-card public-card"><div class="public-utility-card__title"><span class="material-symbols-outlined">manage_accounts</span><h2>Kontrol atas akun Anda</h2></div><p>Anda dapat memperbarui data profil melalui halaman Akun. Untuk permintaan koreksi atau penghapusan data, hubungi tim Salamaid melalui kanal bantuan.</p><a class="public-button" href="{{ route('public.account.help') }}">Buka pusat bantuan</a></section>
    <p class="public-utility-note">Halaman ini menjelaskan praktik aplikasi pada lingkungan saat ini. Ketentuan legal lengkap dapat diperbarui sesuai kebijakan operasional Salamaid.</p>
</section>
@endsection
