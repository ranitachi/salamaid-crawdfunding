@props(['message' => null, 'type' => 'success'])

@if ($message || session('success') || session('error'))
    <div class="admin-toast" role="status">
        <span aria-hidden="true">{{ $type === 'error' || session('error') ? '!' : '✓' }}</span>
        <span>{{ $message ?? session('success') ?? session('error') }}</span>
    </div>
@endif
