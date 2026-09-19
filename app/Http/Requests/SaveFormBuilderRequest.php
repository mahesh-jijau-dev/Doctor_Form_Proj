<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveFormBuilderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title'                          => ['sometimes', 'string', 'max:255'],
            'description'                    => ['nullable', 'string'],
            'fields'                         => ['sometimes', 'array'],
            'fields.*.type'                  => ['required_with:fields', 'string'],
            'fields.*.label'                 => ['required_with:fields', 'string', 'max:255'],
            'fields.*.is_required'           => ['boolean'],
            'fields.*.options'               => ['nullable', 'array'],
            'fields.*.options.*.label'       => ['required_with:fields.*.options', 'string'],
            'sections'                       => ['sometimes', 'array'],
        ];
    }
}
