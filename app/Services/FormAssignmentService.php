<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormAssignment;
use Illuminate\Support\Facades\DB;

class FormAssignmentService
{
    /**
     * Sync doctor assignments for a form.
     * Doctors not in $doctorIds are deactivated; those in the list are upserted as active.
     */
    public function syncAssignments(Form $form, array $doctorIds): void
    {
        DB::transaction(function () use ($form, $doctorIds) {
            // Deactivate assignments for doctors no longer in the list
            $form->assignments()
                ->whereNotIn('doctor_id', $doctorIds)
                ->update(['is_active' => false]);

            foreach ($doctorIds as $doctorId) {
                FormAssignment::updateOrCreate(
                    ['form_id' => $form->id, 'doctor_id' => $doctorId],
                    [
                        'is_active'   => true,
                        'assigned_by' => auth()->id(),
                        'assigned_at' => now(),
                    ]
                );
            }
        });
    }

    /**
     * Check whether a doctor currently has an active assignment for a given form.
     */
    public function isDoctorAssigned(int $formId, int $doctorId): bool
    {
        return FormAssignment::where('form_id', $formId)
            ->where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->exists();
    }
}
