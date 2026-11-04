@extends('public.layouts.app', ['title' => 'Status Pengajuan Mitra'])

@section('content')
<div class="public-partner-page public-partner-page--narrow">
    <section class="public-partner-empty">
        @if (session('registration_reference'))
            <span class="material-symbols-outlined">check_circle</span>
            <h1>Pengajuan berhasil dikirim</h1>
            <p>{{ session('status_message') }}</p>
            <strong class="public-partner-reference">{{ session('registration_reference') }}</strong>
            <small>Simpan nomor pengajuan ini. Fitur pencarian status mandiri akan tersedia pada tahap berikutnya.</small>
        @else
            <span class="material-symbols-outlined">pending_actions</span>
            <h1>Status pengajuan mitra</h1>
            <p>Gunakan nomor pengajuan untuk memantau proses verifikasi. Fitur pencarian status mandiri akan tersedia pada tahap berikutnya.</p>
        @endif
        <a class="public-button" href="{{ route('public.partner') }}">Kembali ke informasi mitra</a>
    </section>
</div>
@endsection
