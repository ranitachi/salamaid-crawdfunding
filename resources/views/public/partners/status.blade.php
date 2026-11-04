@extends('public.layouts.app', ['title' => 'Status Pengajuan Mitra'])

@section('content')
<div class="public-partner-page public-partner-page--narrow">
    @if ($organization)
        @php($statusLabels = ['pending' => ['Menunggu verifikasi', 'pending_actions', 'Pengajuan Anda sedang ditinjau oleh Admin Salamaid.'], 'verified' => ['Terverifikasi', 'verified', 'Lembaga sudah diverifikasi dan akun admin dapat digunakan.'], 'rejected' => ['Perlu diperbaiki', 'edit_note', 'Pengajuan perlu diperbaiki sebelum dapat diverifikasi.'], 'suspended' => ['Ditangguhkan', 'pause_circle', 'Akses lembaga sedang ditangguhkan. Hubungi Salamaid untuk informasi lebih lanjut.']])
        @php($statusView = $statusLabels[$organization->status] ?? $statusLabels['pending'])
        <section class="public-partner-status-card">
            <span class="material-symbols-outlined public-partner-status-icon public-partner-status-icon--{{ $organization->status }}">{{ $statusView[1] }}</span>
            <span class="public-eyebrow">Status pengajuan</span>
            <h1>{{ $statusView[0] }}</h1>
            @if (session('status_message'))<p>{{ session('status_message') }}</p>@else<p>{{ $statusView[2] }}</p>@endif
            <div class="public-partner-status-details"><span>Nomor pengajuan</span><strong>{{ $organization->application_reference }}</strong><span>Nama lembaga</span><strong>{{ $organization->name }}</strong></div>
            @if ($organization->status === 'rejected' && $organization->rejection_reason)<div class="public-partner-rejection"><b>Catatan reviewer</b><p>{{ $organization->rejection_reason }}</p></div><a class="public-button" href="{{ route('public.partner.register', ['reference' => $organization->application_reference]) }}">Perbaiki pengajuan <span class="material-symbols-outlined">edit</span></a>@endif
            @if ($organization->status === 'verified')<a class="public-button" href="{{ route('admin.login') }}">Login admin lembaga <span class="material-symbols-outlined">login</span></a>@endif
            <form method="POST" action="{{ route('public.partner.status.clear') }}">@csrf<button class="public-partner-secondary-button" type="submit">Cek pengajuan lain</button></form>
        </section>
    @else
        <section class="public-partner-empty">
            <span class="material-symbols-outlined">pending_actions</span>
            <h1>Status pengajuan mitra</h1>
            <p>Masukkan nomor pengajuan dan email yang digunakan saat mendaftar untuk melihat status secara aman.</p>
            <form class="public-partner-status-form" method="POST" action="{{ route('public.partner.status.lookup') }}">
                @csrf
                <label class="public-field"><span>Nomor pengajuan <i>*</i></span><input name="application_reference" value="{{ old('application_reference') }}" placeholder="Contoh: SM-AB12CD34EF" autocomplete="off" required></label>
                @error('application_reference')<small class="public-error">{{ $message }}</small>@enderror
                <label class="public-field"><span>Email saat mendaftar <i>*</i></span><input type="email" name="email" value="{{ old('email') }}" placeholder="nama@lembaga.org" autocomplete="email" required></label>
                @error('email')<small class="public-error">{{ $message }}</small>@enderror
                <button class="public-button" type="submit">Lihat status <span class="material-symbols-outlined">arrow_forward</span></button>
            </form>
        </section>
    @endif
        <a class="public-button" href="{{ route('public.partner') }}">Kembali ke informasi mitra</a>
</div>
@endsection
