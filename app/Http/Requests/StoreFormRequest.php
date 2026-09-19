<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title'                => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'status'               => ['nullable', 'in:draft,published,unpublished,archived'],
            'is_multi_section'     => ['boolean'],
            'submit_button_text'   => ['nullable', 'string', 'max:100'],
            'confirmation_message' => ['nullable', 'string'],
        ];
    }
}
