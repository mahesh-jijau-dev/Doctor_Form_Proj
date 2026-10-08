<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_be_updated_without_changing_their_email(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $doctorUser = User::factory()->create([
            'role' => 'doctor',
            'is_active' => true,
        ]);
        $doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'specialty' => 'Cardiology',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.doctors.update', $doctor), [
            'name' => 'Updated Doctor',
            'email' => $doctorUser->email,
            'specialty' => 'Internal Medicine',
        ]);

        $response->assertRedirect(route('admin.doctors.show', $doctor));
        $this->assertDatabaseHas('users', [
            'id' => $doctorUser->id,
            'name' => 'Updated Doctor',
            'email' => $doctorUser->email,
        ]);
        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'specialty' => 'Internal Medicine',
        ]);
    }
}
