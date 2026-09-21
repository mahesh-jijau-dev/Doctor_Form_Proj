@extends('layouts.admin')
@section('title', 'Create Form')
@section('page-title', 'Create Form')

@section('content')
    <div class="max-w-6xl mx-auto w-full">
        <x-page-header title="Create New Form" subtitle="Set up your form, then use the builder to add fields." />

        <form method="POST" action="{{ route('admin.forms.store') }}" x-data="{ multiSection: false }">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

                {{-- ============ LEFT: form details ============ --}}
                <div class="lg:col-span-2 card p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-theme-text mb-1">Form Details</h2>
                    <p class="text-xs text-theme-muted mb-5">Basic information that identifies this form.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-5">

                        {{-- Title --}}
                        <div class="md:col-span-2">
                            <label for="title" class="block text-sm font-medium text-theme-text mb-1.5">
                                Form Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="title" name="title" value="{{ old('title') }}"
                                class="input-base @error('title') border-red-400 @enderror" required
                                placeholder="e.g. Patient Intake Form, Pre-surgery Questionnaire">
                            @error('title')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Category --}}
                        <div>
                            <label for="category" class="block text-sm font-medium text-theme-text mb-1.5">Category</label>
                            <select id="category" name="category"
                                class="input-base @error('category') border-red-400 @enderror">
                                <option value="">Select a category</option>
                                <option value="intake" {{ old('category') === 'intake' ? 'selected' : '' }}>Patient Intake
                                </option>
                                <option value="consent" {{ old('category') === 'consent' ? 'selected' : '' }}>Consent Form
                                </option>
                                <option value="followup" {{ old('category') === 'followup' ? 'selected' : '' }}>Follow-up
                                </option>
                                <option value="assessment" {{ old('category') === 'assessment' ? 'selected' : '' }}>
                                    Assessment</option>
                                <option value="survey" {{ old('category') === 'survey' ? 'selected' : '' }}>Survey</option>
                                <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('category')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-theme-muted mt-1">Used to group forms on the All Forms page.</p>
                        </div>

                        {{-- Status (kept beside category so the row is balanced) --}}
                        <div>
                            <label for="status" class="block text-sm font-medium text-theme-text mb-1.5">Initial
                                Status</label>
                            <select id="status" name="status" class="input-base">
                                <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft —
                                    not visible to patients</option>
                                <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published —
                                    accepting responses</option>
                            </select>
                            <p class="text-xs text-theme-muted mt-1">You can change this at any time.</p>
                        </div>

                        {{-- Description --}}
                        <div class="md:col-span-2">
                            <label for="description"
                                class="block text-sm font-medium text-theme-text mb-1.5">Description</label>
                            <textarea id="description" name="description" rows="4" class="input-base"
                                placeholder="Briefly describe the purpose of this form">{{ old('description') }}</textarea>
                            <p class="text-xs text-theme-muted mt-1">This is shown to patients when they fill out the form.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- ============ RIGHT: options + actions ============ --}}
                <div class="lg:col-span-1 flex flex-col gap-5">

                    <div class="card p-5 sm:p-6">
                        <h2 class="text-base font-semibold text-theme-text mb-1">Options</h2>
                        <p class="text-xs text-theme-muted mb-4">How this form behaves for patients.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4">

                            {{-- Multi-section toggle --}}
                            <div class="flex items-start gap-3 p-4 rounded-lg bg-theme-surface-2">
                                <label class="relative inline-flex items-center cursor-pointer mt-0.5 shrink-0">
                                    <input type="checkbox" name="is_multi_section" value="1" class="sr-only peer"
                                        x-model="multiSection" {{ old('is_multi_section') ? 'checked' : '' }}>
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                                            dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white
                                            after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white
                                            after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5
                                            after:transition-all dark:border-gray-600 peer-checked:bg-theme-primary">
                                    </div>
                                </label>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-theme-text">Multi-section Form</p>
                                    <p class="text-xs text-theme-muted mt-0.5">
                                        Organise fields into several named sections with navigation steps.
                                    </p>
                                    <p class="text-xs text-theme-muted mt-1.5" x-show="multiSection" x-cloak>
                                        <i class="fas fa-info-circle text-theme-primary mr-1"></i>
                                        You can define the sections in the form builder after the form is created.
                                    </p>
                                </div>
                            </div>

                            {{-- Allow anonymous submissions --}}
                            <div class="flex items-start gap-3 p-4 rounded-lg bg-theme-surface-2">
                                <label class="relative inline-flex items-center cursor-pointer mt-0.5 shrink-0">
                                    <input type="checkbox" name="allow_anonymous" value="1" class="sr-only peer"
                                        {{ old('allow_anonymous', true) ? 'checked' : '' }}>
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                                            dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white
                                            after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white
                                            after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5
                                            after:transition-all dark:border-gray-600 peer-checked:bg-theme-primary">
                                    </div>
                                </label>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-theme-text">Allow Anonymous Submissions</p>
                                    <p class="text-xs text-theme-muted mt-0.5">
                                        Let patients submit the form without giving their name or email address.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="card p-5 sm:p-6">
                        <div class="flex flex-col gap-2.5">
                            <button type="submit" name="action" value="builder" class="btn btn-primary w-full">
                                <i class="fas fa-tools"></i> Create &amp; Open Builder
                            </button>
                            <button type="submit" name="action" value="save" class="btn btn-secondary w-full">
                                <i class="fas fa-save"></i> Save as Draft
                            </button>
                            <a href="{{ route('admin.forms.index') }}" class="btn btn-ghost w-full">Cancel</a>
                        </div>
                        <p class="text-xs text-theme-muted mt-3 text-center">
                            Fields are added in the next step.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
