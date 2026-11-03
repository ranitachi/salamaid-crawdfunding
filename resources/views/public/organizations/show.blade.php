@extends('public.layouts.app', ['title' => $organization->name, 'description' => str($organization->description ?: 'Profil lembaga dan program kebaikan '.$organization->name.'.')->squish()->limit(160)])

@section('content')
<section class="public-organization-page">
    <header class="public-inbox-heading"><a href="{{ route('public.program') }}" aria-label="Kembali ke program"><span class="material-symbols-outlined">arrow_back</span></a><h1>Profil lembaga</h1></header>
    <section class="public-organization-hero public-card" @if($organization->banner_url) style="background-image:linear-gradient(90deg, #ffffff 0%, #ffffffeb 48%, #ffffff90 100%), url('{{ $organization->banner_url }}');" @endif>
        @if($organization->logo_url)<img class="public-organization-hero__logo" src="{{ $organization->logo_url }}" alt="Logo {{ $organization->name }}">@else<span class="public-organization-hero__mark material-symbols-outlined">verified</span>@endif
        <div><span class="public-eyebrow">Lembaga terverifikasi</span><h2>{{ $organization->name }}</h2><p>{{ $organization->tagline ?: ($organization->description ?: 'Lembaga yang menghadirkan program kebaikan dan dampak nyata.') }}</p></div>
    </section>
    <nav class="public-organization-tabs" aria-label="Bagian profil lembaga"><a class="is-active" href="#program" data-organization-tab="program">Program kebaikan</a><a href="#tentang" data-organization-tab="tentang">Tentang lembaga</a></nav>
    <section class="public-organization-stats"><div class="public-card"><strong>{{ $campaigns->count() }}</strong><small>Program aktif</small></div><div class="public-card"><strong>Rp {{ number_format($impact['amount'], 0, ',', '.') }}</strong><small>Dana tersalurkan</small></div><div class="public-card"><strong>{{ number_format($impact['count'], 0, ',', '.') }}</strong><small>Donasi</small></div></section>
    <section class="public-organization-contact public-card" id="tentang"><h2>Tentang lembaga</h2>@if($organization->description)<p class="public-organization-contact__description">{{ $organization->description }}</p>@endif @if($organization->address)<p><span class="material-symbols-outlined">location_on</span>{{ $organization->address }}</p>@endif @if($organization->contact_email)<p><span class="material-symbols-outlined">mail</span>{{ $organization->contact_email }}</p>@endif @if($organization->contact_phone)<p><span class="material-symbols-outlined">phone</span>{{ $organization->contact_phone }}</p>@endif @if($organization->website_url)<p><span class="material-symbols-outlined">language</span><a href="{{ $organization->website_url }}" rel="noopener noreferrer">Website</a></p>@endif @if($organization->instagram_url)<p><span class="material-symbols-outlined">photo_camera</span><a href="{{ $organization->instagram_url }}" rel="noopener noreferrer">Instagram</a></p>@endif @if($organization->facebook_url)<p><span class="material-symbols-outlined">public</span><a href="{{ $organization->facebook_url }}" rel="noopener noreferrer">Facebook</a></p>@endif @if($organization->whatsapp_url)<p><span class="material-symbols-outlined">chat</span><a href="{{ $organization->whatsapp_url }}" rel="noopener noreferrer">WhatsApp</a></p>@endif</section>
    <section class="public-organization-campaigns" id="program"><div class="public-home-section__heading"><h2><i></i> Program kebaikan</h2><span>{{ $campaigns->count() }} program</span></div><div class="public-program-feed">@forelse($campaigns as $campaign)<article class="public-program-card"><a href="{{ route('public.campaigns.show', $campaign->slug) }}" class="public-program-card__image">@if($campaign->cover_url)<img src="{{ $campaign->cover_url }}" alt="">@endif<span>{{ $campaign->category?->name ?? 'Program' }}</span></a><div class="public-program-card__body"><small>{{ $organization->name }} <b><span class="material-symbols-outlined">verified</span></b></small><h3><a href="{{ route('public.campaigns.show', $campaign->slug) }}">{{ $campaign->title }}</a></h3><div class="public-progress"><span style="width:{{ min(100, $campaign->target_amount > 0 ? (($campaign->paid_amount ?? 0) / $campaign->target_amount) * 100 : 0) }}%"></span></div><div class="public-program-card__stats"><span>Terkumpul<strong>Rp {{ number_format($campaign->paid_amount ?? 0, 0, ',', '.') }}</strong></span><span>Target<strong>Rp {{ number_format($campaign->target_amount, 0, ',', '.') }}</strong></span></div><div class="public-program-card__footer"><small>{{ number_format($campaign->paid_donation_count, 0, ',', '.') }} donatur</small><a class="public-button" href="{{ route('public.campaigns.donate', $campaign->slug) }}">Donasi</a></div></div></article>@empty<div class="public-trust"><div class="public-trust__item"><strong>Belum ada program aktif</strong><span>Lembaga ini belum memiliki campaign yang dipublikasikan.</span></div></div>@endforelse</div></section>
</section>
@endsection

@push('scripts')
<script>
    (() => {
        const tabs = [...document.querySelectorAll('[data-organization-tab]')];
        const panels = { program: document.getElementById('program'), tentang: document.getElementById('tentang') };
        const activate = (name, updateHash = true) => {
            tabs.forEach((tab) => tab.classList.toggle('is-active', tab.dataset.organizationTab === name));
            Object.entries(panels).forEach(([key, panel]) => panel?.classList.toggle('is-hidden', key !== name));
            if (updateHash) history.replaceState(null, '', `#${name}`);
        };
        tabs.forEach((tab) => tab.addEventListener('click', (event) => { event.preventDefault(); activate(tab.dataset.organizationTab); }));
        activate(window.location.hash === '#tentang' ? 'tentang' : 'program', false);
    })();
</script>
@endpush
