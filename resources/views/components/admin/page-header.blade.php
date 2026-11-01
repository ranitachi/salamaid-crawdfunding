@props(['title', 'description' => null])

<div class="admin-page-header">
    <div>
        <h1>{{ $title }}</h1>
        @if ($description)
            <p>{{ $description }}</p>
        @endif
    </div>
    @if (trim($slot))
        <div class="admin-page-header__actions">{{ $slot }}</div>
    @endif
</div>
