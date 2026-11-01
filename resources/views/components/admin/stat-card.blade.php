@props(['label', 'value', 'trend' => null, 'icon' => '•', 'tone' => 'lime', 'href' => null])

<article class="admin-stat-card" @if($href) style="cursor: pointer;" onclick="window.location.href='{{ $href }}'" @endif>
    <div class="admin-stat-card__top">
        <span class="admin-stat-card__label">{{ $label }}</span>
        <span class="admin-stat-card__icon" style="background: var(--admin-{{ $tone }});">{{ $icon }}</span>
    </div>
    <div class="admin-stat-card__value">{{ $value }}</div>
    @if ($trend)
        <span class="admin-stat-card__trend {{ str_starts_with($trend, '-') ? 'is-muted' : '' }}">{{ $trend }}</span>
    @endif
</article>
