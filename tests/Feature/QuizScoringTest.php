<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormResponse;
use App\Models\User;
use Database\Seeders\FormSeeder;
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

        $savedResponse = FormResponse::where('form_id', $form->id)->firstOrFail();
        $admin = User::where('role', 'super_admin')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.responses.show', $savedResponse))
            ->assertOk()
            ->assertSee('Answer:')
            ->assertSee('Yes')
            ->assertSee('Correct answer:')
            ->assertSee('Score:')
            ->assertSee('2 points');

        $csvResponse = $this->actingAs($admin)->get(route('admin.responses.export', $savedResponse));
        $csvResponse->assertOk();
        $csv = $csvResponse->streamedContent();
        $this->assertStringContainsString('Field,Answer,"Correct Answer",Score', $csv);
        $this->assertStringContainsString('"Choose the correct answer",Yes,Yes,2', $csv);
        $this->assertStringContainsString('"Select all correct answers",Red,"Red, Blue",0', $csv);

        $savedResponse->load(['form', 'values.field.options', 'assignedDoctor']);
        $pdfHtml = view('exports.response-pdf', [
            'response' => $savedResponse,
            'generatedAt' => now(),
        ])->render();
        $this->assertStringContainsString('Total Score:', $pdfHtml);
        $this->assertStringContainsString('Correct Answer', $pdfHtml);
        $this->assertStringContainsString('Yes', $pdfHtml);
        $this->assertStringContainsString('2', $pdfHtml);

        $this->actingAs($admin)
            ->get(route('admin.responses.export.pdf', $savedResponse))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition');
    }

    public function test_per_option_scores_are_saved_and_used_for_multiple_choice_questions(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $form = $this->createForm(showScore: true);
        $form->update(['settings' => ['show_score' => true]]);
        $field = FormField::create([
            'form_id' => $form->id,
            'type' => 'multiple_choice',
            'label' => 'How would you rate this?',
            'is_required' => true,
            'order_index' => 0,
        ]);
        $lowOption = FormFieldOption::create([
            'field_id' => $field->id,
            'label' => 'Needs improvement',
            'value' => 'needs_improvement',
            'order_index' => 0,
        ]);
        $highOption = FormFieldOption::create([
            'field_id' => $field->id,
            'label' => 'Excellent',
            'value' => 'excellent',
            'order_index' => 1,
        ]);

        $builderResponse = $this->actingAs($admin)->postJson(route('admin.forms.builder.save', $form), [
            'title' => $form->title,
            'settings' => ['show_score' => true],
            'fields' => [[
                'id' => $field->id,
                'type' => 'multiple_choice',
                'label' => $field->label,
                'is_required' => true,
                'settings' => ['points' => 1, 'correct_answers' => []],
                'options' => [
                    ['id' => $lowOption->id, 'label' => $lowOption->label, 'value' => $lowOption->value, 'score' => 0],
                    ['id' => $highOption->id, 'label' => $highOption->label, 'value' => $highOption->value, 'score' => 5],
                ],
            ]],
        ]);

        $builderResponse->assertOk();
        $this->assertDatabaseHas('form_field_options', ['id' => $lowOption->id, 'score' => 0]);
        $this->assertDatabaseHas('form_field_options', ['id' => $highOption->id, 'score' => 5]);
        $this->assertDatabaseHas('forms', ['id' => $form->id, 'settings->option_scoring' => true]);
        $builderReload = $this->actingAs($admin)
            ->get(route('admin.forms.builder', $form))
            ->assertOk()
            ->assertSee('Set a score for every option')
            ->assertSee('Score for this answer')
            ->assertSee('placeholder="Optional"', false)
            ->assertSee('!hasOptionScores(selected)', false)
            ->assertSee('Your session has expired.')
            ->assertSee('HTTP ${res.status}')
            ->assertSee('"score":"5.00"', false);

        $this->post(route('forms.public.submit', $form), [
            'field_' . $field->id => 'excellent',
        ])->assertOk()->assertSee('5')->assertSee('% of available points');

        $savedResponse = FormResponse::where('form_id', $form->id)->firstOrFail();
        $this->assertDatabaseHas('form_responses', [
            'id' => $savedResponse->id,
            'quiz_score' => 5,
            'quiz_max_score' => 5,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.responses.show', $savedResponse))
            ->assertOk()
            ->assertSee('Answer:')
            ->assertSee('Excellent')
            ->assertSee('Score:')
            ->assertSee('5 points');
    }

    public function test_builder_warns_when_score_display_is_enabled_without_scored_questions(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $form = $this->createForm(showScore: true);
        FormField::create([
            'form_id' => $form->id,
            'type' => 'multiple_choice',
            'label' => 'Unscored question',
            'order_index' => 0,
            'settings' => ['points' => 1, 'correct_answers' => []],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.forms.builder', $form))
            ->assertOk()
            ->assertSee('Score display is on, but no scoring is configured yet.');
    }

    public function test_builder_requires_a_score_for_every_option_once_option_scoring_is_used(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $form = $this->createForm(showScore: true);
        $field = FormField::create([
            'form_id' => $form->id,
            'type' => 'multiple_choice',
            'label' => 'Question',
            'order_index' => 0,
        ]);
        $first = FormFieldOption::create([
            'field_id' => $field->id,
            'label' => 'First',
            'value' => 'first',
            'order_index' => 0,
        ]);
        $second = FormFieldOption::create([
            'field_id' => $field->id,
            'label' => 'Second',
            'value' => 'second',
            'order_index' => 1,
        ]);

        $this->actingAs($admin)->postJson(route('admin.forms.builder.save', $form), [
            'title' => $form->title,
            'settings' => ['show_score' => true],
            'fields' => [[
                'id' => $field->id,
                'type' => 'multiple_choice',
                'label' => $field->label,
                'options' => [
                    ['id' => $first->id, 'label' => 'First', 'value' => 'first', 'score' => 2],
                    ['id' => $second->id, 'label' => 'Second', 'value' => 'second', 'score' => null],
                ],
            ]],
        ])->assertUnprocessable()->assertJsonValidationErrors('fields.0.options');
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

    public function test_weighted_assessment_options_are_scored_and_interpreted(): void
    {
        $form = $this->createForm(showScore: true);
        $form->update([
            'title' => 'FORM 7: Intellectual Health Assessment',
            'settings' => [
                'show_score' => true,
                'scoring_mode' => 'weighted_options',
            ],
        ]);

        $fields = [];
        for ($index = 0; $index < 18; $index++) {
            $field = FormField::create([
                'form_id' => $form->id,
                'type' => 'multiple_choice',
                'label' => 'Assessment question ' . ($index + 1),
                'is_required' => true,
                'order_index' => $index,
                'settings' => ['assessment_scored' => true],
            ]);
            foreach ([
                '0' => 'Option scored zero',
                '1' => 'Option scored one',
                '2' => 'Option scored two',
                '3' => 'Option scored three',
            ] as $value => $label) {
                FormFieldOption::create([
                    'field_id' => $field->id,
                    'label' => $label,
                    'value' => $value,
                    'order_index' => (int) $value,
                ]);
            }
            $fields[] = $field;
        }

        $answers = [];
        foreach ($fields as $index => $field) {
            $answers['field_' . $field->id] = $index === 0 ? '3' : '1';
        }

        $response = $this->post(route('forms.public.submit', $form), $answers);

        $response->assertOk()
            ->assertSee('Your Assessment Score')
            ->assertSee('Rajasic influence')
            ->assertSee('13–26')
            ->assertSee('not a medical diagnosis');

        $this->assertDatabaseHas('form_responses', [
            'form_id' => $form->id,
            'quiz_score' => 20,
            'quiz_max_score' => 54,
        ]);

        $savedResponse = FormResponse::where('form_id', $form->id)->firstOrFail();
        $admin = User::where('role', 'super_admin')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.responses.show', $savedResponse))
            ->assertOk()
            ->assertSee('Answer:')
            ->assertSee('Option scored one')
            ->assertSee('Score:')
            ->assertSee('1 points');
    }

    public function test_weighted_assessment_rejects_option_values_outside_the_score_scale(): void
    {
        $form = $this->createForm(showScore: true);
        $form->update([
            'settings' => ['show_score' => true, 'scoring_mode' => 'weighted_options'],
        ]);
        $field = FormField::create([
            'form_id' => $form->id,
            'type' => 'multiple_choice',
            'label' => 'Assessment question',
            'is_required' => true,
            'order_index' => 0,
            'settings' => ['assessment_scored' => true],
        ]);

        foreach (['0', '1', '2', '3'] as $index => $value) {
            FormFieldOption::create([
                'field_id' => $field->id,
                'label' => 'Option ' . $value,
                'value' => $value,
                'order_index' => $index,
            ]);
        }

        $this->post(route('forms.public.submit', $form), [
            'field_' . $field->id => '9',
        ])->assertSessionHasErrors('field_' . $field->id);

        $this->assertDatabaseCount('form_responses', 0);
    }

    public function test_assessment_score_boundaries_are_classified_without_gaps(): void
    {
        $form = Form::make(['settings' => ['scoring_mode' => 'weighted_options']]);
        $expectedRanges = [
            0 => '0–12',
            12 => '0–12',
            13 => '13–26',
            26 => '13–26',
            27 => '27–40',
            40 => '27–40',
            41 => '41–54',
            54 => '41–54',
        ];

        foreach ($expectedRanges as $score => $expectedRange) {
            $response = new FormResponse(['quiz_score' => $score]);
            $response->setRelation('form', $form);

            $this->assertSame($expectedRange, $response->quiz_interpretation['range']);
        }
    }

    public function test_form_seeder_creates_form_7_with_eighteen_scored_questions(): void
    {
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        User::factory()->create(['role' => 'doctor', 'is_active' => true]);

        $this->seed(FormSeeder::class);

        $form = Form::where('title', 'FORM 7: Intellectual Health Assessment')->firstOrFail();

        $this->assertTrue($form->is_multi_section);
        $this->assertSame('weighted_options', data_get($form->settings, 'scoring_mode'));
        $this->assertSame(5, $form->fields()->where('type', 'section_header')->count());
        $this->assertSame(18, $form->fields()->where('settings->assessment_scored', true)->count());
        $this->assertSame(72, $form->fields()
            ->where('settings->assessment_scored', true)
            ->withCount('options')
            ->get()
            ->sum('options_count'));

        $admin = User::where('role', 'super_admin')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.forms.builder', $form))
            ->assertOk()
            ->assertSee('Score for this answer')
            ->assertSee('Set a score for every option');
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
