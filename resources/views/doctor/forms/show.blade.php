@extends('layouts.doctor')
@section('title', $form->title)
@section('page-title', 'Form Preview')

@section('content')
<div class="max-w-3xl space-y-4">
    @php
        $publicFormUrl = route('forms.public.show', $form);
    @endphp
    <!-- Header -->
    <x-page-header :title="$form->title" subtitle="Read-only form structure preview">
        <x-slot:actions>
            <a href="{{ route('doctor.forms.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <a href="{{ route('doctor.responses.index', ['form_id' => $form->id]) }}"
               class="btn btn-primary btn-sm">
                <i class="fas fa-inbox"></i> Responses
            </a>
            @if($form->status === 'published')
            <div x-data="{ copied: false, async copyLink() {
                try { await navigator.clipboard.writeText('{{ $publicFormUrl }}'); this.copied = true; setTimeout(() => this.copied = false, 1800); }
                catch (error) { window.prompt('Copy this public form link:', '{{ $publicFormUrl }}'); }
            }}">
                <button type="button" @click="copyLink()" class="btn btn-secondary btn-sm" title="Copy public form link">
                    <i class="fas" :class="copied ? 'fa-check' : 'fa-link'"></i>
                    <span x-text="copied ? 'Copied' : 'Share form'"></span>
                </button>
            </div>
            <a href="{{ $publicFormUrl }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <!-- Form info card -->
    <div class="card p-5">
        <div class="flex flex-wrap items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-theme-surface-2 flex items-center justify-center">
                <i class="fas fa-file-alt text-theme-primary text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-lg font-bold text-theme-text">{{ $form->title }}</h1>
                    @php
                        $badgeType = match($form->status) {
                            'published'   => 'success',
                            'draft'       => 'warning',
                            'unpublished' => 'primary',
                            'archived'    => 'muted',
                            default       => 'muted'
                        };
                    @endphp
                    <x-badge :type="$badgeType">{{ ucfirst($form->status) }}</x-badge>
                </div>
                @if($form->description)
                <p class="text-sm text-theme-muted mt-0.5">{{ $form->description }}</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-3 border-t border-theme">
            <div>
                <p class="text-xs text-theme-muted">Total Fields</p>
                <p class="text-sm font-semibold text-theme-text">{{ $form->fields->count() }}</p>
            </div>
            <div>
                <p class="text-xs text-theme-muted">My Responses</p>
                <p class="text-sm font-semibold text-theme-text">{{ $myResponsesCount ?? 0 }}</p>
            </div>
            <div>
                <p class="text-xs text-theme-muted">Assigned On</p>
                <p class="text-sm font-semibold text-theme-text">{{ $form->created_at->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-theme-muted">Sections</p>
                <p class="text-sm font-semibold text-theme-text">
                    {{ $form->is_multi_section ? $form->fields->pluck('section_title')->filter()->unique()->count() . ' sections' : 'Single section' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Read-only field preview -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-theme flex items-center gap-2">
            <i class="fas fa-eye text-theme-muted text-sm"></i>
            <h2 class="text-sm font-semibold text-theme-text">Form Fields (Preview)</h2>
            <span class="ml-auto text-xs text-theme-muted">Read-only — editing not available here</span>
        </div>

        @if($form->fields->isEmpty())
            <div class="px-5 py-10 text-center">
                <i class="fas fa-inbox text-theme-muted text-3xl mb-3"></i>
                <p class="text-sm text-theme-muted">This form has no fields defined yet.</p>
            </div>
        @else
            @php
                $fieldIcons = [
                    'text'      => 'fa-font',
                    'textarea'  => 'fa-align-left',
                    'email'     => 'fa-envelope',
                    'number'    => 'fa-hashtag',
                    'phone'     => 'fa-phone',
                    'date'      => 'fa-calendar',
                    'time'      => 'fa-clock',
                    'select'    => 'fa-chevron-down',
                    'radio'     => 'fa-dot-circle',
                    'checkbox'  => 'fa-check-square',
                    'file'      => 'fa-file-arrow-up',
                    'signature' => 'fa-pencil-alt-fancy',
                    'heading'   => 'fa-heading',
                    'paragraph' => 'fa-paragraph',
                ];
                $grouped = $form->fields->groupBy('section_title');
            @endphp

            @foreach($grouped as $sectionTitle => $fields)
                @if($sectionTitle && $form->is_multi_section)
                <div class="px-5 py-3 bg-theme-surface-2 border-b border-theme flex items-center gap-2">
                    <i class="fas fa-layer-group text-theme-primary text-xs"></i>
                    <p class="text-xs font-semibold uppercase tracking-widest text-theme-muted">
                        {{ $sectionTitle }}
                    </p>
                    <span class="ml-auto text-xs text-theme-muted">{{ $fields->count() }} field(s)</span>
                </div>
                @endif

                @foreach($fields as $field)
                <div class="px-5 py-4 border-b border-theme last:border-0">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-theme-surface-2 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fas {{ $fieldIcons[$field->type] ?? 'fa-minus' }} text-theme-muted text-xs"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-sm font-medium text-theme-text">{{ $field->label }}</span>
                                @if($field->is_required)
                                <span class="text-xs font-medium text-red-500">Required</span>
                                @endif
                                <span class="text-xs px-1.5 py-0.5 rounded bg-theme-surface-2 text-theme-muted capitalize">
                                    {{ str_replace('_', ' ', $field->type) }}
                                </span>
                            </div>

                            @if($field->placeholder)
                            <p class="text-xs text-theme-muted mb-2">Placeholder: {{ $field->placeholder }}</p>
                            @endif

                            @if($field->help_text)
                            <p class="text-xs text-theme-muted italic mb-2">{{ $field->help_text }}</p>
                            @endif

                            {{-- Render a preview of the field --}}
                            @if(in_array($field->type, ['text', 'email', 'number', 'phone']))
                            <div class="mt-1">
                                <input type="{{ $field->type }}" disabled
                                       placeholder="{{ $field->placeholder ?? $field->label }}"
                                       class="input-base w-full sm:w-64 opacity-60 cursor-not-allowed">
                            </div>

                            @elseif($field->type === 'textarea')
                            <div class="mt-1">
                                <textarea disabled rows="2" placeholder="{{ $field->placeholder ?? $field->label }}"
                                          class="input-base w-full opacity-60 cursor-not-allowed resize-none"></textarea>
                            </div>

                            @elseif($field->type === 'date')
                            <div class="mt-1">
                                <input type="date" disabled class="input-base w-40 opacity-60 cursor-not-allowed">
                            </div>

                            @elseif($field->type === 'time')
                            <div class="mt-1">
                                <input type="time" disabled class="input-base w-36 opacity-60 cursor-not-allowed">
                            </div>

                            @elseif($field->type === 'select' && $field->options)
                            <div class="mt-1">
                                <select disabled class="input-base w-full sm:w-64 opacity-60 cursor-not-allowed">
                                    <option value="">{{ $field->placeholder ?? 'Select an option' }}</option>
                                    @foreach($field->options as $option)
                                    <option value="{{ is_array($option) ? ($option['value'] ?? $option['label'] ?? $option) : $option }}">
                                        {{ is_array($option) ? ($option['label'] ?? $option['value'] ?? $option) : $option }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            @elseif($field->type === 'radio' && $field->options)
                            <div class="mt-2 space-y-1.5">
                                @foreach($field->options as $option)
                                <label class="flex items-center gap-2 opacity-60 cursor-not-allowed">
                                    <input type="radio" disabled class="flex-shrink-0">
                                    <span class="text-sm text-theme-text">
                                        {{ is_array($option) ? ($option['label'] ?? $option['value'] ?? $option) : $option }}
                                    </span>
                                </label>
                                @endforeach
                            </div>

                            @elseif($field->type === 'checkbox' && $field->options)
                            <div class="mt-2 space-y-1.5">
                                @foreach($field->options as $option)
                                <label class="flex items-center gap-2 opacity-60 cursor-not-allowed">
                                    <input type="checkbox" disabled class="rounded flex-shrink-0">
                                    <span class="text-sm text-theme-text">
                                        {{ is_array($option) ? ($option['label'] ?? $option['value'] ?? $option) : $option }}
                                    </span>
                                </label>
                                @endforeach
                            </div>

                            @elseif($field->type === 'file')
                            <div class="mt-1">
                                <div class="flex items-center gap-2 p-2 rounded-lg border border-dashed border-theme bg-theme-surface-2 opacity-60 w-full sm:w-64">
                                    <i class="fas fa-file-arrow-up text-theme-muted"></i>
                                    <span class="text-xs text-theme-muted">File upload field</span>
                                </div>
                            </div>

                            @elseif($field->type === 'signature')
                            <div class="mt-1">
                                <div class="h-16 rounded-lg border border-dashed border-theme bg-theme-surface-2 opacity-60 flex items-center justify-center w-full sm:w-64">
                                    <span class="text-xs text-theme-muted">Signature pad</span>
                                </div>
                            </div>

                            @elseif($field->type === 'heading')
                            <div class="mt-1">
                                <p class="text-base font-semibold text-theme-text opacity-60">{{ $field->label }}</p>
                            </div>

                            @elseif($field->type === 'paragraph')
                            <div class="mt-1">
                                <p class="text-sm text-theme-muted opacity-60">{{ $field->placeholder ?? $field->label }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            @endforeach
        @endif
    </div>

    <!-- Bottom actions -->
    <div class="flex items-center justify-between">
        <a href="{{ route('doctor.forms.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Forms
        </a>
        <a href="{{ route('doctor.responses.index', ['form_id' => $form->id]) }}" class="btn btn-primary btn-sm">
            <i class="fas fa-inbox"></i> View Responses
        </a>
    </div>
</div>
@endsection
