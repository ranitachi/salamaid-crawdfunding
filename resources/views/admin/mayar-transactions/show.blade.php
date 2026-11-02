@extends('admin.layouts.app', ['title' => 'Detail Transaksi Mayar'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Transaksi Mayar', 'url' => route('admin.mayar-transactions.index')], ['label' => 'Detail']]" />
    <x-admin.page-header title="Detail transaksi Mayar" description="Informasi read-only dari provider. Status lokal Salamaid tidak diubah dari halaman ini.">
        <span class="admin-status admin-status--approved">Mayar · {{ strtoupper(config('payment.environment', 'sandbox')) }}</span>
        <a class="admin-button admin-button--quiet" href="{{ route('admin.mayar-transactions.index') }}">← Kembali</a>
    </x-admin.page-header>

    @if ($error)<div class="admin-alert" role="alert"><span class="admin-alert__icon">!</span><div><strong>Mayar tidak tersedia</strong><p>{{ $error }}</p></div></div>@endif

    @php($data = is_array($result['data'] ?? null) ? $result['data'] : $result)
    @if (!$error && $data)
        <div class="admin-grid">
            <div class="admin-panel">
                <div class="admin-panel__header"><div><h2>Ringkasan provider</h2><p>Transaction ID: {{ $data['id'] ?? $providerTransaction }}</p>@if ($localPayment)<p class="admin-table__secondary">Payment lokal: {{ $localPayment->id }}</p>@endif</div></div>
                <div class="admin-panel__body">
                    <dl class="admin-detail-list">
                        <div><dt>Status</dt><dd><x-admin.status-badge :status="strtolower((string) ($data['status'] ?? 'unknown'))" /></dd></div>
                        <div><dt>Nominal</dt><dd>Rp {{ number_format((int) ($data['amount'] ?? 0), 0, ',', '.') }}</dd></div>
                        <div><dt>Metode pembayaran</dt><dd>{{ $data['paymentMethod'] ?? '—' }}</dd></div>
                        <div><dt>Invoice code</dt><dd>{{ $data['invoiceCode'] ?? '—' }}</dd></div>
                        <div><dt>Dibuat</dt><dd>{{ !empty($data['createdAt']) ? \Carbon\Carbon::createFromTimestampMs((int) $data['createdAt'])->timezone(config('app.timezone'))->format('d M Y H:i') : '—' }}</dd></div>
                        <div><dt>Diperbarui</dt><dd>{{ !empty($data['updatedAt']) ? \Carbon\Carbon::createFromTimestampMs((int) $data['updatedAt'])->timezone(config('app.timezone'))->format('d M Y H:i') : '—' }}</dd></div>
                    </dl>
                </div>
            </div>
            <div class="admin-alert"><span class="admin-alert__icon">i</span><div><strong>Read-only provider data</strong><p>Untuk mengubah status lokal, gunakan proses webhook atau inquiry pada detail payment Salamaid. Halaman ini tidak melakukan mutasi transaksi Mayar.</p></div></div>
        </div>
    @endif
@endsection
