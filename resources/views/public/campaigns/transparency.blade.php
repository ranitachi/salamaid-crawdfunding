@extends('public.layouts.app', ['title' => 'Transparansi · '.$campaign->title, 'ogType' => 'article'])

@section('content')
<article class="public-campaign-detail">
    <a class="public-link" href="{{ route('public.campaigns.show', $campaign->slug) }}">← Kembali ke campaign</a>
    <header class="public-detail-section">
        <span class="public-eyebrow">Transparansi campaign</span>
        <h1>{{ $campaign->title }}</h1>
        <p class="public-muted">{{ $campaign->organization?->name }} · Update dan laporan yang sudah dipublikasikan</p>
    </header>
    <section class="public-detail-grid">
        <div>
            <section class="public-detail-section" id="updates">
                <h2>Kabar terbaru</h2>
                @forelse ($updates as $update)
                    <article class="public-update">
                        <div>
                            <span class="public-eyebrow">{{ $update->isFundReport() ? 'Laporan dana' : 'Update campaign' }}</span>
                            <h3>{{ $update->title }}</h3>
                            <small class="public-muted">{{ $update->published_at?->translatedFormat('d F Y') }}</small>
                        </div>
                        <p>{!! nl2br(e($update->content)) !!}</p>
                        @if ($update->media_url)<a class="public-link" href="{{ $update->media_url }}" rel="noopener noreferrer">Lihat media pendukung</a>@endif
                    </article>
                @empty
                    <p class="public-muted">Belum ada kabar yang dipublikasikan untuk campaign ini.</p>
                @endforelse
            </section>
        </div>
        <aside class="public-detail-aside">
            <div class="public-trust__item"><strong>Total alokasi yang dilaporkan</strong><span>Rp {{ number_format($reportedAmount, 0, ',', '.') }}</span></div>
            <div class="public-trust__item"><strong>{{ $reports->count() }} laporan dana</strong><span>Hanya laporan yang telah melewati review yang ditampilkan.</span></div>
            <a class="public-button" href="{{ route('public.campaigns.donate', $campaign->slug) }}">Donasi sekarang →</a>
        </aside>
    </section>
</article>
@endsection
