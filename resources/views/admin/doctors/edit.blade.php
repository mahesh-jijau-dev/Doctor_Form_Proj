@extends('layouts.admin')
@section('title', 'Edit Doctor')
@section('page-title', 'Edit Doctor')

@section('content')
<div class="max-w-2xl">
    <x-page-header
        :title="'Edit: ' . $doctor->user->name"
        subtitle="Update doctor account and profile information"
        :back-url="route('admin.doctors.show', $doctor)" />

    <div class="card p-6">
        <form method="POST" action="{{ route('admin.doctors.update', $doctor) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $doctor->user->name) }}"
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
                    <input type="email" name="email" value="{{ old('email', $doctor->user->email) }}"
                           class="input-base @error('email') border-red-400 @enderror"
                           required placeholder="doctor@hospital.com">
                    @error('email')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">
                        New Password
                        <span class="text-theme-muted font-normal text-xs ml-1">(leave blank to keep current)</span>
                    </label>
                    <div class="relative" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" name="password"
                               class="input-base pr-10 @error('password') border-red-400 @enderror"
                               placeholder="Leave blank to keep current">
                        <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-theme-muted hover:text-theme-text">
                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $doctor->user->phone) }}"
                           class="input-base" placeholder="+91 98765 43210">
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">Specialty</label>
                    <input type="text" name="specialty" value="{{ old('specialty', $doctor->specialty) }}"
                           class="input-base" placeholder="Cardiology, Neurology...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">Qualification</label>
                    <input type="text" name="qualification" value="{{ old('qualification', $doctor->qualification) }}"
                           class="input-base" placeholder="MBBS, MD...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">License Number</label>
                    <input type="text" name="license_number" value="{{ old('license_number', $doctor->license_number) }}"
                           class="input-base" placeholder="MCI-12345">
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">City</label>
                    <input type="text" name="city" value="{{ old('city', $doctor->city) }}"
                           class="input-base" placeholder="Mumbai">
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">State</label>
                    <input type="text" name="state" value="{{ old('state', $doctor->state) }}"
                           class="input-base" placeholder="Maharashtra">
                </div>

                <div>
                    <label class="block text-sm font-medium text-theme-text mb-1.5">Experience (years)</label>
                    <input type="number" name="experience_years"
                           value="{{ old('experience_years', $doctor->experience_years) }}"
                           class="input-base" placeholder="5" min="0" max="60">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-theme-text mb-1.5">Bio</label>
                <textarea name="bio" rows="3" class="input-base"
                          placeholder="Brief professional bio...">{{ old('bio', $doctor->bio) }}</textarea>
            </div>

            <!-- Active status toggle -->
            <div class="flex items-center gap-3 p-3 rounded-lg bg-theme-surface-2">
                <div class="flex-1">
                    <p class="text-sm font-medium text-theme-text">Account Status</p>
                    <p class="text-xs text-theme-muted">Enable or disable this doctor's access to the system</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                           {{ old('is_active', $doctor->user->is_active) ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                                dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white
                                after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white
                                after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5
                                after:transition-all dark:border-gray-600 peer-checked:bg-theme-primary"></div>
                    <span class="ml-2 text-sm text-theme-muted">Active</span>
                </label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="{{ route('admin.doctors.show', $doctor) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
