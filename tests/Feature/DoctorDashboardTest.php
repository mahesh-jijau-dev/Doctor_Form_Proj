<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Form;
use App\Models\FormAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_ignores_assignments_to_soft_deleted_forms(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $doctorUser = User::factory()->create([
            'role' => 'doctor',
            'is_active' => true,
        ]);
        Doctor::create(['user_id' => $doctorUser->id]);
        $form = Form::create([
            'title' => 'Archived form',
            'status' => 'draft',
            'created_by' => $admin->id,
            'submit_button_text' => 'Submit',
            'allow_multiple_responses' => true,
        ]);
        FormAssignment::create([
            'form_id' => $form->id,
            'doctor_id' => $doctorUser->id,
            'assigned_by' => $admin->id,
            'is_active' => true,
        ]);
        $form->delete();

        $response = $this->actingAs($doctorUser)->get(route('doctor.dashboard'));

        $response->assertOk()
            ->assertSee('No forms assigned yet.')
            ->assertDontSee('Archived form');
    }
}
