@props([
    'status',
    'label' => null,
    'tone' => null,
])

@php
    $statusMap = [
        'draft' => 'Draft',
        'under_review' => 'Menunggu Review',
        'published' => 'Dipublikasikan',
        'closed' => 'Ditutup',
        'rejected' => 'Ditolak',
        'suspended' => 'Ditangguhkan',
        'pending' => 'Menunggu Verifikasi',
        'verified' => 'Terverifikasi',
    ];

    $resolvedLabel = $label ?? ($statusMap[$status] ?? ucfirst(str_replace('_', ' ', (string) $status)));
@endphp

<x-admin.status-badge :status="$status" :label="$resolvedLabel" {{ $attributes }} />
