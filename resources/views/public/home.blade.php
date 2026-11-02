@extends('public.layouts.app', ['title' => 'Beranda'])

@section('content')
<div class="public-home">
    <section class="public-home__search" aria-label="Pencarian campaign">
        <form class="public-search" method="GET" action="{{ route('public.program') }}"><span class="material-symbols-outlined" aria-hidden="true">search</span><input name="q" type="search" placeholder="Cari kampanye atau program kebaikan..."><button type="submit" aria-label="Cari"><span class="material-symbols-outlined">arrow_forward</span></button></form>
        <button class="public-filter-button" type="button" aria-label="Filter dan notifikasi"><span class="material-symbols-outlined">tune</span><i></i></button>
    </section>

    <section class="public-home__banner" aria-label="Campaign prioritas">
        <div class="public-home__banner-image"></div>
        <div class="public-home__banner-overlay"><span class="public-home__banner-tag"><span class="material-symbols-outlined">emergency</span> Penyaluran donasi</span><span class="public-home__banner-time"><span class="material-symbols-outlined">schedule</span> Segera</span><h1>Jembatan kebaikan untuk mereka yang membutuhkan.</h1><p>Salurkan kepedulian menjadi dampak nyata bersama campaign terpercaya.</p><a href="{{ route('public.program') }}">Lihat campaign <span class="material-symbols-outlined">arrow_forward</span></a></div>
        <div class="public-home__banner-dots"><b></b><b></b><b></b></div>
    </section>

    <section class="public-wallet" aria-label="Saku Kebaikan"><div class="public-wallet__heading"><span class="public-wallet__icon material-symbols-outlined">account_balance_wallet</span><div><small>Saku Kebaikan</small><strong>Rp 0</strong></div><span class="material-symbols-outlined public-wallet__verified">verified</span><button type="button" disabled><span class="material-symbols-outlined">add_circle</span> Isi saldo</button></div><div class="public-wallet__status"><span></span><small>Donasi otomatis: <b>Segera hadir</b></small><span class="public-muted">Atur</span></div></section>

    <section class="public-shortcuts" aria-label="Layanan kebaikan">
        @foreach ([['volunteer_activism','Sedekah rutin','primary'],['calculate','Zakat maal','success'],['mosque','Wakaf','amber'],['emergency','Siaga bencana','primary'],['savings','Tabung qurban','neutral'],['medical_services','Bantuan medis','success'],['school','Peduli santri','primary'],['campaign','Galang dana','neutral']] as [$icon, $label, $tone])
            <a href="{{ route('public.program', ['q' => $label]) }}" class="public-shortcut"><span class="public-shortcut__icon public-shortcut__icon--{{ $tone }} material-symbols-outlined">{{ $icon }}</span><small>{{ $label }}</small></a>
        @endforeach
    </section>

    <section class="public-home-section"><div class="public-home-section__heading"><h2><i></i> Kampanye Prioritas</h2><a href="{{ route('public.program') }}">Lihat semua <span class="material-symbols-outlined">chevron_right</span></a></div><div class="public-priority-row">@forelse ($featured as $campaign)<a class="public-priority-card" href="{{ route('public.campaigns.show', $campaign->slug) }}"><div class="public-priority-card__image">@if($campaign->cover_url)<img src="{{ $campaign->cover_url }}" alt="">@else<span class="material-symbols-outlined">volunteer_activism</span>@endif<span class="public-priority-card__badge"><span class="material-symbols-outlined">campaign</span> PRIORITAS</span></div><div class="public-priority-card__body"><small>{{ $campaign->organization?->name ?? 'Organisasi terverifikasi' }} <b><span class="material-symbols-outlined">verified</span> Terverifikasi</b></small><h3>{{ $campaign->title }}</h3><div class="public-progress"><span style="width:{{ min(100, $campaign->target_amount > 0 ? (($campaign->paid_amount ?? 0) / $campaign->target_amount) * 100 : 0) }}%"></span></div><div class="public-priority-card__metrics"><span>Terkumpul<strong>Rp {{ number_format($campaign->paid_amount ?? 0, 0, ',', '.') }}</strong></span><span>Target<strong>Rp {{ number_format($campaign->target_amount, 0, ',', '.') }}</strong></span></div><span class="public-fast-donate">Donasi cepat <span class="material-symbols-outlined">arrow_forward</span></span></div></a>@empty<p class="public-muted">Belum ada campaign prioritas.</p>@endforelse</div></section>

    <section class="public-home-section"><div class="public-home-section__heading"><div><h2>Program pilihan</h2><small>Kebaikan yang sedang ramai didukung</small></div><a href="{{ route('public.program') }}">Eksplor <span class="material-symbols-outlined">chevron_right</span></a></div><div class="public-chip-row public-home__categories"><a class="public-chip is-active" href="{{ route('public.program') }}">Semua</a>@foreach ($categories as $category)<a class="public-chip" href="{{ route('public.program', ['category' => $category->slug]) }}">{{ $category->name }}</a>@endforeach</div></section>

    <section class="public-trust public-trust--home"><div class="public-trust__item"><span class="material-symbols-outlined">verified_user</span><div><strong>100% Amanah & Terdaftar Resmi</strong><small>Laporan audit penyaluran dana dikirim langsung ke inbox Anda.</small></div></div></section>
</div>
@endsection
