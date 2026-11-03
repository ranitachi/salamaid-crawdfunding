@extends('public.layouts.app', ['title' => 'Pusat Bantuan'])

@section('content')
<section class="public-utility-page">
    <a class="public-link" href="{{ route('public.account') }}">← Kembali ke akun</a>
    <header class="public-utility-heading"><span class="public-profile-heading__icon material-symbols-outlined">help</span><div><span class="public-eyebrow">Bantuan Salamaid</span><h1>Pusat bantuan</h1><p>Temukan jawaban tentang donasi, pembayaran, dan akun Anda.</p></div></header>
    @forelse ($articles as $category => $categoryArticles)
        <section class="public-utility-card public-card"><div class="public-utility-card__title"><span class="material-symbols-outlined">{{ $loop->first ? 'volunteer_activism' : 'help' }}</span><h2>{{ $category }}</h2></div>@foreach ($categoryArticles as $article)<details @if ($loop->parent->first && $loop->first) open @endif><summary>{{ $article->question }}</summary><p>{!! nl2br(e($article->answer)) !!}</p></details>@endforeach</section>
    @empty
        <section class="public-utility-card public-card"><div class="public-utility-card__title"><span class="material-symbols-outlined">info</span><h2>Belum ada artikel bantuan</h2></div><p>Tim Salamaid sedang menyiapkan informasi bantuan untuk Anda.</p></section>
    @endforelse
    <section class="public-utility-contact public-card"><span class="material-symbols-outlined">support_agent</span><div><h2>Masih membutuhkan bantuan?</h2><p>Hubungi tim Salamaid melalui kanal bantuan yang tersedia.</p></div><a href="mailto:{{ config('mail.from.address', 'support@salamaid.test') }}">Hubungi kami</a></section>
</section>
@endsection
