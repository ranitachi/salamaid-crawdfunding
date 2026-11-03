@extends(view()->exists('admin.layouts.app') ? 'admin.layouts.app' : 'admin.review.layout', ['title' => $organization->name])

@section('title', $organization->name)

@section('content')
    <x-admin.breadcrumb :items="[
        ['label' => 'Organisasi', 'url' => route('admin.organizations.index')],
        ['label' => $organization->name],
    ]" />

    <x-admin.page-header :title="$organization->name" :description="$organization->slug">
        @can('manageSettings', $organization)
            <a href="{{ route('admin.organizations.settings.edit', $organization) }}" class="admin-button admin-button--quiet">Edit profil publik</a>
        @endcan
        @if ($canReview)
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                @if ($organization->status === 'pending')
                    <form method="POST" action="{{ route('admin.organizations.approve', $organization) }}">
                        @csrf
                        <button type="submit" class="admin-button admin-button--primary">Setujui &amp; Verifikasi</button>
                    </form>
                @endif

                @if ($organization->status === 'verified')
                    <form method="POST" action="{{ route('admin.organizations.suspend', $organization) }}" style="display:flex; gap:8px; align-items:end; flex-wrap:wrap;">
                        @csrf
                        <div class="admin-field">
                            <label for="suspend-reason">Alasan penangguhan</label>
                            <input id="suspend-reason" name="reason" required minlength="5" maxlength="2000" placeholder="Alasan penangguhan" value="{{ old('reason') }}">
                        </div>
                        <button type="submit" class="admin-button admin-button--danger">Tangguhkan</button>
                    </form>
                @endif

                @if (in_array($organization->status, ['suspended', 'rejected'], true))
                    <form method="POST" action="{{ route('admin.organizations.restore', $organization) }}">
                        @csrf
                        <button type="submit" class="admin-button admin-button--quiet">Pulihkan Status</button>
                    </form>
                @endif
            </div>
        @endif
    </x-admin.page-header>

    @if ($canReview && $organization->status === 'pending')
        <section class="admin-panel" style="margin-bottom:20px;">
            <div class="admin-panel__header">
                <div>
                    <h2>Keputusan Review</h2>
                    <p>Penolakan wajib menyertakan alasan agar organisasi dapat memperbaiki pengajuan.</p>
                </div>
            </div>
            <div class="admin-panel__body">
                <form method="POST" action="{{ route('admin.organizations.reject', $organization) }}">
                    @csrf
                    <div class="admin-field">
                        <label for="reject-reason">Alasan penolakan (wajib)</label>
                        <textarea id="reject-reason" name="reason" required minlength="5" maxlength="2000" placeholder="Jelaskan alasan penolakan agar organisasi dapat memperbaiki pengajuan." style="width:100%; min-height:90px; padding:8px 10px; border:1px solid var(--admin-ink); font:inherit; font-size:12px;">{{ old('reason') }}</textarea>
                    </div>
                    <div style="margin-top:10px;">
                        <button type="submit" class="admin-button admin-button--danger">Tolak Organisasi</button>
                    </div>
                </form>
            </div>
        </section>
    @endif

    <div class="admin-stat-grid">
        <x-admin.stat-card label="Anggota" :value="$organization->members_count" icon="♙" tone="blue" />
        <x-admin.stat-card label="Campaign" :value="$organization->campaigns_count" icon="✦" tone="lime" />
        <x-admin.stat-card label="Status" :value="$organization->status" icon="◉" tone="yellow" />
        <x-admin.stat-card label="Diajukan" :value="$organization->created_at?->format('d M Y') ?? '—'" icon="⌁" tone="coral" />
    </div>

    <div class="admin-grid">
        <section class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h2>Profil Organisasi</h2>
                    <p>Kontak dan informasi dasar lembaga.</p>
                </div>
                <x-admin-review.status-badge :status="$organization->status" />
            </div>
            <div class="admin-panel__body">
                <dl class="admin-detail-list">
                    <div><dt>Email</dt><dd>{{ $organization->contact_email ?: '—' }}</dd></div>
                    <div><dt>Telepon</dt><dd>{{ $organization->contact_phone ?: '—' }}</dd></div>
                    <div><dt>Alamat</dt><dd>{{ $organization->address ?: '—' }}</dd></div>
                    <div><dt>Diajukan</dt><dd>{{ $organization->created_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                    <div><dt>Terverifikasi</dt><dd>{{ $organization->verified_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                </dl>
                <div style="margin-top:16px;">
                    <div class="admin-table__secondary">Deskripsi</div>
                    <div style="font-size:12px; line-height:1.6;">{!! nl2br(e($organization->description ?: '—')) !!}</div>
                </div>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h2>Rekening Organisasi</h2>
                    <p>Data sensitif hanya untuk reviewer.</p>
                </div>
            </div>
            <div class="admin-panel__body">
                @if ($canViewSensitive)
                    @if (is_array($organization->bank_account_json) && count($organization->bank_account_json) > 0)
                        <dl class="admin-detail-list">
                            @foreach ($organization->bank_account_json as $key => $value)
                                <div>
                                    <dt>{{ ucfirst(str_replace('_', ' ', (string) $key)) }}</dt>
                                    <dd>{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @else
                        <x-admin-review.empty-state title="Belum ada rekening" description="Organisasi belum mengisi data rekening." />
                    @endif
                @else
                    <x-admin-review.empty-state
                        title="Data rekening dibatasi"
                        description="Data rekening hanya dapat dilihat oleh role dengan permission review organisasi."
                    />
                @endif

                <div style="margin-top:18px;">
                    <div class="admin-table__secondary">Informasi review</div>
                    <dl class="admin-detail-list">
                        <div><dt>Direview oleh</dt><dd>{{ $organization->reviewer?->name ?? '—' }}</dd></div>
                        <div><dt>Waktu review</dt><dd>{{ $organization->reviewed_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                    </dl>
                </div>
                @if ($organization->rejection_reason)
                    <div class="admin-alert" style="margin-top:12px;">
                        <span class="admin-alert__icon">!</span>
                        <div><strong>Alasan penolakan</strong><p>{{ $organization->rejection_reason }}</p></div>
                    </div>
                @endif
                @if ($organization->suspension_reason)
                    <div class="admin-alert" style="margin-top:12px;">
                        <span class="admin-alert__icon">!</span>
                        <div><strong>Alasan penangguhan</strong><p>{{ $organization->suspension_reason }}</p></div>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <div style="height:20px;"></div>

    <x-admin.data-table
        title="Anggota Organisasi"
        description="{{ $organization->members_count }} anggota terdaftar."
    >
        @if ($organization->members->isEmpty())
            <tbody>
            <tr>
                <td colspan="4">
                    <x-admin-review.empty-state title="Belum ada anggota" description="Anggota organisasi akan tampil di sini." />
                </td>
            </tr>
            </tbody>
        @else
            <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <th>Bergabung</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($organization->members as $member)
                <tr>
                    <td><span class="admin-table__primary">{{ $member->user?->name ?? '—' }}</span></td>
                    <td>{{ $member->user?->email ?? '—' }}</td>
                    <td><x-admin.status-badge :status="$member->role" :label="$member->role" /></td>
                    <td>{{ $member->created_at?->format('d M Y') ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        @endif
    </x-admin.data-table>

    <div style="height:20px;"></div>

    <x-admin.data-table
        title="Campaign Organisasi"
        description="{{ $organization->campaigns_count }} campaign terdaftar."
    >
        @if ($organization->campaigns->isEmpty())
            <tbody>
            <tr>
                <td colspan="6">
                    <x-admin-review.empty-state title="Belum ada campaign" description="Campaign milik organisasi akan tampil di sini." />
                </td>
            </tr>
            </tbody>
        @else
            <thead>
            <tr>
                <th>Campaign</th>
                <th>Kategori</th>
                <th>Status</th>
                <th>Target</th>
                <th>Terkumpul</th>
                <th>Deadline</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($organization->campaigns as $campaign)
                <tr>
                    <td>
                        <a href="{{ route('admin.campaigns.show', $campaign) }}" class="admin-table__primary">{{ $campaign->title }}</a>
                        <span class="admin-table__secondary">{{ $campaign->slug }}</span>
                    </td>
                    <td>{{ $campaign->category?->name ?? '—' }}</td>
                    <td><x-admin-review.status-badge :status="$campaign->status" /></td>
                    <td>Rp {{ number_format((int) $campaign->target_amount, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format((int) $campaign->collected_amount, 0, ',', '.') }}</td>
                    <td>{{ $campaign->deadline_at?->format('d M Y') ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        @endif
    </x-admin.data-table>
@endsection
