@extends('layouts.admin')
@section('title', 'Doctors')
@section('page-title', 'Doctors')

@section('content')
<div class="space-y-4">
    <x-page-header title="Doctors" subtitle="Manage doctor accounts and their form access">
        <x-slot:actions>
            <a href="{{ route('admin.doctors.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Doctor
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="card p-4">
        <x-search-filter :action="route('admin.doctors.index')">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search by name, email..." class="input-base w-64">
            <select name="status" class="input-base w-36">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </x-search-filter>
    </div>

    <div class="card overflow-hidden">
        @if($doctors->isEmpty())
            <x-empty-state icon="fa-user-md" title="No doctors found"
                subtitle="Add your first doctor to get started.">
                <x-slot:action>
                    <a href="{{ route('admin.doctors.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add Doctor
                    </a>
                </x-slot:action>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Specialty</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($doctors as $doctor)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-theme-primary flex items-center justify-center flex-shrink-0">
                                        <span class="text-white text-xs font-semibold">
                                            {{ strtoupper(substr($doctor->user->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-theme-text">{{ $doctor->user->name }}</p>
                                        <p class="text-xs text-theme-muted">{{ $doctor->user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-theme-muted">{{ $doctor->specialty ?? '—' }}</td>
                            <td class="text-theme-muted">{{ $doctor->user->phone ?? '—' }}</td>
                            <td>
                                <x-badge :type="$doctor->user->is_active ? 'success' : 'danger'">
                                    {{ $doctor->user->is_active ? 'Active' : 'Inactive' }}
                                </x-badge>
                            </td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('admin.doctors.show', $doctor) }}"
                                       class="btn btn-ghost btn-sm" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.doctors.edit', $doctor) }}"
                                       class="btn btn-ghost btn-sm" title="Edit">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                    <form action="{{ route('admin.doctors.toggle-status', $doctor) }}" method="POST" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-ghost btn-sm"
                                                title="{{ $doctor->user->is_active ? 'Deactivate' : 'Activate' }}">
                                            @if($doctor->user->is_active)
                                                <i class="fas fa-ban text-yellow-500"></i>
                                            @else
                                                <i class="fas fa-check text-green-500"></i>
                                            @endif
                                        </button>
                                    </form>
                                    <button onclick="window.dispatchEvent(new CustomEvent('open-del-doc-{{ $doctor->id }}'))"
                                            class="btn btn-ghost btn-sm text-red-500" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <x-confirm-delete
                                        :id="'del-doc-' . $doctor->id"
                                        :action="route('admin.doctors.destroy', $doctor)"
                                        title="Delete Doctor"
                                        message="Are you sure you want to delete {{ $doctor->user->name }}? This will also remove all associated data and cannot be undone." />
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($doctors->hasPages())
            <div class="px-5 py-3 border-t border-theme">
                {{ $doctors->links() }}
            </div>
            @endif
        @endif
    </div>
</div>
@endsection
