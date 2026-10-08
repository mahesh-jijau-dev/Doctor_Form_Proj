<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $doctor = $this->route('doctor');

        $userId = $doctor instanceof Doctor
            ? $doctor->user_id
            : Doctor::query()->findOrFail($doctor)->user_id;

        return [
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone'          => ['nullable', 'string', 'max:20'],
            'password'       => ['nullable', 'string', 'min:8'],
            'specialty'      => ['nullable', 'string', 'max:255'],
            'qualification'  => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'bio'            => ['nullable', 'string'],
            'address'        => ['nullable', 'string'],
            'city'           => ['nullable', 'string', 'max:100'],
            'state'          => ['nullable', 'string', 'max:100'],
            'country'        => ['nullable', 'string', 'max:100'],
        ];
    }
}
