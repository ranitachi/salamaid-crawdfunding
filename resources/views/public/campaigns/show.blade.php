@extends('public.layouts.app', ['title' => $campaign->title])

@push('head')
    <meta property="og:type" content="article">
    <meta property="article:section" content="Campaign">
    <style>
        .public-campaign-route .public-main{padding:16px 16px 112px}
    </style>
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => $campaign->title, 'description' => str($campaign->description)->squish()->limit(160)->toString(), 'url' => route('public.campaigns.show', $campaign->slug)], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
@php
    $paid = (int) ($campaign->paid_amount ?? 0);
    $target = (int) $campaign->target_amount;
    $progress = $target > 0 ? min(100, ($paid / $target) * 100) : 0;
    $daysLeft = $campaign->deadline_at ? max(0, now()->startOfDay()->diffInDays($campaign->deadline_at->startOfDay(), false)) : null;
    $reports = $campaign->updates->filter(fn ($update) => $update->isFundReport())->values();
    $storyMedia = $campaign->media->firstWhere('type', 'image') ?? $campaign->media->first();
    $storyUpdate = $campaign->updates->first(fn ($update) => ! $update->isFundReport() && filled($update->media_url));
    $storyImage = $storyMedia?->url ?? $storyUpdate?->media_url;
    $storyCaption = $storyMedia?->caption ?? $storyUpdate?->title;
    $organization = $campaign->organization;
@endphp

<article class="public-campaign-page" data-campaign-detail>
    <header class="public-campaign-toolbar"><a href="{{ route('public.program') }}" class="public-campaign-toolbar__back" aria-label="Kembali"><span class="material-symbols-outlined">arrow_back</span></a><h1>Detail Kampanye</h1><span class="public-campaign-toolbar__placeholder" aria-hidden="true"></span></header>
    <section class="public-campaign-visual">
        @if ($campaign->cover_url)<img src="{{ $campaign->cover_url }}" alt="{{ $campaign->title }}">@else<div class="public-campaign-visual__empty"><span class="material-symbols-outlined">volunteer_activism</span></div>@endif
        <div class="public-campaign-visual__shade"></div>
        <div class="public-campaign-visual__topline"><span class="public-campaign-category"><span class="material-symbols-outlined">{{ $campaign->category?->icon ?: 'volunteer_activism' }}</span>{{ $campaign->category?->name ?? 'Campaign sosial' }}</span><span class="public-campaign-actions"><button type="button" data-bookmark aria-label="Simpan kampanye"><span class="material-symbols-outlined">bookmark_border</span></button><button type="button" data-share aria-label="Bagikan kampanye"><span class="material-symbols-outlined">share</span></button></span></div>
        @if ($organization)<span class="public-campaign-verified"><span class="material-symbols-outlined">verified</span>Terverifikasi Amanah Salamaid</span>@endif
    </section>

    <div class="public-campaign-content">
        <h2 class="public-campaign-title">{{ $campaign->title }}</h2>
        <section class="public-campaign-organization public-card"><a class="public-campaign-organization__identity" href="{{ $organization ? route('public.organizations.show', $organization->slug) : route('public.program') }}">@if ($organization?->logo_url)<img src="{{ $organization->logo_url }}" alt="{{ $organization->name }}">@else<span class="public-campaign-organization__mark"><span class="material-symbols-outlined">health_and_safety</span></span>@endif<div><strong>{{ $organization?->name ?? 'Organisasi terverifikasi' }} <span class="material-symbols-outlined">verified</span></strong><small>{{ $organization?->description ? str($organization->description)->squish()->limit(58) : 'Mitra kebaikan Salamaid' }}</small></div></a><span class="public-campaign-audit">Audit resmi</span></section>
        <section class="public-campaign-progress public-card"><div class="public-campaign-progress__heading"><div><small>Dana terkumpul</small><strong>Rp {{ number_format($paid, 0, ',', '.') }}</strong></div><div class="public-campaign-progress__target"><b>{{ number_format($progress, 0) }}%</b><small>dari Rp {{ number_format($target, 0, ',', '.') }}</small></div></div><div class="public-progress public-campaign-progress__bar"><span style="width:{{ $progress }}%"></span></div><div class="public-campaign-stats"><div><span class="material-symbols-outlined">group</span><span><b>{{ number_format($campaign->paid_donation_count, 0, ',', '.') }}</b><small>Donatur tergabung</small></span></div><div><span class="material-symbols-outlined">timer</span><span><b>{{ $daysLeft === null ? 'Aktif' : $daysLeft.' hari' }}</b><small>Tersisa waktu</small></span></div></div><div class="public-campaign-guarantee"><span class="material-symbols-outlined">verified</span><p><b>Amanah 100%:</b> Dana dan laporan kampanye ditampilkan setelah melalui proses review Salamaid.</p></div></section>
        <nav class="public-campaign-tabs" aria-label="Bagian kampanye"><a class="is-active" href="#cerita">Cerita</a><a href="#updates">Kabar ({{ $campaign->updates->count() }})</a><a href="#allocation">Alokasi Dana</a></nav>
        <section class="public-campaign-story public-card" id="cerita"><div class="public-campaign-section-heading"><h3>{{ $campaign->meta_json['story_title'] ?? 'Cerita kampanye' }}</h3><span><span class="material-symbols-outlined">schedule</span>{{ ($campaign->published_at ?? $campaign->created_at)?->format('d M Y') }}</span></div><div class="public-campaign-richtext">{!! nl2br(e($campaign->description)) !!}</div>@if ($storyImage)<figure><img src="{{ $storyImage }}" alt="{{ $storyCaption ?: $campaign->title }}">@if ($storyCaption)<figcaption>{{ $storyCaption }}</figcaption>@endif</figure>@endif</section>
        @if ($reports->isNotEmpty())<section class="public-campaign-allocation public-card" id="allocation"><div class="public-campaign-card-heading"><span class="material-symbols-outlined">account_balance_wallet</span><h3>Rincian alokasi dana</h3></div><div class="public-campaign-allocation__list">@foreach ($reports as $report)<div><span>{{ $report->title }}</span><strong>Rp {{ number_format((int) $report->amount_used, 0, ',', '.') }}</strong></div>@endforeach</div></section>@else<section class="public-campaign-allocation public-card" id="allocation"><div class="public-campaign-card-heading"><span class="material-symbols-outlined">account_balance_wallet</span><h3>Alokasi dana</h3></div><p class="public-muted">Laporan alokasi dana akan ditampilkan setelah dipublikasikan.</p></section>@endif
        <section class="public-campaign-legal public-card"><div class="public-campaign-card-heading"><span class="material-symbols-outlined">gavel</span><h3>Legalitas &amp; pengawasan</h3><em>Resmi</em></div><div class="public-campaign-legal__item"><span class="material-symbols-outlined">policy</span><span><b>Organisasi terverifikasi</b><small>@if($organization?->slug)<a class="public-organization-link" href="{{ route('public.organizations.show', $organization->slug) }}">{{ $organization->name }}</a>@else{{ $organization?->name }}@endif telah diverifikasi oleh Salamaid.</small></span></div><div class="public-campaign-legal__item"><span class="material-symbols-outlined">fact_check</span><span><b>Transparansi berkala</b><small>Update dan laporan dana yang sudah dipublikasikan tersedia untuk donatur.</small></span></div></section>
        <section class="public-campaign-updates public-card" id="updates"><div class="public-campaign-card-heading"><span class="material-symbols-outlined">campaign</span><h3>Kabar terbaru</h3></div>@forelse ($campaign->updates as $update)<article><div><b>{{ $update->title }}</b><small>{{ $update->published_at?->format('d M Y') }}</small></div><p>{!! nl2br(e($update->content)) !!}</p></article>@empty<p class="public-muted">Belum ada kabar terbaru yang dipublikasikan.</p>@endforelse</section>
        <section class="public-campaign-prayers public-card"><div class="public-campaign-card-heading"><span class="material-symbols-outlined">favorite</span><h3>Doa donatur</h3></div><p class="public-muted">Doa dan komentar donatur akan tampil di sini setelah fitur komunitas tersedia.</p></section>
    </div>
    <div class="public-campaign-sticky"><button type="button" data-share aria-label="Bagikan kampanye"><span class="material-symbols-outlined">share</span></button><a href="{{ route('public.campaigns.donate', $campaign->slug) }}"><span class="material-symbols-outlined">favorite</span> Donasi Sekarang</a></div>
</article>
@endsection

@push('scripts')
<script>
    (() => { const share = async () => { const data = { title: @json($campaign->title), text: 'Mari dukung kampanye ini melalui Salamaid.', url: window.location.href }; if (navigator.share) { try { await navigator.share(data); } catch (_) {} } else if (navigator.clipboard) { await navigator.clipboard.writeText(window.location.href); } }; document.querySelectorAll('[data-share]').forEach((button) => button.addEventListener('click', share)); document.querySelectorAll('[data-bookmark]').forEach((button) => button.addEventListener('click', () => { const icon = button.querySelector('.material-symbols-outlined'); icon.textContent = icon.textContent === 'bookmark' ? 'bookmark_border' : 'bookmark'; button.classList.toggle('is-active'); })); })();
</script>
@endpush
