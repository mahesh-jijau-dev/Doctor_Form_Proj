@extends('layouts.admin')
@section('title', 'Assign Form')
@section('page-title', 'Assign Form')

@section('content')
<div class="max-w-2xl">
    <x-page-header :title="'Assign: ' . $form->title" subtitle="Select doctors to assign this form to" />

    <div class="card p-6">
        <form action="{{ route('admin.forms.assign.update', $form) }}" method="POST"
              x-data="{ selected: {{ json_encode($assignedIds) }} }">
            @csrf

            @if(session('success'))
            <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 flex items-center gap-2">
                <i class="fas fa-check-circle text-green-500"></i>
                <span class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</span>
            </div>
            @endif

            <!-- Select all toggle -->
            @if($doctors->isNotEmpty())
            <div class="flex items-center justify-between mb-3 pb-3 border-b border-theme">
                <span class="text-sm font-medium text-theme-text">
                    <span x-text="selected.length"></span> of {{ $doctors->count() }} selected
                </span>
                <div class="flex gap-2">
                    <button type="button" @click="selected = {{ $doctors->pluck('id')->toJson() }}"
                            class="text-xs text-theme-primary hover:underline">Select All</button>
                    <span class="text-theme-muted">|</span>
                    <button type="button" @click="selected = []"
                            class="text-xs text-theme-muted hover:text-theme-text">Clear</button>
                </div>
            </div>
            @endif

            <div class="space-y-1 mb-6">
                @forelse($doctors as $doctor)
                <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-theme-surface-2 cursor-pointer transition-colors">
                    <input type="checkbox" name="doctor_ids[]" value="{{ $doctor->id }}"
                           class="rounded border-theme w-4 h-4 flex-shrink-0"
                           x-model="selected">
                    <div class="w-9 h-9 rounded-full bg-theme-primary flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-xs font-semibold">
                            {{ strtoupper(substr($doctor->name, 0, 1)) }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-theme-text">{{ $doctor->name }}</p>
                        <p class="text-xs text-theme-muted">
                            {{ $doctor->doctor?->specialty ?? $doctor->email }}
                        </p>
                    </div>
                    <i class="fas fa-check text-theme-primary text-sm"
                       x-show="selected.includes({{ $doctor->id }})" x-cloak></i>
                </label>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-user-doctor text-theme-muted text-3xl mb-2"></i>
                    <p class="text-sm text-theme-muted">No active doctors found.</p>
                    <a href="{{ route('admin.doctors.create') }}" class="btn btn-primary btn-sm mt-3">
                        <i class="fas fa-plus"></i> Add Doctor
                    </a>
                </div>
                @endforelse
            </div>

            @if($doctors->isNotEmpty())
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Assignments
                </button>
                <a href="{{ route('admin.forms.show', $form) }}" class="btn btn-secondary">Cancel</a>
            </div>
            @endif
        </form>
    </div>
</div>
@endsection
