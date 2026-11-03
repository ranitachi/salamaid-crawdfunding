@extends(view()->exists('admin.layouts.app') ? 'admin.layouts.app' : 'admin.review.layout', ['title' => 'Campaign'])

@section('title', 'Campaign')

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Campaign']]" />

    <x-admin.page-header
        title="Moderasi Campaign"
        description="Review konten, kelola publikasi, feature, dan penutupan campaign."
    >
        <div class="admin-page-header__actions"><span class="admin-pagination" style="padding:0; border:0;">{{ $campaigns->total() }} campaign ditemukan</span>@if(auth()->user()?->hasAdminPermission('campaigns.manage'))<a class="admin-button admin-button--primary" href="{{ route('admin.campaigns.create') }}">+ Buat Campaign</a>@endif</div>
    </x-admin.page-header>

    <x-admin.filter-bar method="GET" action="{{ route('admin.campaigns.index') }}">
        <div class="admin-field">
            <label for="search">Cari</label>
            <input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Judul, slug, lokasi">
        </div>
        <div class="admin-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="admin-field">
            <label for="category_id">Kategori</label>
            <select id="category_id" name="category_id">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="admin-field">
            <label for="organization_id">Organisasi</label>
            <select id="organization_id" name="organization_id">
                <option value="">Semua organisasi</option>
                @foreach ($organizations as $organization)
                    <option value="{{ $organization->id }}" @selected(($filters['organization_id'] ?? '') === $organization->id)>{{ $organization->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="admin-field">
            <label for="featured">Featured</label>
            <select id="featured" name="featured">
                <option value="">Semua</option>
                <option value="1" @selected((string) ($filters['featured'] ?? '') === '1')>Featured</option>
                <option value="0" @selected((string) ($filters['featured'] ?? '') === '0')>Tidak featured</option>
            </select>
        </div>
        <div class="admin-field">
            <label for="date_from">Dibuat dari</label>
            <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="admin-field">
            <label for="date_to">Sampai</label>
            <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="admin-field">
            <label for="per_page">Per halaman</label>
            <select id="per_page" name="per_page">
                @foreach ([15, 25, 50, 100] as $size)
                    <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 15) === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="admin-button admin-button--primary">Terapkan</button>
        <a href="{{ route('admin.campaigns.index') }}" class="admin-button admin-button--quiet">Reset</a>
    </x-admin.filter-bar>

    <div style="height: 20px;"></div>

    <x-admin.data-table
        title="Daftar campaign"
        description="{{ $campaigns->total() }} campaign ditemukan."
    >
        @if ($campaigns->isEmpty())
            <tbody>
            <tr>
                <td colspan="8">
                    <x-admin-review.empty-state
                        title="Belum ada campaign"
                        description="Campaign yang diajukan organisasi akan muncul di sini."
                    />
                </td>
            </tr>
            </tbody>
        @else
            <thead>
            <tr>
                <th>Campaign</th>
                <th>Organisasi</th>
                <th>Kategori</th>
                <th>Status</th>
                <th>Target / Terkumpul</th>
                <th>Donasi</th>
                <th>Deadline</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($campaigns as $campaign)
                <tr>
                    <td>
                        <a href="{{ route('admin.campaigns.show', $campaign) }}" class="admin-table__primary">{{ $campaign->title }}</a>
                        <span class="admin-table__secondary">
                            {{ $campaign->slug }}
                            @if ($campaign->is_featured)
                                · <x-admin.status-badge status="featured" label="Featured" />
                            @endif
                        </span>
                    </td>
                    <td>
                        <span class="admin-table__primary">{{ $campaign->organization?->name ?? '—' }}</span>
                        @if ($campaign->organization)
                            <span class="admin-table__secondary">
                                <x-admin-review.status-badge :status="$campaign->organization->status" />
                            </span>
                        @endif
                    </td>
                    <td>{{ $campaign->category?->name ?? '—' }}</td>
                    <td><x-admin-review.status-badge :status="$campaign->status" /></td>
                    <td>
                        <span class="admin-table__primary">Rp {{ number_format((int) $campaign->target_amount, 0, ',', '.') }}</span>
                        <span class="admin-table__secondary">Rp {{ number_format((int) $campaign->collected_amount, 0, ',', '.') }}</span>
                    </td>
                    <td>{{ $campaign->donations_count }}</td>
                    <td>{{ $campaign->deadline_at?->format('d M Y') ?? '—' }}</td>
                    <td>
                        <a href="{{ route('admin.campaigns.show', $campaign) }}" class="admin-button admin-button--quiet">Detail</a>
                        @can('update', $campaign)
                            <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="admin-button admin-button--primary">Edit</a>
                        @endcan
                        @can('viewAny', \Modules\Transparency\Entities\CampaignUpdate::class)
                            <a href="{{ route('admin.transparency.create', ['campaign_id' => $campaign->id]) }}" class="admin-button admin-button--quiet">Transparansi</a>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        @endif
    </x-admin.data-table>

    {{ $campaigns->links('admin.review.pagination') }}
@endsection
