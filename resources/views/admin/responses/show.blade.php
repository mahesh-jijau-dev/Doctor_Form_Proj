@extends('layouts.admin')
@section('title', 'Response Detail')
@section('page-title', 'Response Detail')

@section('content')
<div class="response-detail-shell">
    <div class="response-detail-page">
        <div class="response-detail-header">
            <div class="response-detail-header-main">
                <div>
                    <p class="response-detail-label">Response</p>
                    <h2>{{ $response->form?->title ?? 'Response Detail' }}</h2>
                </div>
                <div class="response-detail-actions">
                    <a href="{{ route('admin.responses.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    @if($response->form)
                    <a href="{{ route('admin.forms.responses.export', $response->form_id) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-download"></i> Export
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="response-detail-body">
            <div class="response-meta-card">
                <div class="response-meta-grid">
                    <div class="response-meta-item">
                        <span>Submitted By</span>
                        <strong>{{ $response->submitted_by_name ?? 'Anonymous' }}</strong>
                    </div>
                    <div class="response-meta-item">
                        <span>Email</span>
                        <strong>{{ $response->submitted_by_email ?? '—' }}</strong>
                    </div>
                    <div class="response-meta-item">
                        <span>Doctor</span>
                        <strong>{{ $response->assignedDoctor?->name ?? '—' }}</strong>
                    </div>
                    <div class="response-meta-item">
                        <span>Submitted At</span>
                        <strong>{{ $response->submitted_at?->format('M d, Y H:i') ?? '—' }}</strong>
                    </div>
                </div>

                @if($response->form)
                <div class="response-meta-footer">
                    <div class="response-form-badge">
                        <i class="fas fa-file-alt"></i>
                        <span>{{ $response->form->title }}</span>
                        <x-badge :type="$response->form->status === 'published' ? 'success' : 'muted'">
                            {{ ucfirst($response->form->status) }}
                        </x-badge>
                    </div>
                    <a href="{{ route('admin.forms.show', $response->form) }}" class="response-link">View Form</a>
                </div>
                @endif
            </div>

            <div class="response-values-card">
                <div class="response-values-header">
                    <h3><i class="fas fa-list-check"></i> Response Data</h3>
                </div>

                <div class="response-values-list">
                    @forelse($response->values as $value)
                    <div class="response-value-item">
                        <p class="response-field-label">{{ $value->field_label }}</p>
                        @if($value->file_path)
                            <div class="response-file-box">
                                <div class="response-file-icon"><i class="fas fa-file"></i></div>
                                <div class="response-file-name">{{ $value->file_original_name }}</div>
                                <a href="{{ Storage::url($value->file_path) }}" target="_blank" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-external-link-alt"></i> View
                                </a>
                            </div>
                        @elseif(is_array($value->value) || (is_string($value->value) && str_starts_with(trim($value->value), '[')))
                            @php $items = is_array($value->value) ? $value->value : json_decode($value->value, true); @endphp
                            <div class="response-tag-list">
                                @foreach((array)$items as $item)
                                <span class="response-tag">{{ $item }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="response-value-text">
                                {{ method_exists($value, 'getDisplayValue') ? ($value->getDisplayValue() ?: '—') : ($value->value ?: '—') }}
                            </p>
                        @endif
                    </div>
                    @empty
                    <div class="response-empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No field values recorded for this response.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="response-detail-footer">
            <a href="{{ route('admin.responses.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Responses
            </a>
            <button onclick="window.dispatchEvent(new CustomEvent('open-delete-response'))" class="btn btn-danger btn-sm">
                <i class="fas fa-trash"></i> Delete Response
            </button>
        </div>
    </div>
</div>

<x-confirm-delete
    id="delete-response"
    :action="route('admin.responses.destroy', $response)"
    title="Delete Response"
    message="Are you sure you want to permanently delete this response? This action cannot be undone." />
@endsection
