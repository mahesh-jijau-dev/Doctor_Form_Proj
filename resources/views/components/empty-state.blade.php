@props(['icon' => 'fa-inbox', 'title', 'subtitle' => null])
<div class="flex flex-col items-center justify-center py-16 px-4 text-center">
    <div class="w-16 h-16 rounded-2xl bg-theme-surface-2 flex items-center justify-center mb-4">
        <i class="fas {{ $icon }} text-theme-muted text-2xl"></i>
    </div>
    <h3 class="text-base font-semibold text-theme-text mb-1">{{ $title }}</h3>
    @if($subtitle)
        <p class="text-sm text-theme-muted max-w-sm">{{ $subtitle }}</p>
    @endif
    @if(isset($action))
        <div class="mt-4">{{ $action }}</div>
    @endif
</div>
