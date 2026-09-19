@props(['id', 'action', 'title' => 'Delete Confirmation', 'message' => 'This action cannot be undone. Are you sure?', 'method' => 'DELETE'])
<div id="{{ $id }}" x-data="{ open: false }"
     @open-{{ $id }}.window="open = true"
     x-show="open" x-transition.opacity
     class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
    <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
    <div class="relative card p-6 w-full max-w-sm z-10"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-theme-danger"></i>
            </div>
            <div>
                <h3 class="font-semibold text-theme-text">{{ $title }}</h3>
                <p class="text-sm text-theme-muted mt-0.5">{{ $message }}</p>
            </div>
        </div>
        <div class="flex gap-2 justify-end">
            <button @click="open = false" class="btn btn-secondary btn-sm">Cancel</button>
            <form action="{{ $action }}" method="POST">
                @csrf
                @method($method)
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>
</div>
