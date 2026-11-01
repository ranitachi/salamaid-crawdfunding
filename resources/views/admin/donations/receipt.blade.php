<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kwitansi Donasi {{ $donation->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #172b4d; margin: 40px auto; max-width: 760px; }
        .header { border-bottom: 2px solid #0d6efd; padding-bottom: 16px; margin-bottom: 24px; }
        .muted { color: #6c757d; } table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 11px 8px; border-bottom: 1px solid #e9ecef; }
        .amount { font-size: 22px; font-weight: 700; color: #0d6efd; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="header"><h1>Salamaid</h1><div class="muted">Bukti penerimaan donasi</div></div>
    <p class="muted">Nomor donasi: {{ $donation->id }}</p>
    <table>
        <tr><th>Campaign</th><td>{{ $donation->campaign?->title ?? '—' }}</td></tr>
        <tr><th>Donatur</th><td>{{ $donation->anonymous ? 'Donatur anonim' : ($donation->user?->name ?? 'Donatur') }}</td></tr>
        <tr><th>Tanggal pembayaran</th><td>{{ optional($donation->paid_at)->format('d M Y H:i') ?? '—' }}</td></tr>
        <tr><th>Metode</th><td>{{ strtoupper($donation->method) }}</td></tr>
        <tr><th>Total donasi</th><td class="amount">Rp {{ number_format($donation->gross_amount, 0, ',', '.') }}</td></tr>
    </table>
    @if ($donation->message)<p><strong>Pesan:</strong> {{ $donation->message }}</p>@endif
    <p class="muted">Dokumen ini dibuat oleh sistem Salamaid dan bukan faktur pajak.</p>
    <button class="no-print" onclick="window.print()">Cetak / Simpan PDF</button>
</body>
</html>
