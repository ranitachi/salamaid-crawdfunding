@extends('public.layouts.app', ['title' => 'Daftar sebagai Lembaga'])

@section('content')
<div class="public-partner-page public-partner-page--narrow">
    <section class="public-partner-empty">
        <span class="material-symbols-outlined">domain_add</span>
        <h1>Pendaftaran lembaga</h1>
        <p>Formulir pendaftaran mitra sedang kami siapkan. Pelajari dulu manfaat dan persyaratan kemitraan Salamaid sebelum mengajukan data.</p>
        <a class="public-button" href="{{ route('public.partner') }}">Lihat informasi kemitraan</a>
    </section>
</div>
@endsection
