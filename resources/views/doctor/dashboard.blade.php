@extends('layouts.doctor')
@section('title', 'Dashboard')
@section('page-title', 'My Dashboard')

@section('content')
<div class="space-y-6">
    <div class="dashboard-hero dashboard-hero-pattern rounded-xl px-6 py-5 text-white shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm text-white/70">Good morning, {{ auth()->user()->name }}.</p>
                <h2 class="text-2xl font-bold tracking-tight mt-1">Your patient care overview.</h2>
                <p class="text-sm text-white/75 mt-2">Review assigned forms and keep up with incoming responses.</p>
            </div>
            <div class="hidden sm:flex w-12 h-12 rounded-xl bg-white/15 items-center justify-center">
                <i class="fas fa-heart-pulse text-xl" aria-hidden="true"></i>
            </div>
        </div>
    </div>

    <!-- Stats grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-stat-card title="Assigned Forms" :value="$stats['assigned_forms']" icon="fa-file-lines" color="primary" />
        <x-stat-card title="Total Responses" :value="number_format($stats['total_responses'])" icon="fa-inbox" color="secondary" />
        <x-stat-card title="Today" :value="$stats['today_responses']" icon="fa-calendar-check" color="success" />
        <x-stat-card title="This Week" :value="$stats['this_week_responses']" icon="fa-calendar-days" color="warning" />
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <!-- Assigned Forms -->
        <div class="card">
            <div class="flex items-center justify-between px-5 py-4 border-b border-theme">
                <div>
                    <h3 class="text-sm font-semibold text-theme-text">My Forms</h3>
                    <p class="text-xs text-theme-muted mt-1">Forms assigned by your administrator</p>
                </div>
                <a href="{{ route('doctor.forms.index') }}" class="text-xs text-theme-primary hover:underline">View all</a>
            </div>
            <div class="divide-y divide-theme">
                @forelse($assignedForms as $assignment)
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-9 h-9 rounded-lg bg-theme-surface-2 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-file-lines text-theme-primary text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-theme-text truncate">{{ $assignment->form?->title }}</p>
                        <p class="text-xs text-theme-muted">{{ $assignment->form?->responses_count ?? 0 }} responses</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a href="{{ route('doctor.forms.show', $assignment->form) }}" class="btn btn-sm btn-secondary">View</a>
                        @if($assignment->form?->status === 'published')
                        <a href="{{ route('forms.public.show', $assignment->form) }}" target="_blank" rel="noopener"
                           class="btn btn-sm btn-primary" title="Open public form">
                            <i class="fas fa-arrow-up-right-from-square"></i>
                        </a>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center">
                    <i class="fas fa-file-lines text-theme-muted text-2xl mb-2"></i>
                    <p class="text-sm text-theme-muted">No forms assigned yet.</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Responses -->
        <div class="card">
            <div class="flex items-center justify-between px-5 py-4 border-b border-theme">
                <h3 class="text-sm font-semibold text-theme-text">Recent Responses</h3>
                <a href="{{ route('doctor.responses.index') }}" class="text-xs text-theme-primary hover:underline">View all</a>
            </div>
            <div class="divide-y divide-theme">
                @forelse($recentResponses as $response)
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-8 h-8 rounded-full bg-theme-surface-2 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user text-theme-primary text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-theme-text">{{ $response->submitted_by_name ?? 'Anonymous' }}</p>
                        <p class="text-xs text-theme-muted truncate">{{ $response->form?->title }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xs text-theme-muted">{{ $response->submitted_at?->diffForHumans() }}</p>
                        <a href="{{ route('doctor.responses.show', $response) }}"
                           class="text-xs text-theme-primary hover:underline">View</a>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center">
                    <i class="fas fa-inbox text-theme-muted text-2xl mb-2"></i>
                    <p class="text-sm text-theme-muted">No responses yet.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
