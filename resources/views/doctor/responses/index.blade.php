@extends('layouts.doctor')
@section('title', 'Responses')
@section('page-title', 'Responses')

@section('content')
<div class="space-y-4">
    <x-page-header title="Patient Responses" subtitle="Responses submitted through your assigned forms" />

    <div class="card p-4">
        <form method="GET" action="{{ route('doctor.responses.index') }}" class="flex flex-wrap gap-2 items-center">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search by patient name..." class="input-base w-52">
            <select name="form_id" class="input-base w-44">
                <option value="">All Forms</option>
                @foreach($forms as $f)
                <option value="{{ $f->id }}" {{ request('form_id') == $f->id ? 'selected' : '' }}>
                    {{ $f->title }}
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
            @if(request()->hasAny(['search', 'form_id', 'from', 'to']))
            <a href="{{ route('doctor.responses.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-times"></i> Clear
            </a>
            @endif
        </form>
    </div>

    <div class="card overflow-hidden">
        @if($responses->isEmpty())
        <x-empty-state icon="fa-inbox" title="No responses found"
                       subtitle="Responses from your assigned forms will appear here." />
        @else
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Form</th>
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
                        <td class="text-theme-muted text-sm whitespace-nowrap">
                            {{ $response->submitted_at?->format('M d, Y') }}<br>
                            <span class="text-xs">{{ $response->submitted_at?->format('H:i') }}</span>
                        </td>
                        <td>
                            <a href="{{ route('doctor.responses.show', $response) }}"
                               class="btn btn-ghost btn-sm" title="View Response">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-theme">
            {{ $responses->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
