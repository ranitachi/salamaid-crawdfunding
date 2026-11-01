@php($editing = $category->exists)

<form method="POST" action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
    @csrf
    @if ($editing)
        @method('PATCH')
    @endif

    <div class="admin-grid">
        <section class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h2>{{ $editing ? 'Ubah Kategori' : 'Kategori Baru' }}</h2>
                    <p>Nama, slug, ikon, dan status aktif kategori.</p>
                </div>
            </div>
            <div class="admin-panel__body">
                <div style="display:grid; gap:16px;">
                    <div class="admin-field">
                        <label for="name">Nama kategori</label>
                        <input id="name" name="name" type="text" required maxlength="100" value="{{ old('name', $category->name) }}" placeholder="Contoh: Pendidikan">
                        @error('name')<div style="color:var(--admin-coral); font-size:11px;">{{ $message }}</div>@enderror
                    </div>

                    <div class="admin-field">
                        <label for="slug">Slug</label>
                        <input id="slug" name="slug" type="text" maxlength="120" value="{{ old('slug', $category->slug) }}" placeholder="Kosongkan untuk dibuat otomatis dari nama">
                        @error('slug')<div style="color:var(--admin-coral); font-size:11px;">{{ $message }}</div>@enderror
                        <p class="admin-table__secondary">Slug harus unik dan digunakan pada URL kategori.</p>
                    </div>

                    <div class="admin-field">
                        <label for="icon">Ikon</label>
                        <input id="icon" name="icon" type="text" maxlength="255" value="{{ old('icon', $category->icon) }}" placeholder="Nama ikon opsional, mis. book-open">
                        @error('icon')<div style="color:var(--admin-coral); font-size:11px;">{{ $message }}</div>@enderror
                    </div>

                    <div class="admin-field">
                        <label for="is_active">Status</label>
                        <input type="hidden" name="is_active" value="0">
                        <label for="is_active" style="display:flex; gap:8px; align-items:center; font-family:inherit; font-size:12px; text-transform:none; color:var(--admin-ink);">
                            <input id="is_active" name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $category->is_active ?? true)) style="width:auto; min-height:auto;">
                            Aktif dan dapat dipilih untuk campaign
                        </label>
                        @error('is_active')<div style="color:var(--admin-coral); font-size:11px;">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </section>

        <aside class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h2>Panduan</h2>
                    <p>Aturan pengelolaan kategori.</p>
                </div>
            </div>
            <div class="admin-panel__body">
                <ul style="margin:0; padding-left:18px; font-size:12px; line-height:1.7;">
                    <li>Slug harus unik. Jika dikosongkan, slug dibuat dari nama.</li>
                    <li>Kategori nonaktif tidak ditawarkan untuk campaign baru.</li>
                    <li>Kategori yang masih dipakai campaign tidak dapat dihapus.</li>
                    <li>Semua perubahan dicatat melalui audit logger.</li>
                </ul>
            </div>
        </aside>
    </div>

    <div style="margin-top:20px; display:flex; gap:8px; flex-wrap:wrap;">
        <button type="submit" class="admin-button admin-button--primary">{{ $editing ? 'Simpan Perubahan' : 'Buat Kategori' }}</button>
        <a href="{{ route('admin.categories.index') }}" class="admin-button admin-button--quiet">Batal</a>
    </div>
</form>
