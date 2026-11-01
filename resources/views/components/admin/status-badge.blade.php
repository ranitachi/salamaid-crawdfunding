@props(['status', 'label' => null])

<span class="admin-status admin-status--{{ Illuminate\Support\Str::slug($status, '_') }}">{{ $label ?? str_replace('_', ' ', $status) }}</span>
