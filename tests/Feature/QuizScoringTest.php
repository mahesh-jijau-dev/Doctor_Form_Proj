<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_answers_are_scored_and_score_is_shown_when_enabled(): void
    {
        [$form, $fields] = $this->createQuizForm(showScore: true);

        $response = $this->post(route('forms.public.submit', $form), [
            'field_' . $fields['choice']->id => 'yes',
            'field_' . $fields['checkbox']->id => ['red'],
        ]);

        $response->assertOk()->assertSee('Your Score')->assertSee('2')
            ->assertSee('5 points')->assertSee('% correct');

        $this->assertDatabaseHas('form_responses', [
            'form_id' => $form->id,
            'quiz_score' => 2,
            'quiz_max_score' => 5,
        ]);
    }

    public function test_score_is_stored_but_hidden_when_form_setting_is_disabled(): void
    {
        [$form, $fields] = $this->createQuizForm(showScore: false);

        $response = $this->post(route('forms.public.submit', $form), [
            'field_' . $fields['choice']->id => 'yes',
            'field_' . $fields['checkbox']->id => ['red', 'blue'],
        ]);

        $response->assertOk()->assertDontSee('Your Score');

        $this->assertDatabaseHas('form_responses', [
            'form_id' => $form->id,
            'quiz_score' => 5,
            'quiz_max_score' => 5,
        ]);
    }

    public function test_builder_saves_correct_answers_points_and_score_visibility(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $form = $this->createForm(showScore: false);
        $field = FormField::create([
            'form_id' => $form->id,
            'type' => 'multiple_choice',
            'label' => 'Question',
            'order_index' => 0,
        ]);
        $correctOption = FormFieldOption::create([
            'field_id' => $field->id,
            'label' => 'Yes',
            'value' => 'yes',
            'order_index' => 0,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.forms.builder.save', $form), [
            'title' => $form->title,
            'description' => $form->description,
            'settings' => ['show_score' => true],
            'fields' => [[
                'id' => $field->id,
                'type' => 'multiple_choice',
                'label' => 'Question',
                'is_required' => true,
                'settings' => [
                    'points' => 4,
                    'correct_answers' => ['yes'],
                ],
                'options' => [[
                    'id' => $correctOption->id,
                    'label' => 'Yes',
                    'value' => 'yes',
                ]],
            ]],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'settings->show_score' => true,
        ]);
        $this->assertDatabaseHas('form_fields', [
            'id' => $field->id,
            'settings->points' => 4,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.forms.builder', $form))
            ->assertOk()
            ->assertSee('Show score after submission')
            ->assertSee('Correct Answer');
    }

    /**
     * @return array{0: Form, 1: array{choice: FormField, checkbox: FormField}}
     */
    private function createQuizForm(bool $showScore): array
    {
        $form = $this->createForm($showScore);
        $choice = FormField::create([
            'form_id' => $form->id,
            'type' => 'multiple_choice',
            'label' => 'Choose the correct answer',
            'is_required' => true,
            'order_index' => 0,
            'settings' => ['points' => 2, 'correct_answers' => ['yes']],
        ]);
        $checkbox = FormField::create([
            'form_id' => $form->id,
            'type' => 'checkbox',
            'label' => 'Select all correct answers',
            'is_required' => true,
            'order_index' => 1,
            'settings' => ['points' => 3, 'correct_answers' => ['red', 'blue']],
        ]);

        foreach ([
            [$choice, 'yes', 'Yes'],
            [$choice, 'no', 'No'],
            [$checkbox, 'red', 'Red'],
            [$checkbox, 'blue', 'Blue'],
        ] as [$field, $value, $label]) {
            FormFieldOption::create([
                'field_id' => $field->id,
                'label' => $label,
                'value' => $value,
                'order_index' => $field->options()->count(),
            ]);
        }

        return [$form, ['choice' => $choice, 'checkbox' => $checkbox]];
    }

    private function createForm(bool $showScore): Form
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        return Form::create([
            'title' => 'Quiz form',
            'status' => 'published',
            'created_by' => $user->id,
            'submit_button_text' => 'Submit',
            'allow_multiple_responses' => true,
            'settings' => ['show_score' => $showScore],
        ]);
    }
}
