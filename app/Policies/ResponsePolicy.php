<?php

namespace App\Policies;

use App\Models\FormResponse;
use App\Models\User;

class ResponsePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FormResponse $response): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Assignment grants access to all responses submitted for that form.
        return $response->form()
            ->whereHas('assignments', function ($query) use ($user) {
                $query->where('doctor_id', $user->id)
                    ->where('is_active', true);
            })
            ->exists();
    }

    public function export(User $user, FormResponse $response): bool
    {
        return $this->view($user, $response);
    }

    public function delete(User $user, FormResponse $response): bool
    {
        return $user->isAdmin();
    }
}
