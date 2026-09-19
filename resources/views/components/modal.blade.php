@props(['id', 'title'])
<div id="{{ $id }}" x-data="{ open: false }"
     @open-modal-{{ $id }}.window="open = true"
     @close-modal-{{ $id }}.window="open = false"
     x-show="open" x-transition.opacity
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none">
    <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
    <div class="relative card p-6 w-full max-w-md z-10" @click.stop
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-theme-text">{{ $title }}</h3>
            <button @click="open = false" class="btn btn-ghost btn-sm rounded-full w-8 h-8 p-0">
                <i class="fas fa-times"></i>
            </button>
        </div>
        {{ $slot }}
    </div>
</div>
