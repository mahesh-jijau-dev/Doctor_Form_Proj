@extends('layouts.admin')
@section('title', 'Create Form')
@section('page-title', 'Create Form')

@section('content')
<div class="max-w-2xl">
    <x-page-header title="Create New Form" subtitle="Set up a form then use the builder to add fields" />

    <div class="card p-6">
        <form method="POST" action="{{ route('admin.forms.store') }}" class="space-y-5"
              x-data="{ multiSection: false }">
            @csrf

            <div>
                <label class="block text-sm font-medium text-theme-text mb-1.5">
                    Form Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" value="{{ old('title') }}"
                       class="input-base @error('title') border-red-400 @enderror"
                       required placeholder="e.g. Patient Intake Form, Pre-surgery Questionnaire...">
                @error('title')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-theme-text mb-1.5">Description</label>
                <textarea name="description" rows="3" class="input-base"
                          placeholder="Briefly describe the purpose of this form...">{{ old('description') }}</textarea>
                <p class="text-xs text-theme-muted mt-1">This will be shown to patients when filling the form.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-theme-text mb-1.5">Category</label>
                <select name="category" class="input-base">
                    <option value="">Select a category...</option>
                    <option value="intake" {{ old('category') === 'intake' ? 'selected' : '' }}>Patient Intake</option>
                    <option value="consent" {{ old('category') === 'consent' ? 'selected' : '' }}>Consent Form</option>
                    <option value="followup" {{ old('category') === 'followup' ? 'selected' : '' }}>Follow-up</option>
                    <option value="assessment" {{ old('category') === 'assessment' ? 'selected' : '' }}>Assessment</option>
                    <option value="survey" {{ old('category') === 'survey' ? 'selected' : '' }}>Survey</option>
                    <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <!-- Multi-section toggle -->
            <div class="flex items-start gap-3 p-4 rounded-lg bg-theme-surface-2">
                <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                    <input type="checkbox" name="is_multi_section" value="1" class="sr-only peer"
                           x-model="multiSection" {{ old('is_multi_section') ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                                dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white
                                after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white
                                after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5
                                after:transition-all dark:border-gray-600 peer-checked:bg-theme-primary"></div>
                </label>
                <div>
                    <p class="text-sm font-medium text-theme-text">Multi-section Form</p>
                    <p class="text-xs text-theme-muted mt-0.5">
                        Organize fields into multiple named sections with navigation steps.
                    </p>
                    <p class="text-xs text-theme-muted mt-1" x-show="multiSection" x-cloak>
                        <i class="fas fa-info-circle text-theme-primary mr-1"></i>
                        You can define sections in the Form Builder after creation.
                    </p>
                </div>
            </div>

            <!-- Allow anonymous submissions -->
            <div class="flex items-start gap-3 p-4 rounded-lg bg-theme-surface-2">
                <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                    <input type="checkbox" name="allow_anonymous" value="1" class="sr-only peer"
                           {{ old('allow_anonymous', true) ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                                dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white
                                after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white
                                after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5
                                after:transition-all dark:border-gray-600 peer-checked:bg-theme-primary"></div>
                </label>
                <div>
                    <p class="text-sm font-medium text-theme-text">Allow Anonymous Submissions</p>
                    <p class="text-xs text-theme-muted mt-0.5">
                        Allow patients to submit without providing their name or email.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" name="action" value="builder" class="btn btn-primary">
                    <i class="fas fa-tools"></i> Create &amp; Open Builder
                </button>
                <button type="submit" name="action" value="save" class="btn btn-secondary">
                    <i class="fas fa-save"></i> Save as Draft
                </button>
                <a href="{{ route('admin.forms.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
