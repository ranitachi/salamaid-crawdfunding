@props(['id', 'title' => 'Konfirmasi'])

<div class="admin-modal-backdrop" id="{{ $id }}" data-admin-modal hidden>
    <section class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
        <header class="admin-modal__header">
            <h2 id="{{ $id }}-title">{{ $title }}</h2>
            <button class="admin-modal__close" type="button" data-admin-modal-close aria-label="Tutup">×</button>
        </header>
        <div class="admin-modal__body">{{ $slot }}</div>
        <footer class="admin-modal__footer">
            <button class="admin-button admin-button--quiet" type="button" data-admin-modal-close>Batal</button>
            <button class="admin-button admin-button--primary" type="button">Lanjutkan</button>
        </footer>
    </section>
</div>
