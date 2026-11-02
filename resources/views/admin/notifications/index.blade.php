@extends('admin.layouts.app', ['title' => 'Notifikasi'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Notifikasi']]" />
    <x-admin.page-header title="Notifikasi" description="Pekerjaan review dan aktivitas admin yang membutuhkan perhatian.">
        @if ($notifications->whereNull('read_at')->isNotEmpty())
            <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                @csrf
                <button class="admin-button admin-button--primary" type="submit">Tandai semua sudah dibaca</button>
            </form>
        @endif
    </x-admin.page-header>

    <x-admin.data-table title="Kotak notifikasi" description="{{ $notifications->total() }} notifikasi ditemukan.">
        <thead><tr><th>Status</th><th>Notifikasi</th><th>Waktu</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse ($notifications as $notification)
                @php($data = (array) $notification->data)
                <tr>
                    <td><x-admin.status-badge :status="$notification->read_at ? 'read' : 'pending'" :label="$notification->read_at ? 'Dibaca' : 'Baru'" /></td>
                    <td><span class="admin-table__primary">{{ $data['title'] ?? 'Notifikasi admin' }}</span><span class="admin-table__secondary">{{ $data['message'] ?? '—' }}</span></td>
                    <td>{{ $notification->created_at?->format('d M Y H:i') }}</td>
                    <td>
                        @if (!empty($data['url']))<a class="admin-button admin-button--quiet" href="{{ $data['url'] }}">Buka</a>@endif
                        @if (!$notification->read_at)
                            <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}" style="display:inline">@csrf<button class="admin-button admin-button--quiet" type="submit">Tandai dibaca</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4"><x-admin.empty-state title="Belum ada notifikasi" description="Notifikasi pekerjaan review akan muncul di sini." /></td></tr>
            @endforelse
        </tbody>
    </x-admin.data-table>
    <x-admin.pagination :current="$notifications->currentPage()" :total="$notifications->lastPage()" :label="$notifications->total().' notifikasi'" />
@endsection
