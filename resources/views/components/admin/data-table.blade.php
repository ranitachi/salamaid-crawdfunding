@props(['title' => null, 'description' => null])

<section class="admin-panel">
    @if ($title)
        <div class="admin-panel__header">
            <div>
                <h2>{{ $title }}</h2>
                @if ($description)<p>{{ $description }}</p>@endif
            </div>
            @if (trim($header ?? '')){{ $header }}@endif
        </div>
    @endif
    <div class="admin-panel__body">
        <div class="admin-table-wrap">
            <table class="admin-table">{{ $slot }}</table>
        </div>
    </div>
</section>
