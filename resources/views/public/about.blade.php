@extends('public.layouts.app', ['title' => $brand['about_title'] ?? 'Tentang Kami'])

@section('content')
<article class="public-utility-page">
    <a class="public-link" href="{{ route('public.user') }}">← Kembali ke akun</a>
    <header class="public-utility-heading"><div><span class="public-eyebrow">Tentang kami</span><h1>{{ $brand['about_title'] ?? 'Tentang Salamaid' }}</h1><p>{{ $brand['public_tagline'] ?? 'Jembatan Kebaikan' }}</p></div></header>
    <section class="public-utility-card public-card"><p style="white-space:pre-line;">{{ $brand['about_content'] ?? '' }}</p></section>
</article>
@endsection
