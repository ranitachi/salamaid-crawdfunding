@extends('public.layouts.app', ['title' => 'Detail notifikasi'])

@section('content')
@php
    $data = is_array($notification->data) ? $notification->data : [];
    $kindMap = [
        'payment_success' => ['icon' => 'check_circle', 'class' => 'success', 'label' => 'Donasi berhasil'],
        'payment_expired' => ['icon' => 'notifications_off', 'class' => 'danger', 'label' => 'Pembayaran kedaluwarsa'],
        'distribution' => ['icon' => 'campaign', 'class' => 'warning', 'label' => 'Penyaluran terbaru'],
        'impact' => ['icon' => 'menu_book', 'class' => 'info', 'label' => 'Dampak terlaksana'],
    ];
    $kind = $kindMap[$data['kind'] ?? ''] ?? ['icon' => 'notifications', 'class' => 'info', 'label' => 'Pembaruan Salamaid'];
@endphp
<section class="public-inbox-detail">
    <header class="public-inbox-heading"><a href="{{ route('public.inbox') }}" aria-label="Kembali ke inbox"><span class="material-symbols-outlined">arrow_back</span></a><h1>Detail notifikasi</h1></header>
    <article class="public-inbox-detail__card public-card">
        <div class="public-inbox-detail__icon public-inbox-card__icon--{{ $kind['class'] }} material-symbols-outlined">{{ $kind['icon'] }}</div>
        <span class="public-inbox-badge public-inbox-badge--{{ $kind['class'] }}">{{ $kind['label'] }}</span>
        <h2>{{ $data['title'] ?? 'Pembaruan Salamaid' }}</h2>
        <p>{{ $data['message'] ?? $data['description'] ?? 'Ada pembaruan untuk Anda.' }}</p>
        <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->translatedFormat('d F Y, H:i') }}</time>
        @if(($data['kind'] ?? '') === 'payment_success')
            <a class="public-button" href="{{ !empty($data['donation_id']) ? route('public.receipt', $data['donation_id']) : route('public.donations') }}"><span class="material-symbols-outlined">receipt_long</span> Lihat detail donasi</a>
        @elseif(($data['kind'] ?? '') === 'payment_expired')
            <a class="public-button" href="{{ !empty($data['donation_id']) ? route('public.receipt', $data['donation_id']) : route('public.program') }}"><span class="material-symbols-outlined">refresh</span> Ulangi donasi</a>
        @elseif(in_array($data['kind'] ?? '', ['distribution', 'impact'], true))
            <a class="public-button" href="{{ !empty($data['campaign_slug']) ? route('public.campaigns.show', $data['campaign_slug']) : route('public.program') }}"><span class="material-symbols-outlined">campaign</span> Lihat campaign</a>
        @endif
    </article>
</section>
@endsection
