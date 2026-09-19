<?php

namespace App\Policies;

use App\Models\Form;
use App\Models\User;

class FormPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Form $form): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $form->assignments()
            ->where('doctor_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Form $form): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Form $form): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Form $form): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Form $form): bool
    {
        return $user->isAdmin();
    }

    public function publish(User $user, Form $form): bool
    {
        return $user->isAdmin();
    }

    public function assign(User $user, Form $form): bool
    {
        return $user->isAdmin();
    }

    public function duplicate(User $user, Form $form): bool
    {
        return $user->isAdmin();
    }

    public function viewResponses(User $user, Form $form): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $form->assignments()
            ->where('doctor_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }
}
