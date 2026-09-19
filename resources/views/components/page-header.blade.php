@props(['title', 'subtitle' => null])
<div class="mb-6">
    <div class="page-header-content flex items-center justify-between">
        <div class="min-w-0">
            <h2 class="text-xl font-bold text-theme-text">{{ $title }}</h2>
            @if($subtitle)
                <p class="text-sm text-theme-muted mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
        @if(isset($actions))
            <div class="page-header-actions flex items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
