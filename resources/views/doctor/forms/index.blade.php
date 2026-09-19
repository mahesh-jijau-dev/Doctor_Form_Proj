@extends('layouts.doctor')
@section('title', 'My Forms')
@section('page-title', 'My Forms')

@section('content')
<div class="space-y-4">
    <x-page-header title="My Assigned Forms" subtitle="Forms assigned to you by the administrator" />

    <div class="card p-4">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search forms..." class="input-base w-64">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-search"></i>
            </button>
            @if(request('search'))
            <a href="{{ route('doctor.forms.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-times"></i>
            </a>
            @endif
        </form>
    </div>

    @if($assignments->isEmpty())
    <div class="card">
        <x-empty-state icon="fa-file-alt" title="No forms assigned"
                       subtitle="The administrator has not assigned any forms to you yet." />
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($assignments as $assignment)
        @if($assignment->form)
        <div class="card p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl bg-theme-surface-2 flex items-center justify-center">
                    <i class="fas fa-file-lines text-theme-primary text-lg"></i>
                </div>
                <x-badge :type="$assignment->form->status === 'published' ? 'success' : 'muted'">
                    {{ ucfirst($assignment->form->status) }}
                </x-badge>
            </div>

            <div>
                <h3 class="font-semibold text-theme-text">{{ $assignment->form->title }}</h3>
                @if($assignment->form->description)
                <p class="text-sm text-theme-muted mt-1 line-clamp-2">{{ $assignment->form->description }}</p>
                @endif
            </div>

            <div class="flex flex-wrap gap-3 text-xs text-theme-muted">
                <span><i class="fas fa-inbox mr-1"></i>{{ $assignment->form->responses_count ?? 0 }} responses</span>
                <span><i class="fas fa-calendar mr-1"></i>Assigned {{ $assignment->created_at->format('M d, Y') }}</span>
            </div>

            <div class="flex flex-wrap gap-2 pt-1 border-t border-theme">
                <a href="{{ route('doctor.forms.show', $assignment->form) }}"
                   class="btn btn-sm btn-primary flex-1 justify-center">
                    <i class="fas fa-eye"></i> View Form
                </a>
                @if($assignment->form->status === 'published')
                <div x-data="{ copied: false, async copyLink() {
                    try { await navigator.clipboard.writeText('{{ route('forms.public.show', $assignment->form) }}'); this.copied = true; setTimeout(() => this.copied = false, 1800); }
                    catch (error) { window.prompt('Copy this public form link:', '{{ route('forms.public.show', $assignment->form) }}'); }
                }}">
                    <button type="button" @click="copyLink()" class="btn btn-sm btn-secondary" title="Copy public form link">
                        <i class="fas" :class="copied ? 'fa-check' : 'fa-link'"></i>
                    </button>
                </div>
                <a href="{{ route('forms.public.show', $assignment->form) }}" target="_blank" rel="noopener"
                   class="btn btn-sm btn-secondary" title="Open public form">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                </a>
                @endif
                <a href="{{ route('doctor.responses.index', ['form_id' => $assignment->form->id]) }}"
                   class="btn btn-sm btn-secondary" title="View Responses">
                    <i class="fas fa-inbox"></i>
                </a>
            </div>
        </div>
        @endif
        @endforeach
    </div>
    <div class="mt-4">{{ $assignments->links() }}</div>
    @endif
</div>
@endsection
