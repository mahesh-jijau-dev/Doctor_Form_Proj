@extends('layouts.admin')
@section('title', 'Responses')
@section('page-title', 'Responses')

@section('content')
<div class="space-y-4">
    <x-page-header title="All Responses" subtitle="View and manage all form submissions" />

    <div class="card p-4">
        <form method="GET" action="{{ route('admin.responses.index') }}" class="flex flex-wrap gap-2 items-center">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search by name, email..." class="input-base w-52">
            <select name="form_id" class="input-base w-44">
                <option value="">All Forms</option>
                @foreach($forms as $f)
                <option value="{{ $f->id }}" {{ request('form_id') == $f->id ? 'selected' : '' }}>
                    {{ $f->title }}
                </option>
                @endforeach
            </select>
            <select name="doctor_id" class="input-base w-44">
                <option value="">All Doctors</option>
                @foreach($doctors as $d)
                <option value="{{ $d->id }}" {{ request('doctor_id') == $d->id ? 'selected' : '' }}>
                    {{ $d->name }}
                </option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="input-base w-36" title="From date">
            <input type="date" name="to" value="{{ request('to') }}"
                   class="input-base w-36" title="To date">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-filter"></i> Filter
            </button>
            @if(request()->hasAny(['search','form_id','doctor_id','from','to']))
            <a href="{{ route('admin.responses.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-times"></i> Clear
            </a>
            @endif
        </form>
        <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-theme">
            <span class="text-xs text-theme-muted mr-auto">Export filtered responses</span>
            <a href="{{ route('admin.responses.export.filtered.pdf') . '?' . http_build_query(request()->only(['search', 'form_id', 'doctor_id', 'from', 'to'])) }}" class="btn btn-danger btn-sm">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <a href="{{ route('admin.responses.export.filtered') . '?' . http_build_query(request()->only(['search', 'form_id', 'doctor_id', 'from', 'to'])) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Excel (CSV)
            </a>
        </div>
    </div>

    <div class="card overflow-hidden">
        @if($responses->isEmpty())
        <x-empty-state icon="fa-inbox" title="No responses found"
                       subtitle="Responses will appear here once forms are submitted." />
        @else
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Form</th>
                        <th>Doctor</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($responses as $response)
                    <tr>
                        <td class="text-theme-muted text-xs">{{ $response->id }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-theme-surface-2 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user text-theme-muted text-xs"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-theme-text text-sm">
                                        {{ $response->submitted_by_name ?? 'Anonymous' }}
                                    </p>
                                    @if($response->submitted_by_email)
                                    <p class="text-xs text-theme-muted">{{ $response->submitted_by_email }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <p class="font-medium text-theme-text text-sm">{{ $response->form?->title ?? '—' }}</p>
                        </td>
                        <td class="text-theme-muted text-sm">{{ $response->assignedDoctor?->name ?? '—' }}</td>
                        <td class="text-theme-muted text-sm whitespace-nowrap">
                            {{ $response->submitted_at?->format('M d, Y') }}<br>
                            <span class="text-xs">{{ $response->submitted_at?->format('H:i') }}</span>
                        </td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('admin.responses.show', $response) }}"
                                   class="btn btn-ghost btn-sm" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button onclick="window.dispatchEvent(new CustomEvent('open-del-{{ $response->id }}'))"
                                        class="btn btn-ghost btn-sm text-red-500" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <x-confirm-delete
                                    :id="'del-' . $response->id"
                                    :action="route('admin.responses.destroy', $response)"
                                    title="Delete Response"
                                    message="Delete this response permanently? This action cannot be undone." />
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($responses->hasPages())
        <div class="px-5 py-3 border-t border-theme">
            <p class="text-xs text-theme-muted mb-2">
                Showing {{ $responses->firstItem() }} to {{ $responses->lastItem() }} of {{ $responses->total() }} responses
            </p>
            {{ $responses->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
