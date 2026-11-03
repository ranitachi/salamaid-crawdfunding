<form class="admin-panel" method="POST" action="{{ $editing ? route('admin.help.update', $article) : route('admin.help.store') }}">
    @csrf @if($editing) @method('PATCH') @endif
    <div class="admin-panel__body" style="display:grid;gap:16px;">
        <div class="admin-field"><label for="category">Kategori</label><input id="category" name="category" value="{{ old('category', $article->category) }}" maxlength="80" required>@error('category')<small class="admin-field__hint" style="color:var(--admin-coral)">{{ $message }}</small>@enderror</div>
        <div class="admin-field"><label for="question">Pertanyaan</label><input id="question" name="question" value="{{ old('question', $article->question) }}" maxlength="190" required>@error('question')<small class="admin-field__hint" style="color:var(--admin-coral)">{{ $message }}</small>@enderror</div>
        <div class="admin-field"><label for="answer">Jawaban</label><textarea id="answer" name="answer" rows="8" maxlength="10000" required>{{ old('answer', $article->answer) }}</textarea><small class="admin-field__hint">Teks akan ditampilkan sebagai paragraf pada halaman bantuan publik.</small>@error('answer')<small class="admin-field__hint" style="color:var(--admin-coral)">{{ $message }}</small>@enderror</div>
        <div class="admin-field"><label for="sort_order">Urutan</label><input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $article->sort_order ?? 0) }}"></div>
        <label style="display:flex;align-items:center;gap:8px;font-size:13px"><input type="hidden" name="is_published" value="0"><input type="checkbox" name="is_published" value="1" @checked((bool) old('is_published', $article->is_published ?? true))> Tampilkan di halaman publik</label>
    </div>
    <div class="admin-panel__footer" style="display:flex;gap:8px"><button class="admin-button admin-button--primary" type="submit">{{ $editing ? 'Simpan Perubahan' : 'Buat Artikel' }}</button><a class="admin-button admin-button--quiet" href="{{ route('admin.help.index') }}">Batal</a></div>
</form>
