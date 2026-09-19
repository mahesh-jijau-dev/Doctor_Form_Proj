@extends('layouts.admin')
@section('title', $doctor->user->name)
@section('page-title', 'Doctor Profile')

@section('content')
<div class="space-y-4">
    <!-- Header card -->
    <div class="card p-5">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <!-- Avatar -->
            <div class="w-16 h-16 rounded-2xl bg-theme-primary flex items-center justify-center flex-shrink-0">
                <span class="text-white text-2xl font-bold">
                    {{ strtoupper(substr($doctor->user->name, 0, 1)) }}
                </span>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <h1 class="text-xl font-bold text-theme-text">{{ $doctor->user->name }}</h1>
                    <x-badge :type="$doctor->user->is_active ? 'success' : 'danger'">
                        {{ $doctor->user->is_active ? 'Active' : 'Inactive' }}
                    </x-badge>
                </div>
                <p class="text-sm text-theme-muted">
                    {{ $doctor->specialty ?? 'General Practitioner' }}
                    @if($doctor->qualification)
                        &bull; {{ $doctor->qualification }}
                    @endif
                </p>
                <p class="text-xs text-theme-muted mt-1">Member since {{ $doctor->user->created_at->format('M Y') }}</p>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('admin.doctors.edit', $doctor) }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-pencil-alt"></i> Edit
                </a>
                <form action="{{ route('admin.doctors.toggle-status', $doctor) }}" method="POST" class="inline">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="btn btn-sm {{ $doctor->user->is_active ? 'btn-warning' : 'btn-success' }}">
                        @if($doctor->user->is_active)
                            <i class="fas fa-ban"></i> Deactivate
                        @else
                            <i class="fas fa-check"></i> Activate
                        @endif
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <!-- Left: Info -->
        <div class="xl:col-span-2 space-y-4">
            <!-- Contact & Details -->
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-theme-text mb-4 flex items-center gap-2">
                    <i class="fas fa-id-card text-theme-primary"></i> Doctor Information
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-theme-muted mb-0.5">Email Address</p>
                        <p class="text-sm font-medium text-theme-text">{{ $doctor->user->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-theme-muted mb-0.5">Phone</p>
                        <p class="text-sm font-medium text-theme-text">{{ $doctor->user->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-theme-muted mb-0.5">Specialty</p>
                        <p class="text-sm font-medium text-theme-text">{{ $doctor->specialty ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-theme-muted mb-0.5">Qualification</p>
                        <p class="text-sm font-medium text-theme-text">{{ $doctor->qualification ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-theme-muted mb-0.5">License Number</p>
                        <p class="text-sm font-medium text-theme-text">{{ $doctor->license_number ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-theme-muted mb-0.5">Experience</p>
                        <p class="text-sm font-medium text-theme-text">
                            {{ $doctor->experience_years ? $doctor->experience_years . ' years' : '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-theme-muted mb-0.5">City</p>
                        <p class="text-sm font-medium text-theme-text">{{ $doctor->city ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-theme-muted mb-0.5">State</p>
                        <p class="text-sm font-medium text-theme-text">{{ $doctor->state ?? '—' }}</p>
                    </div>
                </div>

                @if($doctor->bio)
                <div class="mt-4 pt-4 border-t border-theme">
                    <p class="text-xs text-theme-muted mb-1">Bio</p>
                    <p class="text-sm text-theme-text leading-relaxed">{{ $doctor->bio }}</p>
                </div>
                @endif
            </div>

            <!-- Assigned Forms -->
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-theme">
                    <h2 class="text-sm font-semibold text-theme-text flex items-center gap-2">
                        <i class="fas fa-file-alt text-theme-primary"></i> Assigned Forms
                    </h2>
                    <span class="badge badge-primary">{{ $doctor->assignments->count() }}</span>
                </div>

                @if($doctor->assignments->isEmpty())
                    <div class="px-5 py-8 text-center">
                        <i class="fas fa-file-alt text-theme-muted text-2xl mb-2"></i>
                        <p class="text-sm text-theme-muted">No forms assigned to this doctor</p>
                    </div>
                @else
                    <div class="divide-y divide-theme">
                        @foreach($doctor->assignments as $assignment)
                        <div class="flex items-center gap-3 px-5 py-3">
                            <div class="w-8 h-8 rounded-lg bg-theme-surface-2 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-file-alt text-theme-muted text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-theme-text truncate">
                                    {{ $assignment->form?->title ?? 'Deleted Form' }}
                                </p>
                                <p class="text-xs text-theme-muted">
                                    Assigned {{ $assignment->created_at->format('M d, Y') }}
                                </p>
                            </div>
                            @if($assignment->form)
                            <div class="flex items-center gap-1">
                                <x-badge :type="$assignment->form->status === 'published' ? 'success' : 'muted'">
                                    {{ ucfirst($assignment->form->status) }}
                                </x-badge>
                                <a href="{{ route('admin.forms.show', $assignment->form) }}"
                                   class="btn btn-ghost btn-sm" title="View Form">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Stats -->
        <div class="space-y-4">
            <!-- Stats -->
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-theme-text mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-bar text-theme-primary"></i> Statistics
                </h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-sm text-theme-muted">
                            <i class="fas fa-file-alt w-4 text-center"></i>
                            <span>Assigned Forms</span>
                        </div>
                        <span class="text-sm font-semibold text-theme-text">
                            {{ $doctor->assignments->count() }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-sm text-theme-muted">
                            <i class="fas fa-inbox w-4 text-center"></i>
                            <span>Total Responses</span>
                        </div>
                        <span class="text-sm font-semibold text-theme-text">
                            {{ number_format($totalResponses ?? 0) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-sm text-theme-muted">
                            <i class="fas fa-calendar-check w-4 text-center"></i>
                            <span>This Month</span>
                        </div>
                        <span class="text-sm font-semibold text-theme-text">
                            {{ $thisMonthResponses ?? 0 }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-theme-text mb-4 flex items-center gap-2">
                    <i class="fas fa-bolt text-theme-primary"></i> Quick Actions
                </h2>
                <div class="space-y-2">
                    <a href="{{ route('admin.responses.index', ['doctor_id' => $doctor->user->id]) }}"
                       class="btn btn-secondary w-full justify-start btn-sm">
                        <i class="fas fa-inbox"></i> View Responses
                    </a>
                    <a href="{{ route('admin.doctors.edit', $doctor) }}"
                       class="btn btn-secondary w-full justify-start btn-sm">
                        <i class="fas fa-pencil-alt"></i> Edit Profile
                    </a>
                    <button onclick="window.dispatchEvent(new CustomEvent('open-del-doctor'))"
                            class="btn btn-danger w-full justify-start btn-sm">
                        <i class="fas fa-trash"></i> Delete Doctor
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<x-confirm-delete
    id="del-doctor"
    :action="route('admin.doctors.destroy', $doctor)"
    title="Delete Doctor"
    :message="'Are you sure you want to permanently delete ' . $doctor->user->name . '? All associated data will be removed.'" />
@endsection
