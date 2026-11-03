<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Receipt Donasi Salamaid</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 42px; color: #17221f; font-family: Arial, sans-serif; font-size: 13px; }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #bc0004; padding-bottom: 18px; }
        .brand { color: #bc0004; font-size: 26px; font-weight: 700; }
        .subtitle, .muted { color: #68736f; }
        h1 { margin: 28px 0 8px; font-size: 24px; }
        .intro { margin: 0 0 24px; color: #68736f; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #d8ded8; padding: 13px 0; text-align: left; vertical-align: top; }
        th { width: 38%; color: #68736f; font-weight: 400; }
        td { font-weight: 700; }
        .amount { color: #bc0004; font-size: 21px; }
        .message { margin-top: 24px; padding: 14px; background: #fff0ef; }
        .footer { margin-top: 42px; padding-top: 14px; border-top: 1px solid #d8ded8; color: #68736f; font-size: 11px; }
    </style>
</head>
<body>
    <header class="header"><div class="brand">Salamaid</div><div class="subtitle">Bukti penerimaan donasi</div></header>
    <h1>Receipt Donasi</h1>
    <p class="intro">Terima kasih telah ikut menjembatani kebaikan.</p>
    <table>
        <tr><th>Campaign</th><td>{{ $donation->campaign?->title ?? '—' }}</td></tr>
        <tr><th>Donatur</th><td>{{ $donation->anonymous ? 'Donatur anonim' : ($donation->user?->name ?? 'Donatur') }}</td></tr>
        <tr><th>Tanggal pembayaran</th><td>{{ $donation->paid_at?->format('d M Y H:i') ?? $donation->created_at?->format('d M Y H:i') ?? '—' }}</td></tr>
        <tr><th>Status</th><td>{{ ucfirst($donation->status) }}</td></tr>
        <tr><th>Metode</th><td>{{ strtoupper($donation->method ?: '—') }}</td></tr>
        <tr><th>Referensi</th><td>{{ $donation->payment?->order_id ?? $donation->id }}</td></tr>
        <tr><th>Total donasi</th><td class="amount">Rp {{ number_format($donation->gross_amount, 0, ',', '.') }}</td></tr>
    </table>
    @if ($donation->message)<div class="message"><strong>Pesan donatur</strong><br>{{ $donation->message }}</div>@endif
    <footer class="footer">Dokumen ini dibuat otomatis oleh sistem Salamaid dan bukan faktur pajak.</footer>
</body>
</html>
