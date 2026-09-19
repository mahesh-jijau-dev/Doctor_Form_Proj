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

        // Doctor can only view if the response is assigned to them
        return $response->assigned_doctor_id === $user->id;
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
