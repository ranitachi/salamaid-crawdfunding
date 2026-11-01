@props([
    'title' => 'Belum ada data',
    'description' => null,
])

<x-admin.empty-state
    :title="$title"
    :description="$description ?? 'Data yang sesuai belum tersedia.'"
>
    {{ $slot }}
</x-admin.empty-state>
