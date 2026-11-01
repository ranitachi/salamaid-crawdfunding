@extends('admin.layouts.app', ['title' => 'Detail Payout'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Payout', 'url' => '/admin/payouts'], ['label' => 'Detail']]" />
    <x-admin.page-header title="Detail payout" :description="'ID: '.$payout->id">
        <a class="admin-button admin-button--quiet" href="/admin/payouts">← Kembali</a>
    </x-admin.page-header>

    @if (session('success'))<x-admin.toast :message="session('success')" />@endif
    @if (session('error'))<x-admin.toast :message="session('error')" type="error" />@endif

    <div class="admin-grid">
        <section class="admin-panel">
            <div class="admin-panel__header"><div><h2>Ringkasan payout</h2><p>Status dan validasi pencairan dana.</p></div><x-admin.status-badge :status="$payout->status" /></div>
            <div class="admin-panel__body">
                <dl class="admin-detail-list">
                    <div><dt>Payout ID</dt><dd>{{ $payout->id }}</dd></div>
                    <div><dt>Organisasi</dt><dd>{{ $payout->organization?->name ?? '—' }}</dd></div>
                    <div><dt>Nominal</dt><dd>Rp {{ number_format($payout->amount, 0, ',', '.') }}</dd></div>
                    <div><dt>Saldo tersedia</dt><dd>Rp {{ number_format($availableAmount, 0, ',', '.') }}</dd></div>
                    <div><dt>Pengaju</dt><dd>{{ $payout->requester?->name ?? '—' }}</dd></div>
                    <div><dt>Approver</dt><dd>{{ $payout->approver?->name ?? '—' }}</dd></div>
                    <div><dt>Diajukan</dt><dd>{{ $payout->created_at?->format('d M Y H:i') }}</dd></div>
                    <div><dt>Dibayar</dt><dd>{{ $payout->paid_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                    <div><dt>Catatan</dt><dd>{{ $payout->note ?: '—' }}</dd></div>
                    <div><dt>Bukti transfer</dt><dd>@if ($payout->evidence_url)<a href="{{ $payout->evidence_url }}" target="_blank" rel="noopener">Buka bukti</a>@else—@endif</dd></div>
                </dl>
            </div>
        </section>

        <aside class="admin-panel">
            <div class="admin-panel__header"><div><h2>Aksi operasional</h2><p>Setiap aksi dicatat pada audit log.</p></div></div>
            <div class="admin-panel__body">
                @can('approve', $payout)
                    <form method="POST" action="{{ route('admin.payouts.approve', $payout) }}" style="margin-bottom: 10px;">@csrf<button class="admin-button admin-button--primary" type="submit">✓ Setujui payout</button></form>
                @endcan
                @can('reject', $payout)
                    <form method="POST" action="{{ route('admin.payouts.reject', $payout) }}" style="display:grid; gap:8px; margin-bottom:10px;">@csrf<textarea class="admin-field" name="reason" required minlength="5" placeholder="Alasan penolakan"></textarea><button class="admin-button admin-button--danger" type="submit">Tolak payout</button></form>
                @endcan
                @can('markPaid', $payout)
                    <form method="POST" action="{{ route('admin.payouts.paid', $payout) }}" style="display:grid; gap:8px;">@csrf<input class="admin-field" type="url" name="evidence_url" required placeholder="https://.../bukti-transfer"><button class="admin-button admin-button--primary" type="submit">Tandai sudah dibayar</button></form>
                @endcan
                @cannot('approve', $payout)
                    @cannot('reject', $payout)
                        @cannot('markPaid', $payout)<x-admin.empty-state title="Tidak ada aksi" description="Role Anda hanya dapat melihat detail payout." />@endcannot
                    @endcannot
                @endcannot
            </div>
        </aside>
    </div>
@endsection
