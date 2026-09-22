@extends('layouts.admin')
@section('title', 'Edit Form')
@section('page-title', 'Edit Form')

@section('content')
<div class="max-w-2xl">
    <x-page-header title="Edit Form" subtitle="Update the form settings and metadata"
                   :back-url="route('admin.forms.show', $form)" />

    <div class="card p-6">
        <form method="POST" action="{{ route('admin.forms.update', $form) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-theme-text mb-1.5">
                    Form Title <span class="text-theme-danger">*</span>
                </label>
                <input type="text" name="title" value="{{ old('title', $form->title) }}"
                       class="input-base @error('title') border-red-400 @enderror"
                       required placeholder="e.g. Patient Registration Form">
                @error('title')
                    <p class="text-xs text-theme-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-theme-text mb-1.5">Description</label>
                <textarea name="description" rows="3"
                          class="input-base @error('description') border-red-400 @enderror"
                          placeholder="Brief description of this form's purpose">{{ old('description', $form->description) }}</textarea>
                @error('description')
                    <p class="text-xs text-theme-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-theme-text mb-1.5">Submit Button Text</label>
                <input type="text" name="submit_button_text"
                       value="{{ old('submit_button_text', $form->submit_button_text ?? 'Submit') }}"
                       class="input-base" placeholder="Submit">
            </div>

            <div>
                <label class="block text-sm font-medium text-theme-text mb-1.5">Confirmation Message</label>
                <textarea name="confirmation_message" rows="2"
                          class="input-base"
                          placeholder="Message shown after successful submission">{{ old('confirmation_message', $form->confirmation_message) }}</textarea>
            </div>

            <div class="flex items-center justify-between py-2 border-t border-theme">
                <div>
                    <p class="text-sm font-medium text-theme-text">Allow Multiple Responses</p>
                    <p class="text-xs text-theme-muted">Allow the same patient to submit more than once</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="allow_multiple_responses" value="0">
                    <input type="checkbox" name="allow_multiple_responses" value="1"
                           {{ old('allow_multiple_responses', $form->allow_multiple_responses) ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-10 h-6 bg-theme-surface-2 border border-theme rounded-full peer peer-checked:bg-theme-primary after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-4"></div>
                </label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Save Changes
                </button>
                <a href="{{ route('admin.forms.show', $form) }}" class="btn btn-secondary">Cancel</a>
                <a href="{{ route('admin.forms.builder', $form) }}" class="btn btn-secondary ml-auto">
                    <i class="fas fa-tools"></i> Open Builder
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
