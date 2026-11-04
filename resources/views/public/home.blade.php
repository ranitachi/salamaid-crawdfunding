@extends('public.layouts.app', ['title' => 'Beranda'])

@section('content')
<div class="public-home">
    <section class="public-home__search" aria-label="Pencarian campaign"><form class="public-search" method="GET" action="{{ route('public.program') }}"><span class="material-symbols-outlined" aria-hidden="true">search</span><input name="q" type="search" placeholder="Cari kampanye atau program kebaikan..."><button type="submit" aria-label="Cari"><span class="material-symbols-outlined">arrow_forward</span></button></form><button class="public-filter-button" type="button" aria-label="Filter dan notifikasi"><span class="material-symbols-outlined">tune</span><i></i></button></section>

    <section class="public-home__banner" aria-label="Campaign prioritas">
        @if ($heroCampaign?->cover_url)<div class="public-home__banner-image" style="background-image:url('{{ $heroCampaign->cover_url }}')"></div>@else<div class="public-home__banner-image"></div>@endif
        <div class="public-home__banner-overlay"><span class="public-home__banner-tag"><span class="material-symbols-outlined">campaign</span> Campaign pilihan</span><span class="public-home__banner-time"><span class="material-symbols-outlined">verified</span> Terverifikasi</span><h1>{{ $heroCampaign?->title ?? 'Jembatan kebaikan untuk mereka yang membutuhkan.' }}</h1><p>@if($heroCampaign?->organization?->slug)<a class="public-organization-link" href="{{ route('public.organizations.show', $heroCampaign->organization->slug) }}">{{ $heroCampaign->organization->name }}</a>@else{{ $heroCampaign?->organization?->name ?? 'Salamaid menghubungkan kepedulian dengan dampak nyata.' }}@endif</p><a href="{{ $heroCampaign ? route('public.campaigns.show', $heroCampaign->slug) : route('public.program') }}">Lihat campaign <span class="material-symbols-outlined">arrow_forward</span></a></div><div class="public-home__banner-dots"><b></b><b></b><b></b></div>
    </section>

    <section class="public-home__action" aria-label="Mulai berdonasi">
        <div class="public-home__action-main">
            <span class="public-home__action-icon material-symbols-outlined">volunteer_activism</span>
            <div>
                <small>Mulai berbagi kebaikan</small>
                <strong>Temukan campaign yang berarti</strong>
            </div>
            <a class="public-home__action-button" href="{{ route('public.program') }}">Jelajahi <span class="material-symbols-outlined">arrow_forward</span></a>
        </div>
        <div class="public-home__action-status"><span></span><small>Campaign terverifikasi dan transparan</small><a href="{{ route('public.organizations.index') }}">Lihat lembaga</a><a class="public-home__partner-link" href="{{ route('public.partner') }}"><span class="material-symbols-outlined">handshake</span>Daftar mitra</a></div>
    </section>

    <section class="public-shortcuts" aria-label="Kategori program">
        @php($categoryIconMap = ['heart' => 'volunteer_activism', 'leaf' => 'eco', 'book-open' => 'menu_book'])
        @forelse ($categories as $category)
            <a href="{{ route('public.program', ['category' => $category->slug]) }}" class="public-shortcut"><span class="public-shortcut__icon material-symbols-outlined">{{ $categoryIconMap[$category->icon] ?? ($category->icon ?: 'category') }}</span><small>{{ $category->name }}</small></a>
        @empty
            <a href="{{ route('public.program') }}" class="public-shortcut"><span class="public-shortcut__icon material-symbols-outlined">category</span><small>Program</small></a>
        @endforelse
    </section>

    <section class="public-home-section"><div class="public-home-section__heading"><h2><i></i> Kampanye Prioritas</h2><a href="{{ route('public.program') }}">Lihat semua <span class="material-symbols-outlined">chevron_right</span></a></div><div class="public-priority-row">@forelse ($featured as $campaign)<article class="public-priority-card"><a class="public-priority-card__image" href="{{ route('public.campaigns.show', $campaign->slug) }}">@if($campaign->cover_url)<img src="{{ $campaign->cover_url }}" alt="" loading="lazy" decoding="async">@else<span class="material-symbols-outlined">volunteer_activism</span>@endif<span class="public-priority-card__badge"><span class="material-symbols-outlined">campaign</span> PRIORITAS</span></a><div class="public-priority-card__body"><small>@if($campaign->organization?->slug)<a class="public-organization-link" href="{{ route('public.organizations.show', $campaign->organization->slug) }}">{{ $campaign->organization->name }}</a>@else{{ $campaign->organization?->name ?? 'Organisasi terverifikasi' }}@endif <b><span class="material-symbols-outlined">verified</span> Terverifikasi</b></small><h3><a class="public-campaign-link" href="{{ route('public.campaigns.show', $campaign->slug) }}">{{ $campaign->title }}</a></h3><div class="public-progress"><span style="width:{{ min(100, $campaign->target_amount > 0 ? (($campaign->paid_amount ?? 0) / $campaign->target_amount) * 100 : 0) }}%"></span></div><div class="public-priority-card__metrics"><span>Terkumpul<strong>Rp {{ number_format($campaign->paid_amount ?? 0, 0, ',', '.') }}</strong></span><span>Target<strong>Rp {{ number_format($campaign->target_amount, 0, ',', '.') }}</strong></span></div><a class="public-fast-donate" href="{{ route('public.campaigns.donate', $campaign->slug) }}">Donasi cepat <span class="material-symbols-outlined">arrow_forward</span></a></div></article>@empty<p class="public-muted">Belum ada campaign prioritas.</p>@endforelse</div></section>

    <section class="public-home-section"><div class="public-home-section__heading"><div><h2>Jelajahi kategori</h2><small>Pilih program sesuai kebutuhan</small></div><a href="{{ route('public.program') }}">Eksplor <span class="material-symbols-outlined">chevron_right</span></a></div><div class="public-chip-row public-home__categories"><a class="public-chip is-active" href="{{ route('public.program') }}">Semua</a>@foreach ($categories as $category)<a class="public-chip" href="{{ route('public.program', ['category' => $category->slug]) }}">{{ $category->name }}</a>@endforeach</div></section>

    <section class="public-home-section"><div class="public-home-section__heading"><div><h2>Program pilihan</h2><small>Campaign terbaru dari lembaga terverifikasi</small></div><a href="{{ route('public.program') }}">Lihat semua <span class="material-symbols-outlined">chevron_right</span></a></div><div class="public-program-feed">@forelse($programs as $program)<article class="public-program-card"><a href="{{ route('public.campaigns.show', $program->slug) }}" class="public-program-card__image">@if($program->cover_url)<img src="{{ $program->cover_url }}" alt="{{ $program->title }}" loading="lazy" decoding="async">@else<span class="material-symbols-outlined">volunteer_activism</span>@endif<span>{{ $program->category?->name ?? 'Program' }}</span></a><div class="public-program-card__body"><small>@if($program->organization?->slug)<a class="public-organization-link" href="{{ route('public.organizations.show', $program->organization->slug) }}">{{ $program->organization->name }}</a>@else{{ $program->organization?->name ?? 'Lembaga terverifikasi' }}@endif <b><span class="material-symbols-outlined">verified</span></b></small><h3><a href="{{ route('public.campaigns.show', $program->slug) }}">{{ $program->title }}</a></h3><div class="public-progress"><span style="width:{{ min(100, $program->target_amount > 0 ? (($program->paid_amount ?? 0) / $program->target_amount) * 100 : 0) }}%"></span></div><div class="public-program-card__stats"><span>Terkumpul<strong>Rp {{ number_format($program->paid_amount ?? 0, 0, ',', '.') }}</strong></span><span>Target<strong>Rp {{ number_format($program->target_amount, 0, ',', '.') }}</strong></span></div><div class="public-program-card__footer"><small>{{ number_format($program->paid_donation_count ?? 0, 0, ',', '.') }} donatur terlibat</small><a class="public-button" href="{{ route('public.campaigns.donate', $program->slug) }}">Donasi</a></div></div></article>@empty<p class="public-muted">Belum ada program pilihan.</p>@endforelse</div></section>

    <section class="public-trust public-trust--home"><div class="public-trust__item"><span class="material-symbols-outlined">verified_user</span><div><strong>{{ $brand['app_name'] ?? 'Salamaid' }} · Organisasi terverifikasi</strong><small>Campaign, donasi, dan update transparansi diambil dari data yang sudah direview.</small></div></div></section>
</div>
@endsection

@push('scripts')
<script type="application/json" id="public-load-more-state">@json(['featured' => $featured->nextPageUrl(), 'programs' => $programs->nextPageUrl()])</script>
@endpush
