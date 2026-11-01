@props(['items' => []])

<nav class="admin-breadcrumb" aria-label="Breadcrumb">
    <a href="/admin">Admin</a>
    @foreach ($items as $item)
        <span aria-hidden="true">/</span>
        @if (!empty($item['url']))
            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
        @else
            <span class="admin-breadcrumb__current">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
