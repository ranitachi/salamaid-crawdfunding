@extends('admin.layouts.app', ['title' => 'Transaksi Mayar'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Pembayaran', 'url' => route('admin.payments.index')], ['label' => 'Transaksi Mayar']]" />
    <x-admin.page-header title="Transaksi Mayar" description="Read-only view transaksi yang tercatat pada akun Mayar. Data ini berasal langsung dari API provider.">
        <span class="admin-status admin-status--approved">Mayar · {{ strtoupper(config('payment.environment', 'sandbox')) }}</span>
    </x-admin.page-header>

    @if ($error)<div class="admin-alert" role="alert"><span class="admin-alert__icon">!</span><div><strong>Mayar tidak tersedia</strong><p>{{ $error }}</p></div></div><div style="height:20px"></div>@endif

    <x-admin.filter-bar method="GET" action="{{ route('admin.mayar-transactions.index') }}">
        <div class="admin-field"><label for="mayar-status">Status</label><input id="mayar-status" name="status" value="{{ $filters['status'] ?? '' }}" placeholder="paid, unpaid..."></div>
        <div class="admin-field"><label for="mayar-start">Dari</label><input id="mayar-start" type="date" name="start_at" value="{{ $filters['start_at'] ?? '' }}"></div>
        <div class="admin-field"><label for="mayar-end">Sampai</label><input id="mayar-end" type="date" name="end_at" value="{{ $filters['end_at'] ?? '' }}"></div>
        <div class="admin-field"><label for="mayar-limit">Per halaman</label><select id="mayar-limit" name="limit"><option value="20">20</option><option value="50" @selected(($filters['limit'] ?? null) == 50)>50</option></select></div>
        <button class="admin-button admin-button--primary" type="submit">Muat transaksi</button>
        <a class="admin-button admin-button--quiet" href="{{ route('admin.mayar-transactions.index') }}">Reset</a>
    </x-admin.filter-bar>

    <div style="height:20px"></div>
    <x-admin.data-table title="Transaksi dari Mayar" description="{{ count($transactions) }} transaksi pada halaman ini. Pagination menggunakan cursor provider.">
        <thead><tr><th>Transaction ID</th><th>Status</th><th>Nominal</th><th>Metode</th><th>Dibuat</th><th>Diperbarui</th><th></th></tr></thead>
        <tbody>
            @forelse ($transactions as $transaction)
                <tr>
                    <td><span class="admin-table__primary">{{ $transaction['id'] ?? '—' }}</span></td>
                    <td><x-admin.status-badge :status="strtolower((string) ($transaction['status'] ?? 'unknown'))" /></td>
                    <td>Rp {{ number_format((int) ($transaction['amount'] ?? 0), 0, ',', '.') }}</td>
                    <td>{{ $transaction['paymentMethod'] ?? '—' }}</td>
                    <td>{{ !empty($transaction['createdAt']) ? \Carbon\Carbon::createFromTimestampMs((int) $transaction['createdAt'])->timezone(config('app.timezone'))->format('d M Y H:i') : '—' }}</td>
                    <td>{{ !empty($transaction['updatedAt']) ? \Carbon\Carbon::createFromTimestampMs((int) $transaction['updatedAt'])->timezone(config('app.timezone'))->format('d M Y H:i') : '—' }}</td>
                    <td><a class="admin-button admin-button--quiet" href="{{ route('admin.mayar-transactions.show', $transaction['id']) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-admin.empty-state title="Tidak ada transaksi Mayar" description="Coba ubah filter periode atau status." /></td></tr>
            @endforelse
        </tbody>
    </x-admin.data-table>

    @if ($nextStartingAfter)
        <div style="display:flex; justify-content:flex-end; margin-top:18px;"><a class="admin-button admin-button--primary" href="{{ route('admin.mayar-transactions.index', array_merge($filters, ['starting_after' => $nextStartingAfter])) }}">Berikutnya →</a></div>
    @endif
@endsection
