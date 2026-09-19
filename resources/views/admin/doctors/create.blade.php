@extends('layouts.admin')
@section('title', 'Add Doctor')
@section('page-title', 'Add Doctor')

@section('content')
<div class="max-w-2xl">
    <x-page-header title="Add New Doctor" subtitle="Create a doctor account and profile" />

    <div class="card p-6">
        <form method="POST" action="{{ route('admin.doctors.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="input-base @error('name') border-red-400 @enderror"
                           required placeholder="Dr. John Smith">
                    @error('name')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="input-base @error('email') border-red-400 @enderror"
                           required placeholder="doctor@hospital.com">
                    @error('email')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password"
                           class="input-base @error('password') border-red-400 @enderror"
                           required placeholder="Min. 8 characters">
                    @error('password')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="input-base" placeholder="+91 98765 43210">
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">Specialty</label>
                    <input type="text" name="specialty" value="{{ old('specialty') }}"
                           class="input-base" placeholder="Cardiology, Neurology...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">Qualification</label>
                    <input type="text" name="qualification" value="{{ old('qualification') }}"
                           class="input-base" placeholder="MBBS, MD...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">License Number</label>
                    <input type="text" name="license_number" value="{{ old('license_number') }}"
                           class="input-base" placeholder="MCI-12345">
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">City</label>
                    <input type="text" name="city" value="{{ old('city') }}"
                           class="input-base" placeholder="Mumbai">
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">State</label>
                    <input type="text" name="state" value="{{ old('state') }}"
                           class="input-base" placeholder="Maharashtra">
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">Experience (years)</label>
                    <input type="number" name="experience_years" value="{{ old('experience_years') }}"
                           class="input-base" placeholder="5" min="0" max="60">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-theme-text mb-1.5">Bio</label>
                <textarea name="bio" rows="3" class="input-base"
                          placeholder="Brief professional bio...">{{ old('bio') }}</textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Create Doctor
                </button>
                <a href="{{ route('admin.doctors.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
