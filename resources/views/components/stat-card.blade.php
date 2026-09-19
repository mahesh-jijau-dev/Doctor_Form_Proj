@props(['title', 'value', 'icon', 'color' => 'primary', 'subtitle' => null, 'iconBg' => null])
<div class="card p-5">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-theme-muted mb-1">{{ $title }}</p>
            <p class="text-3xl font-bold text-theme-text">{{ $value }}</p>
            @if($subtitle)
                <p class="text-xs text-theme-muted mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0
                    {{ $iconBg ?? 'bg-theme-primary' }} bg-opacity-15">
            <i class="fas {{ $icon }} text-theme-primary text-lg"></i>
        </div>
    </div>
</div>
