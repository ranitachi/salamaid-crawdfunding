@extends('admin.layouts.app', ['title' => 'Audit Log'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Audit Log']]" />
    <x-admin.page-header title="Audit Log" description="Jejak perubahan dan aksi sensitif pada panel admin." />
    <x-admin.filter-bar method="GET" :action="route('admin.audit-logs.index')">
        <div class="admin-field"><label for="audit-q">Cari</label><input id="audit-q" name="q" value="{{ request('q') }}" placeholder="Action, subject, actor..."></div>
        <div class="admin-field"><label for="audit-action">Action</label><input id="audit-action" name="action" value="{{ request('action') }}" placeholder="campaign.publish"></div>
        <div class="admin-field"><label for="audit-from">Dari</label><input id="audit-from" type="date" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="admin-field"><label for="audit-to">Sampai</label><input id="audit-to" type="date" name="date_to" value="{{ request('date_to') }}"></div>
        <button class="admin-button admin-button--primary" type="submit">Filter</button>
        <a class="admin-button admin-button--quiet" href="{{ route('admin.audit-logs.index') }}">Reset</a>
    </x-admin.filter-bar>
    <div style="height: 20px;"></div>
    <x-admin.data-table title="Aktivitas tercatat" description="{{ $logs->total() }} event ditemukan.">
        <thead><tr><th>Waktu</th><th>Action</th><th>Actor</th><th>Subject</th><th>Reason</th></tr></thead>
        <tbody>
            @forelse ($logs as $log)
                <tr><td>{{ $log->created_at?->format('d M Y H:i:s') }}</td><td><span class="admin-table__primary">{{ $log->action }}</span><span class="admin-table__secondary">{{ $log->context['module'] ?? '—' }}</span></td><td>{{ $log->actor?->name ?? $log->actor_email ?? 'System' }}</td><td><span class="admin-table__primary">{{ class_basename($log->subject_type) }}</span><span class="admin-table__secondary">{{ $log->subject_id }}</span></td><td>{{ $log->reason ?: '—' }}</td></tr>
            @empty
                <tr><td colspan="5"><x-admin.empty-state title="Belum ada audit log" description="Aksi sensitif akan muncul setelah tercatat." /></td></tr>
            @endforelse
        </tbody>
    </x-admin.data-table>
    <x-admin.pagination :current="$logs->currentPage()" :total="$logs->lastPage()" :label="$logs->total().' event'" />
@endsection
