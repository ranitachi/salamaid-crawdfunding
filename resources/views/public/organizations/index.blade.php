@extends('public.layouts.app', ['title' => 'Lembaga'])

@section('content')
<div class="public-organization-index">
    <header class="public-section__heading">
        <div><h1>Lembaga terverifikasi</h1><p>Pilih lembaga untuk melihat profil dan seluruh program kebaikannya.</p></div>
    </header>

    <section class="public-organization-grid" aria-label="Daftar lembaga">
        @forelse ($organizations as $organization)
            <a class="public-organization-list-card" href="{{ route('public.organizations.show', $organization->slug) }}">
                <div class="public-organization-list-card__identity">
                    @if ($organization->logo_url)<img src="{{ $organization->logo_url }}" alt="Logo {{ $organization->name }}">@else<span class="material-symbols-outlined">domain</span>@endif
                    <div><h2>{{ $organization->name }}</h2><span><span class="material-symbols-outlined">verified</span> Terverifikasi</span></div>
                </div>
                <p>{{ $organization->description ? str($organization->description)->squish()->limit(110) : 'Lembaga yang menghadirkan program kebaikan dan dampak nyata.' }}</p>
                <footer><span>{{ $organization->published_campaign_count }} program publik</span><span class="material-symbols-outlined">arrow_forward</span></footer>
            </a>
        @empty
            <div class="public-trust"><div class="public-trust__item"><strong>Belum ada lembaga</strong><span>Lembaga terverifikasi akan tampil di halaman ini.</span></div></div>
        @endforelse
    </section>
</div>
@endsection
