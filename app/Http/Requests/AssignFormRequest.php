<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'doctor_ids'   => ['required', 'array'],
            'doctor_ids.*' => ['exists:users,id'],
        ];
    }
}
