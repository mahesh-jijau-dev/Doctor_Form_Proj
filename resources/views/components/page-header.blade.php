@props(['title', 'subtitle' => null, 'backUrl' => null, 'backLabel' => 'Back'])
<div class="mb-6">
    <div class="page-header-content flex items-center justify-between">
        <div class="min-w-0 flex items-center gap-3">
            @if($backUrl)
                <a href="{{ $backUrl }}" class="btn btn-secondary btn-sm flex-shrink-0" title="{{ $backLabel }}">
                    <i class="fas fa-arrow-left"></i>
                    <span>{{ $backLabel }}</span>
                </a>
            @endif
            <div>
            <h2 class="text-xl font-bold text-theme-text">{{ $title }}</h2>
            @if($subtitle)
                <p class="text-sm text-theme-muted mt-0.5">{{ $subtitle }}</p>
            @endif
            </div>
        </div>
        @if(isset($actions))
            <div class="page-header-actions flex items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
