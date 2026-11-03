@extends('public.layouts.app', ['title' => 'Receipt Donasi'])

@section('content')
@php($paymentRequest = optional($donation->payment)->raw_request_json)
@php($storedMobile = is_array($paymentRequest) ? ($paymentRequest['mobile'] ?? null) : null)
<section class="public-receipt">
    <a class="public-link" href="{{ route('public.donations') }}">← Kembali ke donasi saya</a>
    <div class="public-receipt__card">
        <span class="public-payment-status__icon public-payment-status__icon--{{ $donation->status === 'paid' ? 'paid' : 'pending' }}">{{ $donation->status === 'paid' ? '✓' : '!' }}</span>
        <span class="public-eyebrow">Receipt Salamaid</span>
        <h1>{{ $donation->status === 'paid' ? 'Terima kasih atas donasi Anda' : 'Donasi belum selesai' }}</h1>
        <div class="public-payment-status__details">
            <div><span>Campaign</span><strong>{{ $donation->campaign?->title }}</strong></div>
            <div><span>Nominal</span><strong>Rp {{ number_format($donation->gross_amount, 0, ',', '.') }}</strong></div>
            <div><span>Status</span><strong>{{ ucfirst($donation->status) }}</strong></div>
            <div><span>Referensi</span><strong>{{ $donation->payment?->order_id ?? $donation->id }}</strong></div>
        </div>
        @if (session('status_message'))<div class="public-form-success">{{ session('status_message') }}</div>@endif
        @if (session('status_error'))<div class="public-form-error">{{ session('status_error') }}</div>@endif
        @if (in_array($donation->status, ['pending', 'expired'], true) && (!$donation->payment || $donation->payment->provider === 'mayar') && $donation->payment?->status !== 'paid')
            @if ($donation->payment?->redirect_url)<a class="public-button" href="{{ $donation->payment->redirect_url }}">Lanjutkan pembayaran</a>@endif
            <form method="POST" action="{{ route('public.receipt.regenerate-payment', $donation) }}">
                @csrf
                <label class="public-field"><span>Nomor WhatsApp untuk pembayaran</span><input type="text" name="mobile" value="{{ old('mobile', $storedMobile) }}" placeholder="+62812..." required></label>
                <button class="public-button public-button--outline" type="submit">Buat ulang link pembayaran</button>
            </form>
        @endif
        <button class="public-button public-button--outline" type="button" onclick="window.print()">Cetak receipt</button>
    </div>
</section>
@endsection
