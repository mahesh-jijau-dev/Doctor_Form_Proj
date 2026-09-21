@extends('layouts.admin')
@section('title', $form->title)
@section('page-title', 'Form Details')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="card p-5">
        <div class="flex flex-col sm:flex-row sm:items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-theme-primary flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-lines text-white text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <h1 class="text-xl font-bold text-theme-text">{{ $form->title }}</h1>
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
                    <p class="text-sm text-theme-muted">{{ $form->description }}</p>
                @endif
                <div class="flex flex-wrap gap-3 mt-2 text-xs text-theme-muted">
                    <span><i class="fas fa-user mr-1"></i>Created by {{ $form->creator?->name ?? 'System' }}</span>
                    <span><i class="fas fa-calendar mr-1"></i>{{ $form->created_at->format('M d, Y') }}</span>
                    @if($form->updated_at->ne($form->created_at))
                        <span><i class="fas fa-clock mr-1"></i>Updated {{ $form->updated_at->diffForHumans() }}</span>
                    @endif
                    @if($form->version)
                        <span><i class="fas fa-code-branch mr-1"></i>v{{ $form->version }}</span>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                <a href="{{ route('admin.forms.builder', $form) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-tools"></i> Builder
                </a>
                <a href="{{ route('admin.forms.assign', $form) }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-user-plus"></i> Assign
                </a>
                @if($form->status === 'published')
                    <a href="{{ route('forms.public.show', $form) }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                        <i class="fas fa-share-alt"></i> Public Link
                    </a>
                    <form action="{{ route('admin.forms.unpublish', $form) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="fas fa-eye-slash"></i> Unpublish
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.forms.publish', $form) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-globe"></i> Publish
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.responses.index', ['form_id' => $form->id]) }}"
                   class="btn btn-secondary btn-sm">
                    <i class="fas fa-inbox"></i> Responses
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <!-- Left: Fields -->
        <div class="xl:col-span-2 space-y-4">
            <!-- Stats bar -->
            <div class="grid grid-cols-3 gap-3">
                <div class="card p-4 text-center">
                    <p class="text-2xl font-bold text-theme-text">{{ $form->fields->count() }}</p>
                    <p class="text-xs text-theme-muted mt-0.5">Fields</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-2xl font-bold text-theme-text">{{ $form->responses_count ?? 0 }}</p>
                    <p class="text-xs text-theme-muted mt-0.5">Responses</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-2xl font-bold text-theme-text">{{ $form->assignments->count() }}</p>
                    <p class="text-xs text-theme-muted mt-0.5">Doctors</p>
                </div>
            </div>

            <!-- Fields list -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-theme flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-theme-text flex items-center gap-2">
                        <i class="fas fa-list text-theme-primary"></i> Form Fields
                    </h2>
                    <a href="{{ route('admin.forms.builder', $form) }}" class="text-xs text-theme-primary hover:underline">
                        Edit in Builder
                    </a>
                </div>

                @if($form->fields->isEmpty())
                    <div class="px-5 py-10 text-center">
                        <i class="fas fa-circle-plus text-theme-muted text-3xl mb-3"></i>
                        <p class="text-sm font-medium text-theme-text">No fields yet</p>
                        <p class="text-xs text-theme-muted mt-1">Open the builder to add fields to this form.</p>
                        <a href="{{ route('admin.forms.builder', $form) }}" class="btn btn-primary btn-sm mt-3">
                            <i class="fas fa-tools"></i> Open Builder
                        </a>
                    </div>
                @else
                    @php
                        $fieldIcons = [
                            'text'     => 'fa-font',
                            'textarea' => 'fa-align-left',
                            'email'    => 'fa-envelope',
                            'number'   => 'fa-hashtag',
                            'phone'    => 'fa-phone',
                            'date'     => 'fa-calendar',
                            'time'     => 'fa-clock',
                            'select'   => 'fa-chevron-down',
                            'radio'    => 'fa-dot-circle',
                            'checkbox' => 'fa-check-square',
                            'file'     => 'fa-file-arrow-up',
                            'signature'=> 'fa-pencil-alt-fancy',
                            'heading'  => 'fa-heading',
                            'paragraph'=> 'fa-paragraph',
                        ];
                        $grouped = $form->fields->groupBy('section_title');
                    @endphp

                    @foreach($grouped as $section => $fields)
                        @if($section && $form->is_multi_section)
                        <div class="px-5 py-2 bg-theme-surface-2 border-b border-theme">
                            <p class="text-xs font-semibold uppercase tracking-widest text-theme-muted">
                                {{ $section }}
                            </p>
                        </div>
                        @endif

                        @foreach($fields as $index => $field)
                        <div class="flex items-start gap-3 px-5 py-3.5 border-b border-theme last:border-0">
                            <div class="w-8 h-8 rounded-lg bg-theme-surface-2 flex items-center justify-center flex-shrink-0">
                                <i class="fas {{ $fieldIcons[$field->type] ?? 'fa-minus' }} text-theme-muted text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-medium text-theme-text">{{ $field->label }}</p>
                                    @if($field->is_required)
                                        <span class="text-xs text-red-500 font-medium">Required</span>
                                    @endif
                                    @if($field->placeholder)
                                        <span class="text-xs text-theme-muted">({{ $field->placeholder }})</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-theme-muted capitalize">{{ str_replace('_', ' ', $field->type) }}</span>
                                    @if($field->options && count($field->options))
                                        <span class="text-xs text-theme-muted">&bull; {{ count($field->options) }} options</span>
                                    @endif
                                </div>
                            </div>
                            <span class="text-xs text-theme-muted">{{ $loop->iteration }}</span>
                        </div>
                        @endforeach
                    @endforeach
                @endif
            </div>
        </div>

        <!-- Right: Assignments & Actions -->
        <div class="space-y-4">
            <!-- Quick actions -->
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-theme-text mb-3 flex items-center gap-2">
                    <i class="fas fa-bolt text-theme-primary"></i> Actions
                </h2>
                <div class="space-y-2">
                    <a href="{{ route('admin.forms.builder', $form) }}"
                       class="btn btn-primary w-full justify-start btn-sm">
                        <i class="fas fa-tools"></i> Edit in Builder
                    </a>
                    <a href="{{ route('admin.forms.assign', $form) }}"
                       class="btn btn-secondary w-full justify-start btn-sm">
                        <i class="fas fa-user-plus"></i> Assign Doctors
                    </a>
                    <a href="{{ route('admin.responses.index', ['form_id' => $form->id]) }}"
                       class="btn btn-secondary w-full justify-start btn-sm">
                        <i class="fas fa-inbox"></i> View Responses
                    </a>
                    <form action="{{ route('admin.forms.duplicate', $form) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-secondary w-full justify-start btn-sm">
                            <i class="fas fa-copy"></i> Duplicate Form
                        </button>
                    </form>
                    <button onclick="window.dispatchEvent(new CustomEvent('open-delete-form'))"
                            class="btn btn-danger w-full justify-start btn-sm">
                        <i class="fas fa-trash"></i> Delete Form
                    </button>
                </div>
            </div>

            <!-- Assigned Doctors -->
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-theme">
                    <h2 class="text-sm font-semibold text-theme-text flex items-center gap-2">
                        <i class="fas fa-user-doctor text-theme-primary"></i> Assigned Doctors
                    </h2>
                    <a href="{{ route('admin.forms.assign', $form) }}"
                       class="text-xs text-theme-primary hover:underline">Edit</a>
                </div>

                @if($form->assignments->isEmpty())
                    <div class="px-5 py-6 text-center">
                        <p class="text-sm text-theme-muted">No doctors assigned</p>
                        <a href="{{ route('admin.forms.assign', $form) }}"
                           class="text-xs text-theme-primary hover:underline mt-1 inline-block">Assign doctors</a>
                    </div>
                @else
                    <div class="divide-y divide-theme">
                        @foreach($form->assignments as $assignment)
                        <div class="flex items-center gap-3 px-4 py-3">
                            <div class="w-8 h-8 rounded-full bg-theme-primary flex items-center justify-center flex-shrink-0">
                                <span class="text-white text-xs font-semibold">
                                    {{ strtoupper(substr($assignment->doctor?->name ?? 'D', 0, 1)) }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-theme-text truncate">
                                    {{ $assignment->doctor?->name ?? '—' }}
                                </p>
                                <p class="text-xs text-theme-muted">
                                    {{ $assignment->doctor?->doctor?->specialty ?? '' }}
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<x-confirm-delete
    id="delete-form"
    :action="route('admin.forms.destroy', $form)"
    title="Delete Form"
    :message="'Are you sure you want to permanently delete \'' . $form->title . '\'? All fields and responses will be removed.'" />
@endsection
