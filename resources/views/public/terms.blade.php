@extends('public.layouts.app', ['title' => 'Syarat & Ketentuan'])

@section('content')
<article class="public-utility-page"><a class="public-link" href="{{ route('public.user') }}">← Kembali ke akun</a><header class="public-utility-heading"><div><span class="public-eyebrow">Informasi publik</span><h1>Syarat &amp; Ketentuan</h1><p>Ketentuan penggunaan layanan Salamaid.</p></div></header><section class="public-utility-card public-card"><h2>Penggunaan layanan</h2><p>Gunakan Salamaid untuk mendukung campaign yang tersedia dan berikan data yang benar saat melakukan donasi.</p><h2>Donasi dan pembayaran</h2><p>Donasi diproses melalui payment gateway yang tersedia. Status pembayaran mengikuti konfirmasi dari penyedia pembayaran.</p><h2>Konten campaign</h2><p>Campaign dan informasi lembaga dapat melalui proses review sebelum ditampilkan kepada publik.</p></section></article>
@endsection
