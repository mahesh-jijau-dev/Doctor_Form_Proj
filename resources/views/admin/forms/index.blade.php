@extends('layouts.admin')
@section('title', 'Forms')
@section('page-title', 'Forms')

@section('content')
<div class="space-y-4">
    <x-page-header title="Forms" subtitle="Create and manage medical forms">
        <x-slot:actions>
            <a href="{{ route('admin.forms.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Create Form
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="card p-4">
        <x-search-filter :action="route('admin.forms.index')">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search forms..." class="input-base w-64">
            <select name="status" class="input-base w-36">
                <option value="">All Status</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                <option value="unpublished" {{ request('status') === 'unpublished' ? 'selected' : '' }}>Unpublished</option>
                <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
            </select>
        </x-search-filter>
    </div>

    @if($forms->isEmpty())
    <div class="card">
            <x-empty-state icon="fa-file-lines" title="No forms yet" subtitle="Create your first form to get started.">
            <x-slot:action>
                <a href="{{ route('admin.forms.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Create Form
                </a>
            </x-slot:action>
        </x-empty-state>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($forms as $form)
        <div class="card p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-theme-text truncate">{{ $form->title }}</h3>
                    <p class="text-xs text-theme-muted mt-0.5">Created {{ $form->created_at->format('M d, Y') }}</p>
                </div>
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
            <p class="text-sm text-theme-muted line-clamp-2">{{ $form->description }}</p>
            @endif

            <div class="flex items-center gap-4 text-xs text-theme-muted">
                <span><i class="fas fa-inbox mr-1"></i>{{ $form->responses_count ?? 0 }} responses</span>
                <span><i class="fas fa-user-doctor mr-1"></i>{{ $form->assignments_count ?? 0 }} doctors</span>
            </div>

            <div class="flex flex-wrap gap-1.5 pt-1 border-t border-theme">
                <a href="{{ route('admin.forms.builder', $form) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-tools"></i> Builder
                </a>
                <a href="{{ route('admin.forms.show', $form) }}" class="btn btn-sm btn-secondary" title="View">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="{{ route('admin.forms.assign', $form) }}" class="btn btn-sm btn-secondary" title="Assign Doctors">
                    <i class="fas fa-user-plus"></i>
                </a>

                @if($form->status === 'published')
                <form action="{{ route('admin.forms.unpublish', $form) }}" method="POST" class="inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-warning" title="Unpublish">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </form>
                @else
                <form action="{{ route('admin.forms.publish', $form) }}" method="POST" class="inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-success" title="Publish">
                        <i class="fas fa-globe"></i>
                    </button>
                </form>
                @endif

                <form action="{{ route('admin.forms.duplicate', $form) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-secondary" title="Duplicate">
                        <i class="fas fa-copy"></i>
                    </button>
                </form>

                <button onclick="window.dispatchEvent(new CustomEvent('open-delete-{{ $form->id }}'))"
                        class="btn btn-sm btn-danger" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
                <x-confirm-delete
                    :id="'delete-' . $form->id"
                    :action="route('admin.forms.destroy', $form)"
                    title="Delete Form"
                    :message="'Are you sure you want to delete \'' . $form->title . '\'? This cannot be undone.'" />
            </div>
        </div>
        @endforeach
    </div>
    @if($forms->hasPages())
    <div class="mt-4">
        {{ $forms->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
