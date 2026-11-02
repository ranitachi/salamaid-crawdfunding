@extends('public.layouts.app', ['title' => 'Program'])

@section('content')
<section class="public-section">
    <div class="public-section__heading"><div><h1>Program kebaikan</h1><p>{{ $campaigns->total() }} campaign terverifikasi tersedia.</p></div></div>
    <form class="public-search" style="max-width:none;margin:0 0 16px;" method="GET"><span aria-hidden="true">⌕</span><input name="q" value="{{ request('q') }}" type="search" placeholder="Cari campaign atau organisasi..."></form>
    <div class="public-chip-row"><a class="public-chip {{ request()->filled('category') ? '' : 'is-active' }}" href="{{ route('public.program', request()->except('category')) }}">Semua</a>@foreach ($categories as $category)<a class="public-chip {{ request('category') === $category->slug ? 'is-active' : '' }}" href="{{ route('public.program', array_merge(request()->except('category'), ['category' => $category->slug])) }}">{{ $category->name }}</a>@endforeach</div>
    <div class="public-campaign-grid" style="margin-top:18px;">
        @forelse ($campaigns as $campaign)
            <a class="public-campaign-card" data-campaign-card href="{{ route('public.campaigns.show', $campaign->slug) }}">
                <div class="public-campaign-card__image">@if($campaign->cover_url)<img src="{{ $campaign->cover_url }}" alt="">@else<span aria-hidden="true">✦</span>@endif</div>
                <div class="public-campaign-card__body"><h3>{{ $campaign->title }}</h3><p class="public-campaign-card__org">{{ $campaign->organization?->name }}</p><div class="public-progress"><span style="width: {{ min(100, $campaign->target_amount > 0 ? (($campaign->paid_amount ?? 0) / $campaign->target_amount) * 100 : 0) }}%"></span></div><div class="public-campaign-card__metrics"><span class="public-muted">Terkumpul<strong>Rp {{ number_format($campaign->paid_amount ?? 0, 0, ',', '.') }}</strong></span><span class="public-muted">{{ $campaign->paid_donation_count }} donasi</span></div></div>
            </a>
        @empty
            <div class="public-trust"><div class="public-trust__item"><strong>Campaign tidak ditemukan</strong><span>Coba kata kunci atau kategori lain.</span></div></div>
        @endforelse
    </div>
    {{ $campaigns->links('public.pagination') }}
</section>
@endsection
