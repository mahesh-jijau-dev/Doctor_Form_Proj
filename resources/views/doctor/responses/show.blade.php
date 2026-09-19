@extends('layouts.doctor')
@section('title', 'Response Detail')
@section('page-title', 'Response Detail')

@section('content')
<div class="max-w-3xl space-y-4">
    <x-page-header :title="$response->form?->title ?? 'Response'" subtitle="Submitted response details">
        <x-slot:actions>
            <a href="{{ route('doctor.responses.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Meta info -->
    <div class="card p-5">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-theme-muted mb-0.5">Patient Name</p>
                <p class="text-sm font-medium text-theme-text">{{ $response->submitted_by_name ?? 'Anonymous' }}</p>
            </div>
            <div>
                <p class="text-xs text-theme-muted mb-0.5">Email</p>
                <p class="text-sm text-theme-text">{{ $response->submitted_by_email ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-theme-muted mb-0.5">Submitted At</p>
                <p class="text-sm text-theme-text">{{ $response->submitted_at?->format('M d, Y H:i') ?? '—' }}</p>
            </div>
        </div>

        @if($response->form)
        <div class="mt-4 pt-4 border-t border-theme flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs text-theme-muted">
                <i class="fas fa-file-alt"></i>
                <span>{{ $response->form->title }}</span>
            </div>
            <a href="{{ route('doctor.forms.show', $response->form) }}"
               class="text-xs text-theme-primary hover:underline">View Form</a>
        </div>
        @endif
    </div>

    <!-- Response field values -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-theme">
            <h2 class="text-sm font-semibold text-theme-text flex items-center gap-2">
                <i class="fas fa-list-check text-theme-primary"></i> Patient Answers
            </h2>
        </div>

        @forelse($response->values as $value)
        <div class="px-5 py-4 border-b border-theme last:border-0">
            <p class="text-xs font-semibold uppercase tracking-widest text-theme-muted mb-1.5">
                {{ $value->field_label }}
            </p>

            @if($value->file_path)
                <div class="flex items-center gap-3 p-3 rounded-lg bg-theme-surface-2">
                    <i class="fas fa-file text-theme-primary text-lg"></i>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-theme-text truncate">{{ $value->file_original_name }}</p>
                    </div>
                    <a href="{{ Storage::url($value->file_path) }}" target="_blank"
                       class="btn btn-sm btn-secondary flex-shrink-0">
                        <i class="fas fa-external-link-alt"></i> View
                    </a>
                </div>
            @elseif(is_array($value->value) || (is_string($value->value) && str_starts_with(trim((string) $value->value), '[')))
                @php $items = is_array($value->value) ? $value->value : json_decode($value->value, true); @endphp
                <div class="flex flex-wrap gap-1.5">
                    @foreach((array)$items as $item)
                    <span class="px-2.5 py-1 text-xs rounded-full bg-theme-surface-2 text-theme-text">{{ $item }}</span>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-theme-text whitespace-pre-wrap">
                    {{ method_exists($value, 'getDisplayValue') ? ($value->getDisplayValue() ?: '—') : ($value->value ?: '—') }}
                </p>
            @endif
        </div>
        @empty
        <div class="px-5 py-10 text-center">
            <i class="fas fa-inbox text-theme-muted text-3xl mb-2"></i>
            <p class="text-sm text-theme-muted">No field values recorded for this response.</p>
        </div>
        @endforelse
    </div>

    <!-- Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('doctor.responses.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Responses
        </a>
        @if($response->form)
        <a href="{{ route('doctor.forms.show', $response->form) }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-file-alt"></i> View Form
        </a>
        @endif
    </div>
</div>
@endsection
