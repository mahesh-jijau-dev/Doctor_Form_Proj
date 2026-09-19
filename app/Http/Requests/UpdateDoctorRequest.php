<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $doctorId = $this->route('doctor');

        // Retrieve the associated user ID so we can exclude it from the unique check
        $userId = Doctor::find($doctorId)?->user_id;

        return [
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', "unique:users,email,{$userId}"],
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
