@extends('layouts.doctor')
@section('title', 'Response Detail')
@section('page-title', 'Response Detail')

@section('content')
<div class="response-detail-shell">
    <div class="response-detail-page">
        <div class="response-detail-header">
            <div class="response-detail-header-main">
                <div>
                    <p class="response-detail-label">Patient Response</p>
                    <h2>{{ $response->form?->title ?? 'Response Detail' }}</h2>
                </div>
                <div class="response-detail-actions">
                    <a href="{{ route('doctor.responses.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    @if($response->form)
                    <a href="{{ route('doctor.forms.responses.export.pdf', $response->form) }}" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="{{ route('doctor.forms.responses.export', $response->form) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="response-detail-body">
            <div class="response-meta-card">
                <div class="response-meta-grid">
                    <div class="response-meta-item">
                        <span>Patient Name</span>
                        <strong>{{ $response->submitted_by_name ?? 'Anonymous' }}</strong>
                    </div>
                    <div class="response-meta-item">
                        <span>Email</span>
                        <strong>{{ $response->submitted_by_email ?? '—' }}</strong>
                    </div>
                    <div class="response-meta-item">
                        <span>Submitted At</span>
                        <strong>{{ $response->submitted_at?->format('M d, Y H:i') ?? '—' }}</strong>
                    </div>
                </div>
                @if($response->form)
                <div class="response-meta-footer">
                    <div class="response-form-badge">
                        <i class="fas fa-file-lines"></i>
                        <span>{{ $response->form->title }}</span>
                    </div>
                    <a href="{{ route('doctor.forms.show', $response->form) }}" class="response-link">View Form</a>
                </div>
                @endif
            </div>

            <div class="response-values-card">
                <div class="response-values-header">
                    <h3><i class="fas fa-list-check"></i> Patient Answers</h3>
                </div>
                <div class="response-values-list">

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
            </div>
        </div>

        <div class="response-detail-footer">
            <a href="{{ route('doctor.responses.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Responses
            </a>
            @if($response->form)
            <a href="{{ route('doctor.forms.show', $response->form) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-file-lines"></i> View Form
            </a>
            @endif
        </div>
    </div>
</div>
@endsection
