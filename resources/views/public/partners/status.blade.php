@extends('public.layouts.app', ['title' => 'Status Pengajuan Mitra'])

@section('content')
<div class="public-partner-page public-partner-page--narrow">
    <section class="public-partner-empty">
        <span class="material-symbols-outlined">pending_actions</span>
        <h1>Status pengajuan mitra</h1>
        <p>Halaman status pengajuan akan tersedia setelah formulir pendaftaran lembaga diaktifkan. Simpan nomor pengajuan Anda untuk memantau proses verifikasi.</p>
        <a class="public-button" href="{{ route('public.partner') }}">Kembali ke informasi mitra</a>
    </section>
</div>
@endsection
