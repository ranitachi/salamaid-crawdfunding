@extends('admin.layouts.app', ['title' => 'Detail Update'])

@php
    use Modules\Transparency\App\Enums\CampaignUpdateStatus as Status;
    $textareaStyle = 'width:100%; min-height:80px; padding:8px 10px; border:1px solid var(--admin-ink); font:inherit; font-size:12px;';
    $canReview = auth()->user()->can('review', $update);
    $canSubmit = auth()->user()->can('submit', $update);
    $canPublish = auth()->user()->can('publish', $update);
    $canManageFiles = auth()->user()->can('manageAttachments', $update) && $update->isEditable();
@endphp

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Transparansi', 'url' => route('admin.transparency.index')], ['label' => 'Detail']]" />
    <x-admin.page-header :title="$update->title" :description="($update->typeEnum()?->label() ?? $update->type).' · '.($update->campaign?->title ?? 'Campaign tidak tersedia')">
        <a class="admin-button admin-button--quiet" href="{{ route('admin.transparency.index') }}">← Kembali</a>
    </x-admin.page-header>

    @if (session('success'))<x-admin.toast :message="session('success')" />@endif
    @if (session('error'))<x-admin.toast :message="session('error')" type="error" />@endif
    @if ($errors->any())
        <div class="admin-alert" role="alert" style="margin-bottom:16px;"><span class="admin-alert__icon">!</span>
            <div>@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        </div>
    @endif

    <div class="admin-grid">
        <div style="display:grid; gap:20px;">
            <section class="admin-panel">
                <div class="admin-panel__header">
                    <div><h2>Konten</h2><p>Diperiksa sebelum tampil publik.</p></div>
                    <x-admin.status-badge :status="$update->status" :label="$status?->label()" />
                </div>
                <div class="admin-panel__body">
                    <div style="white-space:pre-line; font-size:13px; line-height:1.6;">{{ $update->content }}</div>

                    @if ($update->review_note)
                        <div class="admin-alert" style="margin-top:12px;">
                            <span class="admin-alert__icon">!</span>
                            <div><strong>Catatan review</strong><p>{{ $update->review_note }}</p></div>
                        </div>
                    @endif

                    <dl class="admin-detail-list">
                        <div><dt>Campaign</dt><dd>@if ($update->campaign)<a href="{{ route('admin.campaigns.show', $update->campaign) }}">{{ $update->campaign->title }}</a> ({{ $update->campaign->status }})@else—@endif</dd></div>
                        <div><dt>Organisasi</dt><dd>{{ $update->campaign?->organization?->name ?? '—' }}</dd></div>
                        @if ($update->isFundReport())
                            <div><dt>Nominal digunakan</dt><dd>Rp {{ number_format((int) $update->amount_used, 0, ',', '.') }}</dd></div>
                            <div><dt>Payout terkait</dt><dd>
                                @if ($update->payout)
                                    @can('view', $update->payout)
                                        <a href="{{ route('admin.payouts.show', $update->payout) }}">Rp {{ number_format($update->payout->amount, 0, ',', '.') }}</a>
                                    @else
                                        Rp {{ number_format($update->payout->amount, 0, ',', '.') }}
                                    @endcan
                                    · {{ $update->payout->status }}
                                @else—@endif
                            </dd></div>
                        @endif
                        <div><dt>Dicatat oleh</dt><dd>{{ $update->creator?->name ?? '—' }}</dd></div>
                        <div><dt>Dikirim review</dt><dd>{{ $update->submitted_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                        <div><dt>Reviewer terakhir</dt><dd>{{ $update->reviewer?->name ?? '—' }} {{ $update->reviewed_at?->format('· d M Y H:i') }}</dd></div>
                        <div><dt>Dipublikasikan</dt><dd>{{ $update->published_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                        <div><dt>Ditarik</dt><dd>{{ $update->unpublished_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                    </dl>
                </div>
            </section>

            <x-admin.data-table title="Bukti penggunaan dana" description="{{ $update->attachments->count() }} file · disimpan privat, hanya dapat diunduh admin berwenang.">
                <thead><tr><th>File</th><th>Tipe</th><th>Ukuran</th><th>Diunggah</th><th></th></tr></thead>
                <tbody>
                    @forelse ($update->attachments as $attachment)
                        <tr>
                            <td>
                                @if (str_starts_with((string) $attachment->mime_type, 'image/'))
                                    <a href="{{ route('admin.transparency.attachments.preview', [$update, $attachment]) }}" target="_blank" rel="noopener" aria-label="Lihat {{ $attachment->original_name }}">
                                        <img src="{{ route('admin.transparency.attachments.preview', [$update, $attachment]) }}" alt="{{ $attachment->original_name }}" loading="lazy" style="display:block; width:96px; height:64px; margin-bottom:8px; border:1px solid var(--admin-line); object-fit:cover;">
                                    </a>
                                @endif
                                <span class="admin-table__primary">{{ $attachment->original_name }}</span><span class="admin-table__secondary">sha256 {{ \Illuminate\Support\Str::limit($attachment->checksum, 12, '…') }}</span>
                            </td>
                            <td>{{ $attachment->mime_type }}</td>
                            <td>{{ number_format($attachment->size / 1024, 0, ',', '.') }} KB</td>
                            <td>{{ $attachment->uploader?->name ?? '—' }}<span class="admin-table__secondary">{{ $attachment->created_at?->format('d M Y H:i') }}</span></td>
                            <td style="display:flex; gap:6px;">
                                <a class="admin-button admin-button--quiet" href="{{ route('admin.transparency.attachments.download', [$update, $attachment]) }}">Unduh</a>
                                @if ($canManageFiles)
                                    <form method="POST" action="{{ route('admin.transparency.attachments.destroy', [$update, $attachment]) }}">
                                        @csrf @method('DELETE')
                                        <button class="admin-button admin-button--danger" type="submit">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty-state title="Belum ada bukti" description="{{ $update->isFundReport() ? 'Laporan penggunaan dana wajib memiliki bukti sebelum dikirim review.' : 'Update ini belum memiliki lampiran.' }}" /></td></tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </div>

        <aside class="admin-panel">
            <div class="admin-panel__header"><div><h2>Aksi review</h2><p>Setiap aksi dicatat pada audit log.</p></div></div>
            <div class="admin-panel__body" style="display:grid; gap:14px;">
                @if ($canManageFiles)
                    <form method="POST" action="{{ route('admin.transparency.attachments.store', $update) }}" enctype="multipart/form-data" style="display:grid; gap:8px;">
                        @csrf
                        <div class="admin-field">
                            <label for="file">Unggah bukti ({{ implode(', ', $evidence['mimes']) }}, maks {{ $evidence['max_kilobytes'] / 1024 }} MB)</label>
                            <input id="file" name="file" type="file" required accept=".{{ implode(',.', $evidence['mimes']) }}">
                        </div>
                        <button class="admin-button" type="submit">Unggah bukti</button>
                    </form>
                @endif

                @if ($canSubmit && $status?->canTransitionTo(Status::UNDER_REVIEW))
                    <form method="POST" action="{{ route('admin.transparency.submit', $update) }}">@csrf
                        <button class="admin-button admin-button--primary" type="submit">Kirim ke review</button>
                    </form>
                @endif

                @if ($status === Status::DRAFT)
                    <p class="admin-table__secondary" style="margin:0; line-height:1.5;">Draft dibuat oleh <strong>{{ $update->creator?->name ?? 'pengguna admin' }}</strong>. Setelah dikirim ke review, hanya reviewer dengan permission <code>transparency.review</code> yang dapat menyetujui dan mempublikasikannya.</p>
                @endif

                @if ($canReview && $status === Status::UNDER_REVIEW)
                    <form method="POST" action="{{ route('admin.transparency.approve', $update) }}">@csrf
                        <button class="admin-button admin-button--primary" type="submit">✓ Setujui</button>
                    </form>
                    <form method="POST" action="{{ route('admin.transparency.reject', $update) }}" style="display:grid; gap:8px;">@csrf
                        <div class="admin-field">
                            <label for="reject-reason">Alasan revisi (wajib)</label>
                            <textarea id="reject-reason" name="reason" required minlength="5" maxlength="2000" style="{{ $textareaStyle }}">{{ old('reason') }}</textarea>
                        </div>
                        <button class="admin-button admin-button--danger" type="submit">Minta revisi</button>
                    </form>
                @endif

                @if ($canPublish && $status?->canTransitionTo(Status::PUBLISHED))
                    <form method="POST" action="{{ route('admin.transparency.publish', $update) }}">@csrf
                        <button class="admin-button admin-button--primary" type="submit">Publikasikan</button>
                    </form>
                @endif

                @if ($canPublish && $status === Status::PUBLISHED)
                    <form method="POST" action="{{ route('admin.transparency.unpublish', $update) }}" style="display:grid; gap:8px;">@csrf
                        <div class="admin-field">
                            <label for="unpublish-reason">Alasan penarikan (wajib)</label>
                            <textarea id="unpublish-reason" name="reason" required minlength="5" maxlength="2000" style="{{ $textareaStyle }}">{{ old('reason') }}</textarea>
                        </div>
                        <button class="admin-button admin-button--danger" type="submit">Tarik dari publikasi</button>
                    </form>
                @endif

                @unless ($canReview || $canPublish)
                    <x-admin.empty-state title="Tidak ada aksi" description="Role Anda hanya dapat melihat update ini." />
                @endunless
            </div>
        </aside>
    </div>
@endsection
