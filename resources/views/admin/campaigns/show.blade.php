@extends(view()->exists('admin.layouts.app') ? 'admin.layouts.app' : 'admin.review.layout', ['title' => $campaign->title])

@section('title', $campaign->title)

@section('content')
    <x-admin.breadcrumb :items="[
        ['label' => 'Campaign', 'url' => route('admin.campaigns.index')],
        ['label' => $campaign->title],
    ]" />

    <x-admin.page-header :title="$campaign->title" :description="$campaign->slug">
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <x-admin-review.status-badge :status="$campaign->status" />
            @if ($canEdit)
                <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="admin-button admin-button--quiet">Edit Campaign</a>
            @endif
            @if ($campaign->is_featured)
                <x-admin.status-badge status="featured" label="Featured" />
            @endif

            @if ($canModerate)
                @if ($campaign->status === 'draft')
                    <form method="POST" action="{{ route('admin.campaigns.submit', $campaign) }}">
                        @csrf
                        <button type="submit" class="admin-button admin-button--primary">Kirim ke Review</button>
                    </form>
                @endif

                @if ($campaign->status === 'under_review')
                    <form method="POST" action="{{ route('admin.campaigns.publish', $campaign) }}">
                        @csrf
                        <button type="submit" class="admin-button admin-button--primary">Setujui &amp; Publikasikan</button>
                    </form>
                @endif

                @if ($campaign->status === 'published')
                    <form method="POST" action="{{ route('admin.campaigns.close', $campaign) }}" onsubmit="return confirm('Tutup campaign ini? Campaign yang ditutup tidak dapat dibuka kembali dari alur ini.');">
                        @csrf
                        <button type="submit" class="admin-button admin-button--quiet">Tutup Campaign</button>
                    </form>
                @endif

                @if (in_array($campaign->status, ['suspended', 'rejected'], true))
                    <form method="POST" action="{{ route('admin.campaigns.restore', $campaign) }}">
                        @csrf
                        <button type="submit" class="admin-button admin-button--quiet">
                            {{ $campaign->status === 'suspended' ? 'Pulihkan Publikasi' : 'Kembalikan ke Draft' }}
                        </button>
                    </form>
                @endif

                @if ($canFeature && in_array($campaign->status, ['published', 'suspended'], true))
                    @if ($campaign->is_featured)
                        <form method="POST" action="{{ route('admin.campaigns.unfeature', $campaign) }}">
                            @csrf
                            <button type="submit" class="admin-button admin-button--quiet">Lepas Featured</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.campaigns.feature', $campaign) }}">
                            @csrf
                            <button type="submit" class="admin-button admin-button--primary">Jadikan Featured</button>
                        </form>
                    @endif
                @endif
            @endif
        </div>
    </x-admin.page-header>

    @if ($canModerate && $campaign->status === 'under_review')
        <section class="admin-panel" style="margin-bottom:20px;">
            <div class="admin-panel__header">
                <div>
                    <h2>Keputusan Review</h2>
                    <p>Penolakan wajib menyertakan alasan agar organisasi dapat memperbaiki campaign.</p>
                </div>
            </div>
            <div class="admin-panel__body">
                <form method="POST" action="{{ route('admin.campaigns.reject', $campaign) }}">
                    @csrf
                    <div class="admin-field">
                        <label for="reject-reason">Alasan penolakan (wajib)</label>
                        <textarea id="reject-reason" name="reason" required minlength="5" maxlength="2000" placeholder="Jelaskan bagian konten, target, deadline, atau media yang perlu diperbaiki." style="width:100%; min-height:90px; padding:8px 10px; border:1px solid var(--admin-ink); font:inherit; font-size:12px;">{{ old('reason') }}</textarea>
                    </div>
                    <div style="margin-top:10px;">
                        <button type="submit" class="admin-button admin-button--danger">Tolak Campaign</button>
                    </div>
                </form>
            </div>
        </section>
    @endif

    @if ($canModerate && $campaign->status === 'published')
        <section class="admin-panel" style="margin-bottom:20px;">
            <div class="admin-panel__header">
                <div>
                    <h2>Tindakan Moderasi</h2>
                    <p>Penangguhan wajib menyertakan alasan.</p>
                </div>
            </div>
            <div class="admin-panel__body">
                <form method="POST" action="{{ route('admin.campaigns.suspend', $campaign) }}">
                    @csrf
                    <div class="admin-field">
                        <label for="suspend-reason">Alasan penangguhan (wajib)</label>
                        <textarea id="suspend-reason" name="reason" required minlength="5" maxlength="2000" placeholder="Contoh: indikasi pelanggaran, konten tidak sesuai, atau laporan penyalahgunaan." style="width:100%; min-height:90px; padding:8px 10px; border:1px solid var(--admin-ink); font:inherit; font-size:12px;">{{ old('reason') }}</textarea>
                    </div>
                    <div style="margin-top:10px;">
                        <button type="submit" class="admin-button admin-button--danger">Tangguhkan Campaign</button>
                    </div>
                </form>
            </div>
        </section>
    @endif

    <div class="admin-stat-grid">
        <x-admin.stat-card label="Target Dana" :value="'Rp '.number_format((int) $campaign->target_amount, 0, ',', '.')" icon="◎" tone="blue" />
        <x-admin.stat-card label="Terkumpul" :value="'Rp '.number_format((int) $campaign->collected_amount, 0, ',', '.')" icon="↗" tone="lime" />
        <x-admin.stat-card label="Donasi" :value="$campaign->donations_count" :trend="'Paid: Rp '.number_format((int) ($campaign->paid_donation_total ?? 0), 0, ',', '.')" icon="✓" tone="green" />
        <x-admin.stat-card label="Deadline" :value="$campaign->deadline_at?->format('d M Y') ?? '—'" :trend="$campaign->deadline_at && $campaign->deadline_at->isPast() ? 'Sudah lewat' : 'Masih berjalan'" icon="⌁" tone="coral" />
    </div>

    @php
        $progress = (int) $campaign->target_amount > 0
            ? min(100, round(((int) $campaign->collected_amount / (int) $campaign->target_amount) * 100))
            : 0;
    @endphp
    <section class="admin-panel" style="margin-bottom:20px;">
        <div class="admin-panel__body">
            <div class="admin-table__secondary">Progres pendanaan {{ $progress }}%</div>
            <div class="admin-progress"><span style="width: {{ $progress }}%"></span></div>
        </div>
    </section>

    <div class="admin-grid">
        <section class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h2>Konten Campaign</h2>
                    <p>Periksa deskripsi, target, deadline, dan media sebelum publikasi.</p>
                </div>
            </div>
            <div class="admin-panel__body">
                @if ($campaign->cover_url)
                    <img src="{{ $campaign->cover_url }}" alt="Cover {{ $campaign->title }}" style="max-width:100%; border:1px solid var(--admin-ink); margin-bottom:14px;">
                @endif
                <dl class="admin-detail-list">
                    <div><dt>Kategori</dt><dd>{{ $campaign->category?->name ?? '—' }}</dd></div>
                    <div><dt>Lokasi</dt><dd>{{ $campaign->location_label ?: '—' }}</dd></div>
                    <div><dt>Dibuat</dt><dd>{{ $campaign->created_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                    <div><dt>Diperbarui</dt><dd>{{ $campaign->updated_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                </dl>
                <div style="margin-top:16px;">
                    <div class="admin-table__secondary">Deskripsi</div>
                    <div style="font-size:12px; line-height:1.6;">{!! nl2br(e($campaign->description ?: '—')) !!}</div>
                </div>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h2>Organisasi &amp; Moderasi</h2>
                    <p>Status organisasi memengaruhi eligibility publikasi.</p>
                </div>
            </div>
            <div class="admin-panel__body">
                @if ($campaign->organization)
                    <div style="margin-bottom:10px;">
                        <a href="{{ route('admin.organizations.show', $campaign->organization) }}" class="admin-table__primary">{{ $campaign->organization->name }}</a>
                        <div style="margin-top:6px;">
                            <x-admin-review.status-badge :status="$campaign->organization->status" />
                        </div>
                    </div>
                    <dl class="admin-detail-list">
                        <div><dt>Email</dt><dd>{{ $campaign->organization->contact_email ?: '—' }}</dd></div>
                        <div><dt>Telepon</dt><dd>{{ $campaign->organization->contact_phone ?: '—' }}</dd></div>
                        <div><dt>Diverifikasi</dt><dd>{{ $campaign->organization->verified_at?->format('d M Y') ?? '—' }}</dd></div>
                    </dl>
                @else
                    <x-admin-review.empty-state title="Organisasi tidak ditemukan" />
                @endif

                <div style="margin-top:18px;">
                    <div class="admin-table__secondary">Informasi moderasi</div>
                    <dl class="admin-detail-list">
                        <div><dt>Direview oleh</dt><dd>{{ $campaign->reviewer?->name ?? '—' }}</dd></div>
                        <div><dt>Waktu review</dt><dd>{{ $campaign->reviewed_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                        <div><dt>Dipublikasikan</dt><dd>{{ $campaign->published_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                        <div><dt>Ditutup</dt><dd>{{ $campaign->closed_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                    </dl>
                </div>

                @if ($campaign->rejection_reason)
                    <div class="admin-alert" style="margin-top:12px;">
                        <span class="admin-alert__icon">!</span>
                        <div><strong>Alasan penolakan</strong><p>{{ $campaign->rejection_reason }}</p></div>
                    </div>
                @endif
                @if ($campaign->suspension_reason)
                    <div class="admin-alert" style="margin-top:12px;">
                        <span class="admin-alert__icon">!</span>
                        <div><strong>Alasan penangguhan</strong><p>{{ $campaign->suspension_reason }}</p></div>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <div style="height:20px;"></div>

    <x-admin.data-table
        title="Media Campaign"
        description="{{ $campaign->media->count() }} media terlampir."
    >
        @if ($canEdit)
            <div class="admin-panel__body" style="border-bottom:1px solid var(--admin-line);">
                <form method="POST" action="{{ route('admin.campaigns.media.store', $campaign) }}" enctype="multipart/form-data" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto;gap:10px;align-items:end;">
                    @csrf
                    <div class="admin-field"><label for="media">Tambah gambar media</label><input id="media" name="media" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required><small class="admin-field__hint">JPG, PNG, atau WebP, maksimal 4 MB. Gambar dikompres otomatis.</small></div>
                    <div class="admin-field"><label for="caption">Caption (opsional)</label><input id="caption" name="caption" maxlength="190" placeholder="Keterangan gambar"></div>
                    <button class="admin-button admin-button--primary" type="submit">Upload Media</button>
                </form>
                @error('media')<small class="admin-field__hint" style="display:block;color:var(--admin-coral);margin-top:8px">{{ $message }}</small>@enderror
                @error('caption')<small class="admin-field__hint" style="display:block;color:var(--admin-coral);margin-top:8px">{{ $message }}</small>@enderror
            </div>
        @endif
        @if ($campaign->media->isEmpty())
            <tbody>
            <tr>
                        <td colspan="{{ $canEdit ? 5 : 4 }}">
                    <x-admin-review.empty-state
                        title="Belum ada media"
                        description="Campaign membutuhkan minimal satu media atau cover sebelum dapat dipublikasikan."
                    />
                </td>
            </tr>
            </tbody>
        @else
            <thead>
            <tr>
                <th>Tipe</th>
                <th>Preview</th>
                <th>Caption</th>
                <th>Urutan</th>
                @if ($canEdit)<th>Aksi</th>@endif
            </tr>
            </thead>
            <tbody>
            @foreach ($campaign->media as $media)
                <tr>
                    <td><x-admin.status-badge :status="$media->type" :label="$media->type" /></td>
                    @php($mediaUrl = str_contains($media->url, '/storage/') ? '/storage/'.\Illuminate\Support\Str::after($media->url, '/storage/') : $media->url)
                    <td><a href="{{ $mediaUrl }}" target="_blank" rel="noopener noreferrer"><img src="{{ $mediaUrl }}" alt="{{ $media->caption ?: 'Media campaign' }}" style="display:block;width:120px;height:72px;object-fit:cover;border:1px solid var(--admin-ink);border-radius:6px;"></a></td>
                    <td>{{ $media->caption ?: '—' }}</td>
                    <td>{{ $media->sort }}</td>
                    @if ($canEdit)
                        <td><form method="POST" action="{{ route('admin.campaigns.media.destroy', [$campaign, $media]) }}" onsubmit="return confirm('Hapus media ini?');">@csrf @method('DELETE')<button class="admin-button admin-button--quiet" type="submit">Hapus</button></form></td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        @endif
    </x-admin.data-table>

    @if (is_array($campaign->meta_json) && count($campaign->meta_json) > 0)
        <div style="height:20px;"></div>
        <x-admin.data-table title="Metadata Tambahan">
            <tbody>
            @foreach ($campaign->meta_json as $key => $value)
                <tr>
                    <th>{{ ucfirst(str_replace('_', ' ', (string) $key)) }}</th>
                    <td>{{ is_scalar($value) ? $value : json_encode($value) }}</td>
                </tr>
            @endforeach
            </tbody>
        </x-admin.data-table>
    @endif

    @if ($canDelete || $canForceDelete)
        <div style="height:20px;"></div>
        <section class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h2>Zona Berbahaya</h2>
                    <p>Campaign published atau yang sudah memiliki donasi tidak dapat dihapus permanen.</p>
                </div>
            </div>
            <div class="admin-panel__body">
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    @if ($canDelete)
                        <form method="POST" action="{{ route('admin.campaigns.destroy', $campaign) }}" onsubmit="return confirm('Hapus campaign ini dari daftar aktif?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-button admin-button--quiet">Hapus (soft delete)</button>
                        </form>
                    @endif
                    @if ($canForceDelete)
                        <form method="POST" action="{{ route('admin.campaigns.force-destroy', $campaign) }}" onsubmit="return confirm('Hapus campaign ini secara permanen? Tindakan ini tidak dapat dibatalkan.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-button admin-button--danger">Hapus Permanen</button>
                        </form>
                    @endif
                </div>
            </div>
        </section>
    @endif
@endsection
