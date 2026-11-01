@props(['title' => 'Belum ada data', 'description' => 'Data yang sesuai belum tersedia.'])

<div class="admin-empty">
    <div>
        <div class="admin-empty__mark">∅</div>
        <h3>{{ $title }}</h3>
        <p>{{ $description }}</p>
        @if (trim($slot))<div style="margin-top: 16px;">{{ $slot }}</div>@endif
    </div>
</div>
