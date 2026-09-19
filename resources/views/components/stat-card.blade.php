@props(['title', 'value', 'icon', 'color' => 'primary', 'subtitle' => null, 'iconBg' => null])
@php
    $statColors = [
        'primary' => '15 118 110',
        'secondary' => '79 70 229',
        'success' => '16 185 129',
        'warning' => '245 158 11',
    ];
@endphp
<div class="card dashboard-stat-card p-5" style="--stat-color: {{ $statColors[$color] ?? $statColors['primary'] }}">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-theme-muted mb-1">{{ $title }}</p>
            <p class="dashboard-stat-value text-3xl font-bold text-theme-text mt-2">{{ $value }}</p>
            @if($subtitle)
                <p class="text-xs text-theme-muted mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="dashboard-stat-icon flex-shrink-0">
            <i class="fas {{ $icon }} text-lg" aria-hidden="true"></i>
        </div>
    </div>
</div>
