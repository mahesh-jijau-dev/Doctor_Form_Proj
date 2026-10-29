<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormAssignment;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormResponse;
use App\Models\User;
use App\Services\AssessmentScoringService;
use Database\Seeders\AssessmentFormsSeeder;
use Database\Seeders\FormSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_score_bands_include_all_boundaries_and_report_group_subtotals(): void
    {
        $form = $this->makeForm([
            'type' => 'score_bands',
            'max_score' => 10,
            'groups' => ['digestive' => 'Digestive tract'],
            'bands' => [
                ['min' => 0, 'max' => 4, 'range' => '0-4', 'title' => 'Low'],
                ['min' => 5, 'max' => 10, 'range' => '5-10', 'title' => 'High'],
            ],
        ]);
        $field = $this->makeChoice($form, 'Symptom', 'digestive', [
            ['Never', '0', 0],
            ['Sometimes', '5', 5],
            ['Often', '10', 10],
        ]);
        $scoring = app(AssessmentScoringService::class);

        $low = $scoring->calculate($form, ['field_'.$field->id => '0']);
        $this->assertSame('Low', $low['result']['title']);
        $this->assertSame(0, $low['groups']['digestive']['score']);

        $boundary = $scoring->calculate($form, ['field_'.$field->id => '5']);
        $this->assertSame('High', $boundary['result']['title']);
        $this->assertSame(5, $boundary['groups']['digestive']['score']);
        $this->assertSame(50.0, $boundary['percentage']);

        $top = $scoring->calculate($form, ['field_'.$field->id => '10']);
        $this->assertSame('5-10', $top['result']['range']);
        $this->assertSame(10, $top['total_score']);
    }

    public function test_form_one_style_highest_group_includes_all_ties_but_not_zero_scored_groups(): void
    {
        $form = $this->makeForm([
            'type' => 'score_bands',
            'highlight_highest_group' => true,
            'groups' => ['digestive' => 'Digestive tract', 'skin' => 'Skin'],
            'bands' => [['min' => 0, 'max' => 10, 'title' => 'Assessment']],
        ]);
        $digestive = $this->makeChoice($form, 'Digestive symptom', 'digestive', [
            ['Never', '0', 0], ['Often', '4', 4],
        ]);
        $skin = $this->makeChoice($form, 'Skin symptom', 'skin', [
            ['Never', '0', 0], ['Often', '4', 4],
        ]);
        $scoring = app(AssessmentScoringService::class);

        $tied = $scoring->calculate($form, [
            'field_'.$digestive->id => '4',
            'field_'.$skin->id => '4',
        ]);
        $this->assertSame(['Digestive tract', 'Skin'], $tied['result']['highest_groups']);

        $zero = $scoring->calculate($form, [
            'field_'.$digestive->id => '0',
            'field_'.$skin->id => '0',
        ]);
        $this->assertSame([], $zero['result']['highest_groups']);
    }

    public function test_seeded_form_seven_assigns_the_gap_score_to_rajasic_band(): void
    {
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        User::factory()->create(['role' => 'doctor', 'is_active' => true]);
        $this->seed(FormSeeder::class);

        $form = Form::where('title', 'FORM 7: Intellectual Health Assessment')->firstOrFail();
        $this->assertSame(5, count(data_get($form->settings, 'assessment.sections')));
        $answers = [];
        foreach ($form->fields()->where('settings->assessment_scored', true)->get() as $index => $field) {
            $answers['field_'.$field->id] = $index < 4 ? '3' : '0';
        }

        $result = app(AssessmentScoringService::class)->calculate($form, $answers);

        $this->assertSame(12, $result['total_score']);
        $this->assertSame('12–26', $result['result']['range']);
        $this->assertSame('Rajasic influence (mild)', $result['result']['title']);
        $this->assertSame(12, $result['sections']['cognitive_memory']['max_score']);
    }

    public function test_form_three_reports_all_four_section_totals_including_lifestyle(): void
    {
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->seed(AssessmentFormsSeeder::class);
        $form = Form::where('title', 'Form 3 - Male Hormonal Imbalance Assessment')->firstOrFail();
        $answers = [];

        foreach ($form->fields()->where('settings->assessment_scored', true)->with('options')->get() as $field) {
            $answers['field_'.$field->id] = (string) $field->options->sortByDesc('score')->first()->value;
        }

        $result = app(AssessmentScoringService::class)->calculate($form, $answers);

        $this->assertSame([36, 24, 12, 12], array_column($result['sections'], 'score'));
        $this->assertSame(84, array_sum(array_column($result['sections'], 'score')));
    }

    public function test_seeded_score_band_forms_classify_each_configured_boundary(): void
    {
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->seed(AssessmentFormsSeeder::class);
        $titles = [
            'Form 1 - Physical Health Assessment Form',
            'Form 3 - Male Hormonal Imbalance Assessment',
            'Form 4 - Female Hormonal Imbalance Assessment Questionnaire',
            'Form 7 - Intellectual Health Assessment Form',
        ];
        $scoring = app(AssessmentScoringService::class);

        foreach ($titles as $title) {
            $form = Form::where('title', $title)->firstOrFail();
            $configuration = data_get($form->settings, 'assessment');
            $boundaries = collect($configuration['bands'])
                ->flatMap(fn (array $band) => array_filter([$band['min'] ?? null, $band['max'] ?? null], fn ($value) => $value !== null))
                ->unique()
                ->values();

            foreach ($boundaries as $boundary) {
                $answers = $this->answersAtScore($form, (int) $boundary, $title);
                $result = $scoring->calculate($form, $answers);
                $expectedBand = collect($configuration['bands'])->first(fn (array $band) => $boundary >= ($band['min'] ?? 0) && $boundary <= ($band['max'] ?? PHP_INT_MAX)
                );

                $this->assertNotNull($expectedBand, $title.' boundary '.$boundary.' must match a configured band');
                $this->assertSame((int) $boundary, $result['total_score'], $title.' boundary total');
                $this->assertSame($expectedBand['title'], $result['result']['title'], $title.' boundary classification');
            }
        }
    }

    public function test_pdf_assessment_seeder_creates_all_eight_complete_forms_and_is_idempotent(): void
    {
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        User::factory()->create(['role' => 'doctor', 'is_active' => true]);

        $this->seed(AssessmentFormsSeeder::class);
        $this->seed(AssessmentFormsSeeder::class);

        $forms = Form::query()->orderBy('title')->get();
        $this->assertCount(8, $forms);
        $this->assertSame(
            [16, 18, 19, 21, 25, 28, 40, 92],
            $forms->map(fn (Form $form) => $form->fields()->where('type', 'multiple_choice')->count())->sort()->values()->all()
        );
        $this->assertSame(8, Form::query()->where('status', 'published')->count());
        $this->assertSame(8, FormAssignment::query()->count());
        $this->assertSame([3, 3, 3, 3, 4, 4, 5, 7], $forms->map(fn (Form $form) => $form->sections()->count())->sort()->values()->all());

        $formOne = Form::where('title', 'Form 1 - Physical Health Assessment Form')->firstOrFail();
        $formTwo = Form::where('title', 'Form 2 - Manas Bhav Prakruti Pariksha')->firstOrFail();
        $formThree = Form::where('title', 'Form 3 - Male Hormonal Imbalance Assessment')->firstOrFail();
        $formFour = Form::where('title', 'Form 4 - Female Hormonal Imbalance Assessment Questionnaire')->firstOrFail();
        $formFive = Form::where('title', 'Form 5 - Comprehensive Prakriti Analysis (Vata, Pitta, Kapha)')->firstOrFail();
        $formEight = Form::where('title', 'Form 8 - Dhatu Sarata Parikshana (Tissue Excellence Assessment)')->firstOrFail();

        $this->assertSame(339, data_get($formOne->settings, 'assessment.max_score'));
        $this->assertSame(7, $formOne->fields()->where('settings->assessment_group', 'digestive_tract')->count());
        $this->assertSame(48, data_get($formTwo->settings, 'assessment.max_score'));
        $this->assertSame(84, data_get($formThree->settings, 'assessment.max_score'));
        $this->assertSame(4, count(data_get($formFour->settings, 'assessment.bands')));
        $this->assertSame(40, $formFive->fields()->where('settings->assessment_scored', true)->count());
        $this->assertSame(7, $formEight->fields()->where('settings->assessment_current_condition', true)->count());

        $formTwoAnswers = [];
        foreach ($formTwo->fields()->where('settings->assessment_scored', true)->with('options')->get() as $field) {
            $formTwoAnswers['field_'.$field->id] = (string) $field->options->last()->value;
        }
        $formTwoResult = app(AssessmentScoringService::class)->calculate($formTwo, $formTwoAnswers);
        $this->assertSame(48, $formTwoResult['total_score']);
        $this->assertSame(['sattva' => 0, 'rajas' => 0, 'tamas' => 16], $formTwoResult['categories']);
    }

    public function test_all_eight_seeded_forms_submit_and_save_the_expected_max_score_result(): void
    {
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        User::factory()->create(['role' => 'doctor', 'is_active' => true]);
        $this->seed(AssessmentFormsSeeder::class);

        $expectedResults = [
            'Form 1 - Physical Health Assessment Form' => 'Severe Health Risk',
            'Form 2 - Manas Bhav Prakruti Pariksha' => 'Tamasic Prakruti',
            'Form 3 - Male Hormonal Imbalance Assessment' => 'Severe',
            'Form 4 - Female Hormonal Imbalance Assessment Questionnaire' => 'Severe Imbalance',
            'Form 5 - Comprehensive Prakriti Analysis (Vata, Pitta, Kapha)' => 'Vata dominant',
            'Form 6 - Prakruti Parikshan (Kshar, Sama, Amla)' => 'Kshar dominant',
            'Form 7 - Intellectual Health Assessment Form' => 'Tamas Dominant (Severe)',
            'Form 8 - Dhatu Sarata Parikshana (Tissue Excellence Assessment)' => 'Dhatu Sarata profile',
        ];

        foreach ($expectedResults as $title => $expectedTitle) {
            $form = Form::where('title', $title)->firstOrFail();
            $answers = [];

            foreach ($form->fields()->with('options')->get() as $field) {
                if ($field->hasOptions()) {
                    $options = $field->options;
                    $option = $options->first();
                    if ($options->contains(fn (FormFieldOption $candidate) => $candidate->score !== null)) {
                        $option = $options->sortByDesc('score')->first();
                    }
                    $answers['field_'.$field->id] = (string) $option->value;

                    continue;
                }

                if (! $field->is_required) {
                    continue;
                }

                $answers['field_'.$field->id] = match ($field->type) {
                    'email' => 'patient@example.com',
                    'date' => '1990-01-01',
                    'number' => '35',
                    default => 'Test patient',
                };
            }

            $this->post(route('forms.public.submit', $form), $answers)->assertOk();

            $response = $form->responses()->latest('id')->firstOrFail();
            $this->assertSame($expectedTitle, $response->assessment_result['result']['title'], $title);
            $this->assertSame(
                (float) $response->assessment_result['max_score'],
                (float) $response->assessment_result['total_score'],
                $title.' maximum-score answers should reach the configured total'
            );
            if ($response->assessment_result['score_is_meaningful']) {
                $this->assertEquals($response->assessment_result['total_score'], $response->quiz_score, $title);
                $this->assertEquals($response->assessment_result['max_score'], $response->quiz_max_score, $title);
            } else {
                $this->assertNull($response->quiz_score, $title.' category tally should not be stored as a numeric score');
                $this->assertNull($response->quiz_max_score, $title.' category tally should not have a numeric maximum');
            }
        }

        $this->assertSame(8, FormResponse::query()->count());
    }

    public function test_missing_required_assessment_question_has_a_clear_error_and_identifies_its_field(): void
    {
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        User::factory()->create(['role' => 'doctor', 'is_active' => true]);
        $this->seed(AssessmentFormsSeeder::class);

        $form = Form::where('title', 'Form 1 - Physical Health Assessment Form')->firstOrFail();
        $field = $form->fields()->where('label', 'Ears — Ringing in ear')->firstOrFail();

        $this->get(route('forms.public.show', $form))
            ->assertOk()
            ->assertSee('data-field-id="'.$field->id.'"', false)
            ->assertSee('data-required="true"', false)
            ->assertSee('Please answer it before continuing.');

        $this->from(route('forms.public.show', $form))
            ->post(route('forms.public.submit', $form), [])
            ->assertRedirect(route('forms.public.show', $form))
            ->assertSessionHasErrors(['field_'.$field->id]);

        $this->get(route('forms.public.show', $form))
            ->assertOk()
            ->assertSee('Ears — Ringing in ear')
            ->assertSee('Please answer this question.');
    }

    public function test_guna_tie_is_reported_as_mixed_and_rajas_combines_one_and_two(): void
    {
        $form = $this->makeForm([
            'type' => 'guna_majority',
            'max_score' => 4,
            'category_map' => [
                'sattva' => ['0'],
                'rajas' => ['1', '2'],
                'tamas' => ['3'],
            ],
            'category_titles' => ['sattva' => 'Sattvic', 'rajas' => 'Rajasic', 'tamas' => 'Tamasic'],
        ]);
        $fields = [];
        for ($i = 0; $i < 4; $i++) {
            $fields[] = $this->makeChoice($form, 'Question '.$i, '', [
                ['Sattva', '0', 1], ['Rajas one', '1', 1], ['Rajas two', '2', 1], ['Tamas', '3', 1],
            ]);
        }

        $result = app(AssessmentScoringService::class)->calculate($form, [
            'field_'.$fields[0]->id => '1',
            'field_'.$fields[1]->id => '2',
            'field_'.$fields[2]->id => '0',
            'field_'.$fields[3]->id => '3',
        ]);

        $this->assertSame(['sattva' => 1, 'rajas' => 2, 'tamas' => 1], $result['categories']);
        $this->assertSame('Rajasic', $result['result']['title']);
        $this->assertSame(4, $result['total_score']);
    }

    public function test_equal_guna_categories_are_reported_as_balanced_mixed(): void
    {
        $form = $this->makeForm([
            'type' => 'guna_majority',
            'category_map' => ['sattva' => ['0'], 'rajas' => ['1', '2'], 'tamas' => ['3']],
        ]);
        $fields = [];
        foreach (range(1, 3) as $index) {
            $fields[] = $this->makeChoice($form, 'Question '.$index, '', [
                ['Sattva', '0', 1], ['Rajas', '1', 1], ['Tamas', '3', 1],
            ]);
        }

        $result = app(AssessmentScoringService::class)->calculate($form, [
            'field_'.$fields[0]->id => '0',
            'field_'.$fields[1]->id => '1',
            'field_'.$fields[2]->id => '3',
        ]);

        $this->assertSame('Balanced mixed profile', $result['result']['title']);
        $this->assertCount(3, $result['result']['categories']);
    }

    public function test_vpk_assessment_returns_percentages_and_single_or_dual_profile(): void
    {
        $form = $this->makeForm([
            'type' => 'prakriti_vpk',
            'category_map' => ['vata' => ['vata'], 'pitta' => ['pitta'], 'kapha' => ['kapha']],
            'category_titles' => ['vata' => 'Vata', 'pitta' => 'Pitta', 'kapha' => 'Kapha'],
            'dual_tolerance' => 2,
            'balanced_spread_percent' => 10,
        ]);
        $fields = [];
        for ($i = 0; $i < 5; $i++) {
            $fields[] = $this->makeChoice($form, 'Trait '.$i, '', [
                ['Vata', 'vata', 1], ['Pitta', 'pitta', 1], ['Kapha', 'kapha', 1],
            ]);
        }

        $scoring = app(AssessmentScoringService::class);
        $answers = [
            'field_'.$fields[0]->id => 'vata',
            'field_'.$fields[1]->id => 'vata',
            'field_'.$fields[2]->id => 'pitta',
            'field_'.$fields[3]->id => 'pitta',
            'field_'.$fields[4]->id => 'vata',
        ];
        $dual = $scoring->calculate($form, $answers);
        $this->assertStringContainsString('dual constitution', $dual['result']['title']);
        $this->assertSame(60.0, $dual['result']['percentages']['vata']);

        foreach ($fields as $field) {
            $answers['field_'.$field->id] = 'vata';
        }
        $single = $scoring->calculate($form, $answers);
        $this->assertSame(['vata'], $single['result']['categories']);
        $this->assertSame('Vata dominant', $single['result']['title']);
    }

    public function test_ksa_assessment_applies_documented_balance_and_mixed_tolerances(): void
    {
        $form = $this->makeForm([
            'type' => 'prakriti_ksa',
            'category_map' => ['kshar' => ['k'], 'sama' => ['s'], 'amla' => ['a']],
            'category_titles' => ['kshar' => 'Kshar', 'sama' => 'Sama', 'amla' => 'Amla'],
            'balanced_tolerance' => 1,
            'dual_tolerance' => 2,
        ]);
        $fields = [];
        for ($i = 0; $i < 6; $i++) {
            $fields[] = $this->makeChoice($form, 'Trait '.$i, '', [
                ['Kshar', 'k', 1], ['Sama', 's', 1], ['Amla', 'a', 1],
            ]);
        }

        $scoring = app(AssessmentScoringService::class);
        $balancedAnswers = [];
        foreach ($fields as $index => $field) {
            $balancedAnswers['field_'.$field->id] = ['k', 's', 'a'][$index % 3];
        }
        $this->assertSame('Balanced (Tridoshic)', $scoring->calculate($form, $balancedAnswers)['result']['title']);

        $mixedAnswers = [];
        foreach ($fields as $index => $field) {
            $mixedAnswers['field_'.$field->id] = $index < 3 ? 'k' : ($index < 5 ? 's' : 'a');
        }
        $this->assertStringContainsString('(mixed)', $scoring->calculate($form, $mixedAnswers)['result']['title']);
    }

    public function test_seeded_form_five_and_six_report_category_counts_by_part(): void
    {
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->seed(AssessmentFormsSeeder::class);
        $scoring = app(AssessmentScoringService::class);
        $forms = [
            'Form 5 - Comprehensive Prakriti Analysis (Vata, Pitta, Kapha)' => [
                'part_2' => 'vata', 'part_3' => 'pitta', 'part_4' => 'kapha',
            ],
            'Form 6 - Prakruti Parikshan (Kshar, Sama, Amla)' => [
                'part_1' => 'kshar', 'part_2' => 'sama', 'part_3' => 'amla',
            ],
        ];

        foreach ($forms as $title => $partCategories) {
            $form = Form::where('title', $title)->firstOrFail();
            $answers = [];
            $expectedCounts = [];
            foreach ($form->fields()->where('settings->assessment_scored', true)->with('options')->get() as $field) {
                $section = $field->settings['assessment_section'];
                $category = $partCategories[$section];
                $answers['field_'.$field->id] = $category;
                $expectedCounts[$section][$category] = ($expectedCounts[$section][$category] ?? 0) + 1;
            }

            $result = $scoring->calculate($form, $answers);

            foreach ($expectedCounts as $section => $counts) {
                foreach ($result['section_categories'][$section]['categories'] as $category => $entry) {
                    $this->assertSame($counts[$category] ?? 0, $entry['count'], $title.' / '.$section.' / '.$category);
                }
            }
        }
    }

    public function test_dhatu_profile_scores_each_group_and_records_current_condition(): void
    {
        $groups = ['rasa', 'rakta', 'mamsa', 'meda', 'asthi', 'majja', 'shukra'];
        $form = $this->makeForm([
            'type' => 'dhatu_sarata',
            'max_score' => 42,
            'groups' => array_combine($groups, array_map('ucfirst', $groups)),
            'condition_map' => ['normal' => 'Sarata', 'excess' => 'Vriddhi', 'depleted' => 'Kshaya'],
        ]);
        $answers = [];
        $profileFields = [];
        foreach ($groups as $group) {
            $profileFields[$group] = [];
            for ($i = 0; $i < 3; $i++) {
                $profileFields[$group][] = $this->makeChoice($form, "{$group} question {$i}", $group, [
                    ['Avara', '0', 0], ['Madhya', '1', 1], ['Pravara', '2', 2],
                ]);
            }
            $condition = $this->makeChoice($form, "{$group} current condition", $group, [
                ['Balanced', 'normal', null], ['Excess', 'excess', null], ['Depleted', 'depleted', null],
            ], ['assessment_current_condition' => true]);
            $answers['field_'.$condition->id] = $group === 'rasa' ? 'excess' : 'normal';
        }
        foreach ($profileFields as $group => $fields) {
            foreach ($fields as $field) {
                $answers['field_'.$field->id] = '2';
            }
        }

        $result = app(AssessmentScoringService::class)->calculate($form, $answers);

        $this->assertSame(42, $result['max_score']);
        $this->assertSame(6, $result['groups']['rasa']['score']);
        $this->assertSame('Pravara Sara', $result['result']['profiles']['rasa']['status']);
        $this->assertSame('Vriddhi', $result['result']['profiles']['rasa']['current_condition']);
        $this->assertTrue($result['result']['sarvasara']);
        $this->assertCount(7, $result['result']['strengths']);
        $this->assertSame(['rasa'], $result['result']['active_conditions']);
    }

    public function test_public_submission_persists_configured_result_and_displays_system_totals(): void
    {
        $form = $this->makeForm([
            'type' => 'score_bands',
            'max_score' => 4,
            'groups' => ['digestive' => 'Digestive tract'],
            'bands' => [['min' => 0, 'max' => 4, 'range' => '0–4', 'title' => 'Result', 'summary' => 'Summary text']],
        ]);
        $field = $this->makeChoice($form, 'Nausea', 'digestive', [
            ['Never', '0', 0], ['Often', '4', 4],
        ]);

        $this->post(route('forms.public.submit', $form), ['field_'.$field->id => '4'])
            ->assertOk()
            ->assertSee('Result')
            ->assertSee('Digestive tract')
            ->assertSee('4 / 4')
            ->assertSee('not a medical diagnosis');

        $response = $form->responses()->firstOrFail();
        $this->assertSame('Result', $response->assessment_result['result']['title']);
        $this->assertEquals(4, $response->quiz_score);
        $this->assertEquals(4, $response->quiz_max_score);

        $admin = User::where('role', 'super_admin')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.responses.show', $response))
            ->assertOk()
            ->assertSee('Digestive tract');
        $this->get(route('admin.responses.export.pdf', $response))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $csv = $this->get(route('admin.responses.export', $response))->streamedContent();
        $this->assertStringContainsString('"Assessment Result",Result', $csv);
        $this->assertStringContainsString('"System / category total","Digestive tract",4,4', $csv);
    }

    public function test_category_assessment_displays_part_tallies_without_a_misleading_numeric_score(): void
    {
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        User::factory()->create(['role' => 'doctor', 'is_active' => true]);
        $this->seed(AssessmentFormsSeeder::class);
        $form = Form::where('title', 'Form 6 - Prakruti Parikshan (Kshar, Sama, Amla)')->firstOrFail();
        $answers = [];
        foreach ($form->fields()->with('options')->get() as $field) {
            if ($field->settings['assessment_scored'] ?? false) {
                $answers['field_'.$field->id] = (string) $field->options->first()->value;
            } elseif ($field->is_required) {
                $answers['field_'.$field->id] = match ($field->type) {
                    'email' => 'patient@example.com',
                    'date' => '1990-01-01',
                    default => 'Test patient',
                };
            }
        }

        $this->post(route('forms.public.submit', $form), $answers)
            ->assertOk()
            ->assertSee('Category counts by section')
            ->assertSee('Part 1: Physical Characteristics')
            ->assertDontSee(' / 19 points');

        $response = $form->responses()->latest('id')->firstOrFail();
        $this->assertNull($response->quiz_score);
        $this->assertNull($response->quiz_max_score);

        $admin = User::where('role', 'super_admin')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.responses.show', $response))
            ->assertOk()
            ->assertSee('Category counts by section')
            ->assertDontSee(' / 19 points')
            ->assertDontSee('Score:</strong> 1 points');

        $csv = $this->get(route('admin.responses.export', $response))->streamedContent();
        $this->assertStringContainsString('"Section category count","Part 1: Physical Characteristics",Kshar,6', $csv);
        $this->assertStringNotContainsString('"Section total"', $csv);
        $this->assertStringNotContainsString('"How would you describe your overall body structure?",Underweight,,1', $csv);

        $this->get(route('admin.responses.export.pdf', $response))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $aggregateCsv = $this->get(route('admin.forms.responses.export', $form))->streamedContent();
        $this->assertStringContainsString('Assessment Categories', $aggregateCsv);
        $this->assertStringContainsString('Part 1: Physical Characteristics: Kshar: 6', $aggregateCsv);
    }

    public function test_assessment_submission_rejects_an_option_not_in_the_configured_form(): void
    {
        $form = $this->makeForm([
            'type' => 'score_bands',
            'bands' => [['min' => 0, 'max' => 1, 'title' => 'Assessment']],
        ]);
        $field = $this->makeChoice($form, 'Question', '', [
            ['Valid', 'valid', 1],
        ]);

        $this->post(route('forms.public.submit', $form), ['field_'.$field->id => 'unexpected'])
            ->assertSessionHasErrors('field_'.$field->id);
        $this->assertDatabaseCount('form_responses', 0);
    }

    public function test_form_builder_preserves_assessment_group_metadata(): void
    {
        $form = $this->makeForm([
            'type' => 'score_bands',
            'bands' => [['min' => 0, 'max' => 1, 'title' => 'Assessment']],
        ]);
        $admin = User::where('role', 'super_admin')->firstOrFail();
        $field = $this->makeChoice($form, 'Question', 'digestive', [
            ['Never', '0', 0], ['Often', '1', 1],
        ]);

        $this->actingAs($admin)->postJson(route('admin.forms.builder.save', $form), [
            'title' => $form->title,
            'settings' => ['show_score' => true],
            'fields' => [[
                'id' => $field->id,
                'type' => 'multiple_choice',
                'label' => $field->label,
                'is_required' => true,
                'settings' => [],
                'options' => $field->options->map(fn (FormFieldOption $option) => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'value' => $option->value,
                    'score' => $option->score,
                ])->all(),
            ]],
        ])->assertOk();

        $this->assertSame('digestive', $field->fresh()->settings['assessment_group']);
        $this->assertSame('score_bands', data_get($form->fresh()->settings, 'assessment.type'));
    }

    private function makeForm(array $assessment): Form
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        return Form::create([
            'title' => 'Assessment test',
            'status' => 'published',
            'created_by' => $admin->id,
            'submit_button_text' => 'Submit',
            'allow_multiple_responses' => true,
            'settings' => ['show_score' => true, 'assessment' => $assessment],
        ]);
    }

    private function answersAtScore(Form $form, int $target, string $context = ''): array
    {
        $states = [0 => []];
        $fields = $form->fields()->with('options')->get()->filter(
            fn (FormField $field) => ($field->settings['assessment_scored'] ?? false)
                || $field->options->contains(fn (FormFieldOption $option) => $option->score !== null)
        );

        foreach ($fields as $field) {
            $nextStates = $states;
            foreach ($states as $score => $answers) {
                foreach ($field->options as $option) {
                    $optionScore = $option->score ?? (is_numeric($option->value) ? (float) $option->value : null);
                    if ($optionScore === null) {
                        continue;
                    }

                    $newScore = $score + (int) $optionScore;
                    if ($newScore <= $target && ! array_key_exists($newScore, $nextStates)) {
                        $nextStates[$newScore] = $answers + ['field_'.$field->id => (string) $option->value];
                    }
                }
            }
            $states = $nextStates;
        }

        $this->assertArrayHasKey($target, $states, "{$context}: Could not construct an answer set for total {$target}; maximum reachable total is ".max(array_keys($states)).'.');

        return $states[$target];
    }

    private function makeChoice(Form $form, string $label, string $group, array $options, array $settings = []): FormField
    {
        $field = FormField::create([
            'form_id' => $form->id,
            'type' => 'multiple_choice',
            'label' => $label,
            'is_required' => true,
            'order_index' => $form->fields()->count(),
            'settings' => array_merge($settings, $group === '' ? [] : ['assessment_group' => $group]),
        ]);
        foreach ($options as $index => [$optionLabel, $value, $score]) {
            FormFieldOption::create([
                'field_id' => $field->id,
                'label' => $optionLabel,
                'value' => $value,
                'score' => $score,
                'order_index' => $index,
            ]);
        }

        return $field;
    }
}
