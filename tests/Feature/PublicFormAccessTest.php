<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFormAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_form_is_accessible_publicly(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $form = Form::create([
            'title' => 'Public Consultation Form',
            'description' => 'Customer intake form',
            'status' => 'published',
            'created_by' => $user->id,
            'submit_button_text' => 'Submit',
            'allow_multiple_responses' => true,
        ]);

        $response = $this->get(route('forms.public.show', $form));

        $response->assertOk();
    }

    public function test_draft_form_is_not_accessible_publicly(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $form = Form::create([
            'title' => 'Private Draft Form',
            'description' => 'Should not be public',
            'status' => 'draft',
            'created_by' => $user->id,
            'submit_button_text' => 'Submit',
            'allow_multiple_responses' => true,
        ]);

        $response = $this->get(route('forms.public.show', $form));

        $response->assertNotFound();
    }
}
