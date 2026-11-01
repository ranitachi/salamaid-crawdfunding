@props(['current' => 1, 'total' => 1, 'label' => null])

<div class="admin-pagination">
    <span>{{ $label ?? "Halaman $current dari $total" }}</span>
    <div class="admin-pagination__links">
        <a class="admin-pagination__link" href="#" aria-label="Sebelumnya">‹</a>
        <a class="admin-pagination__link is-active" href="#">{{ $current }}</a>
        <a class="admin-pagination__link" href="#" aria-label="Berikutnya">›</a>
    </div>
</div>
