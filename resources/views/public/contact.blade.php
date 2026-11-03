@extends('public.layouts.app', ['title' => 'Kontak Kami'])

@section('content')
<article class="public-utility-page">
    <a class="public-link" href="{{ route('public.user') }}">← Kembali ke akun</a>
    <header class="public-utility-heading"><div><span class="public-eyebrow">Kontak kami</span><h1>Hubungi {{ $brand['app_name'] ?? 'Salamaid' }}</h1><p>{{ $brand['contact_description'] ?? '' }}</p></div></header>
    <section class="public-utility-card public-card">
        @if (filled($brand['contact_email'] ?? null))<div class="public-utility-contact"><span class="material-symbols-outlined">mail</span><div><h2>Email</h2><a href="mailto:{{ $brand['contact_email'] }}">{{ $brand['contact_email'] }}</a></div></div>@endif
        @if (filled($brand['contact_phone'] ?? null))<div class="public-utility-contact"><span class="material-symbols-outlined">phone</span><div><h2>Telepon / WhatsApp</h2><a href="tel:{{ preg_replace('/[^0-9+]/', '', $brand['contact_phone']) }}">{{ $brand['contact_phone'] }}</a></div></div>@endif
        @if (filled($brand['contact_address'] ?? null) || filled($brand['contact_city'] ?? null))<div class="public-utility-contact"><span class="material-symbols-outlined">location_on</span><div><h2>Alamat</h2><p>{{ collect([$brand['contact_address'] ?? null, $brand['contact_city'] ?? null, $brand['contact_country'] ?? null])->filter()->implode(', ') }}</p></div></div>@endif
        @if (blank($brand['contact_email'] ?? null) && blank($brand['contact_phone'] ?? null) && blank($brand['contact_address'] ?? null))<p class="public-utility-note">Informasi kontak belum tersedia.</p>@endif
    </section>
</article>
@endsection
