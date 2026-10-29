<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\FormAssignment;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormSection;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssessmentFormsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'super_admin')->first();
        if (! $admin) {
            throw new \RuntimeException('A super admin must exist before seeding assessment forms.');
        }

        $doctors = User::where('role', 'doctor')->where('is_active', true)->orderBy('id')->get();
        $definitions = $this->definitions();

        DB::transaction(function () use ($admin, $doctors, $definitions): void {
            foreach ($definitions as $formIndex => $definition) {
                $form = Form::firstOrNew(['title' => $definition['title']]);
                $form->fill([
                    'description' => $definition['description'],
                    'status' => 'published',
                    'created_by' => $admin->id,
                    'submit_button_text' => 'Submit Assessment',
                    'confirmation_message' => 'Thank you for completing this assessment.',
                    'settings' => $definition['settings'],
                    'is_multi_section' => true,
                    'allow_multiple_responses' => true,
                ])->save();

                $this->syncSectionsAndFields($form, $definition['sections']);

                if ($doctors->isNotEmpty()) {
                    $doctor = $doctors[$formIndex % $doctors->count()];
                    FormAssignment::updateOrCreate(
                        ['form_id' => $form->id, 'doctor_id' => $doctor->id],
                        ['assigned_by' => $admin->id, 'assigned_at' => now(), 'is_active' => true]
                    );
                }
            }
        });

        $this->command?->info('Seeded eight PDF-based assessment forms.');
    }

    private function syncSectionsAndFields(Form $form, array $sections): void
    {
        $fieldOrder = 0;
        $usedSectionIds = [];
        $usedFieldIds = [];

        foreach ($sections as $sectionIndex => $sectionData) {
            $section = FormSection::updateOrCreate(
                ['form_id' => $form->id, 'order_index' => $sectionIndex],
                ['title' => $sectionData['title'], 'description' => $sectionData['description'] ?? null]
            );
            $usedSectionIds[] = $section->id;

            $header = $this->saveField($form, $section, $fieldOrder++, [
                'type' => 'section_header',
                'label' => $sectionData['title'],
                'is_required' => false,
                'settings' => [],
            ]);
            $usedFieldIds[] = $header->id;

            foreach ($sectionData['fields'] as $fieldData) {
                $field = $this->saveField($form, $section, $fieldOrder++, $fieldData);
                $usedFieldIds[] = $field->id;

                $options = $fieldData['options'] ?? [];
                $usedOptionIds = [];
                foreach ($options as $optionIndex => $optionData) {
                    $option = FormFieldOption::updateOrCreate(
                        ['field_id' => $field->id, 'order_index' => $optionIndex],
                        [
                            'label' => $optionData['label'],
                            'value' => (string) $optionData['value'],
                            'score' => $optionData['score'],
                            'is_other' => false,
                        ]
                    );
                    $usedOptionIds[] = $option->id;
                }
                $field->options()->whereNotIn('id', $usedOptionIds)->delete();
            }
        }

        $form->fields()->whereNotIn('id', $usedFieldIds)->each(function (FormField $field): void {
            $field->options()->delete();
            $field->conditions()->delete();
            $field->conditionTargets()->delete();
            $field->delete();
        });
        $form->sections()->whereNotIn('id', $usedSectionIds)->delete();
    }

    private function saveField(Form $form, FormSection $section, int $order, array $fieldData): FormField
    {
        $field = FormField::updateOrCreate(
            ['form_id' => $form->id, 'order_index' => $order],
            [
                'section_id' => $section->id,
                'type' => $fieldData['type'],
                'label' => $fieldData['label'],
                'description' => $fieldData['description'] ?? null,
                'placeholder' => $fieldData['placeholder'] ?? null,
                'is_required' => $fieldData['is_required'] ?? true,
                'is_visible' => true,
                'settings' => $fieldData['settings'] ?? null,
                'width' => 'full',
            ]
        );

        return $field;
    }

    private function question(string $label, array $options, array $settings = []): array
    {
        return [
            'type' => 'multiple_choice',
            'label' => $label,
            'is_required' => true,
            'settings' => $settings,
            'options' => $options,
        ];
    }

    private function choices(array $labelsAndScores): array
    {
        return array_map(
            fn (array $option, int $index) => [
                'label' => $option[0],
                'value' => (string) ($option[2] ?? $index),
                'score' => $option[1],
            ],
            $labelsAndScores,
            array_keys($labelsAndScores)
        );
    }

    private function definitions(): array
    {
        return [
            $this->formOne(),
            $this->formTwo(),
            $this->formThree(),
            $this->formFour(),
            $this->formFive(),
            $this->formSix(),
            $this->formSeven(),
            $this->formEight(),
        ];
    }

    private function formOne(): array
    {
        $optionScale = $this->choices([
            ['Never or almost never have the symptom', 0],
            ['Occasionally have it, effect is not severe', 1],
            ['Occasionally have it, effect is severe', 2],
            ['Frequently have it, effect is not severe', 3],
            ['Frequently have it, effect is severe', 4],
        ]);
        $systems = [
            'digestive_tract' => ['Digestive Tract', [
                'Nausea / Vomiting', 'Diarrhea / Loose stool', 'Constipation', 'Bloated feeling',
                'Belching / Passing gas', 'Heartburn', 'Intestinal or stomach pain',
            ]],
            'ears' => ['Ears', ['Itchy ears', 'Ear ache, ear infection', 'Drainage from ear', 'Ringing in ear', 'Hearing loss']],
            'mouth_throat' => ['Mouth / Throat', [
                'Chronic coughing', 'Gagging, frequent need to clear throat',
                'Sour throat, hoarseness, loss of voice', 'Swollen / discolored tongue, gums, lips',
            ]],
            'heart' => ['Heart', ['Irregular or skipped heartbeat', 'Rapid / pounding heartbeat', 'Chest pain']],
            'skin' => ['Skin', ['Acne', 'Hives, rashes, dry skin', 'Hair loss', 'Flushing / hot flushes', 'Excessive sweating']],
            'head' => ['Head', ['Headache', 'Faintness', 'Dizziness', 'Insomnia']],
            'joints_muscles' => ['Joints / Muscles', [
                'Pain / aches in joints', 'Arthritis', 'Stiffness / limitation of movement',
                'Pain / aches in muscles', 'Feeling of weakness / tiredness',
            ]],
            'nose' => ['Nose', ['Stuffy nose', 'Sinus problems', 'Watery itchy eyes', 'Sneezing attacks', 'Excessive mucus formation']],
            'energy_activity' => ['Energy / Activity', ['Fatigue, sluggishness', 'Apathy, lethargy', 'Hyperactivity', 'Restlessness']],
            'weight' => ['Weight', ['Binge eating / drinking', 'Craving certain foods', 'Excessive weight', 'Compulsive eating', 'Water retention', 'Underweight']],
            'lungs' => ['Lungs', ['Chest congestion', 'Asthma, bronchitis', 'Shortness of breath', 'Pain / aches in muscles', 'Difficulty in breathing']],
            'spine' => ['Spine', [
                'Pain when bending forward, backward, or twisting',
                'Morning stiffness in the neck or lower back',
                'Radiating pain, numbness, or tingling in limbs',
            ]],
            'eyes' => ['Eyes', ['Watery / itchy eyes', 'Swollen, redness, sticky eyelids', 'Eye bags / dark circles under eyes', 'Blurred or tunnel vision']],
            'other' => ['Other', ['Frequent illness', 'Frequent or urgent urination', 'Genital itching or discharge', 'Low metabolic disorders']],
        ];

        $partOne = [];
        foreach ($systems as $systemKey => [$systemName, $symptoms]) {
            foreach ($symptoms as $symptom) {
                $partOne[] = $this->question(
                    $systemName.' — '.$symptom,
                    $optionScale,
                    ['assessment_scored' => true, 'assessment_section' => 'part_1', 'assessment_group' => $systemKey]
                );
            }
        }

        $frequencyScale = $this->choices([
            ['No / Never', 0], ['Occasionally', 1], ['Frequently', 2], ['Constantly', 3],
        ]);
        $partTwoQuestions = [
            ['Do you consume alcohol, tobacco products, or recreational drugs?', $this->choices([['Never', 0], ['Occasionally', 1], ['Frequently', 2], ['Constantly', 3]])],
            ['Do you wake up feeling fatigued, heavy, or unrested?', $frequencyScale],
            ['Do you experience abnormal heaviness in the stomach after meals?', $frequencyScale],
            ['Do you experience foul-smelling, sour, or food-tasting burps after meals?', $frequencyScale],
            ['How would you rate your ability to concentrate?', $this->choices([['Excellent', 0], ['Good', 1], ['Fair', 2], ['Poor', 3]])],
            ['Do you engage in regular physical activity?', $this->choices([['Yes, regularly', 0], ['Occasionally', 1], ['Rarely', 2], ['No, never', 3]])],
            ['How would you rate your satisfaction with your sex life with your partner?', $this->choices([['Very satisfied', 0], ['Somewhat satisfied', 1], ['Neutral', 2], ['Dissatisfied', 3]])],
            ['Have you experienced any sexual difficulties?', $this->choices([['No', 0], ['Yes, occasionally', 1], ['Yes, frequently', 2], ['Unsure', 3]])],
            ['Do you carry excess weight around your midsection?', $this->choices([['No', 0], ['Yes, occasionally', 1], ['Yes, frequently', 2], ['Unsure', 3]])],
            ['How often do you consume foods rich in vitamin B12 and calcium?', $this->choices([['Daily', 0], ['Several times a week', 1], ['Occasionally', 2], ['Rarely or never', 3]])],
            ['Have you noticed any changes in your appetite?', $this->choices([['No change', 0], ['Increased appetite', 1], ['Decreased appetite', 2]])],
            ['Have you noticed any changes in the color of your hair, such as greying?', $this->choices([['No', 0], ['Yes, occasionally', 1], ['Yes, frequently', 2], ['Unsure', 3]])],
            ['Have you observed any changes in the thickness or texture of your hair?', $this->choices([['No change', 0], ['Thinning', 1], ['Increased brittleness', 2], ['Unsure', 3]])],
            ['Do you notice any signs of premature aging?', $frequencyScale],
            ['Do you notice any paleness or pigmentation changes in your skin?', $this->choices([['No', 0], ['Occasionally', 1], ['Yes, frequently', 2], ['Unsure', 3]])],
            ['Have you experienced any hormonal changes recently?', $this->choices([['No', 0], ['Yes, occasionally', 1], ['Yes, frequently', 2], ['Unsure', 3]])],
        ];
        $partTwo = [];
        foreach ($partTwoQuestions as [$label, $options]) {
            $partTwo[] = $this->question($label, $options, ['assessment_scored' => true, 'assessment_section' => 'part_2']);
        }
        $historyOptions = $this->choices([
            ['No', 0], ['Yes, in the past', 1], ['Currently undergoing treatment', 2], ['Unsure', 3],
        ]);
        foreach ([
            'Diagnosed with high blood pressure?',
            'Elevated triglyceride levels?',
            'Elevated cholesterol levels?',
            'Diagnosed with type 2 diabetes?',
            'Informed that you are pre-diabetic?',
            'Colitis or irritable bowel syndrome (IBS)?',
            'Diagnosed with thyroid issues?',
            'Diagnosed with gout?',
            'Diagnosed with reduced hemoglobin levels?',
            'Diagnosed with any heart disease?',
            'Brain stroke or suffered brain damage?',
            'History of major neck/back physical trauma?',
        ] as $condition) {
            $partTwo[] = $this->question($condition, $historyOptions, ['assessment_scored' => true, 'assessment_section' => 'part_2']);
        }

        return [
            'title' => 'Form 1 - Physical Health Assessment Form',
            'description' => 'Rate current symptoms and provide medical history and lifestyle information. This self-assessment is not a medical diagnosis.',
            'settings' => [
                'show_score' => true,
                'scoring_mode' => 'weighted_options',
                'assessment' => [
                    'type' => 'score_bands',
                    'max_score' => 339,
                    'highlight_highest_group' => true,
                    'sections' => ['part_1' => 'Part 1: Current Symptom Inventory', 'part_2' => 'Part 2: Medical History, Lifestyle & Specific Conditions'],
                    'groups' => array_map(fn (array $system) => $system[0], $systems),
                    'bands' => [
                        ['min' => 0, 'max' => 50, 'range' => '0–50', 'title' => 'Optimal Health & Systemic Harmony', 'summary' => 'Your physical health is currently stable. Symptoms are transient, infrequent, and likely related to minor, temporary stressors. Your body’s metabolic processes and physiological systems are maintaining a good baseline balance.', 'recommendation' => 'Continue current lifestyle, dietary habits, and routine physical activity. Focus on preventative care and seasonal maintenance.'],
                        ['min' => 51, 'max' => 100, 'range' => '51–100', 'title' => 'Mild Imbalance & Early Warning Signs', 'summary' => 'You are experiencing the early accumulation of stress or physiological imbalance. Certain systems may be operating under strain.', 'recommendation' => 'Minor lifestyle and dietary adjustments are recommended. Focus on digestion, sleep patterns, and triggers in the highest-scoring symptom categories.'],
                        ['min' => 101, 'max' => 165, 'range' => '101–165', 'title' => 'Moderate Dysfunction', 'summary' => 'There is a clear manifestation of deeper systemic issues, potentially affecting energy, concentration, or physical comfort.', 'recommendation' => 'Consult a healthcare practitioner to review the systems contributing to the score and discuss a targeted plan.'],
                        ['min' => 166, 'range' => '166+', 'title' => 'Severe Health Risk', 'summary' => 'Significant physical distress may be present across multiple body systems.', 'recommendation' => 'Prompt clinical supervision and comprehensive evaluation are advised. This score is not a diagnosis.'],
                    ],
                ],
            ],
            'sections' => [
                ['title' => 'Patient Information', 'fields' => [
                    ['type' => 'short_text', 'label' => 'Name'],
                    ['type' => 'date', 'label' => 'Date'],
                    ['type' => 'number', 'label' => 'Age'],
                    ['type' => 'number', 'label' => 'Weight'],
                    ['type' => 'short_text', 'label' => 'Resting Blood Pressure (if known)', 'placeholder' => 'Systolic / Diastolic', 'is_required' => false],
                ]],
                ['title' => 'Part 1: Current Symptom Inventory (0–4)', 'description' => '0 = Never/almost never; 1 = occasionally, not severe; 2 = occasionally, severe; 3 = frequently, not severe; 4 = frequently, severe.', 'fields' => $partOne],
                ['title' => 'Part 2: Medical History, Lifestyle & Specific Conditions', 'description' => 'Select the answer that best applies. Diagnostic-history answers use No (0), past (1), currently treated (2), unsure (3).', 'fields' => $partTwo],
            ],
        ];
    }

    private function formTwo(): array
    {
        $questions = [
            ['Anxiety Level', ['Absent / Calm', 'Low', 'Moderate', 'High']],
            ['Emotional Stability', ['Calm & Balanced', 'Occasional Mood Swings', 'Easily Irritated', 'Highly Volatile / Moody']],
            ['Response to Stress', ['Calm Resolution', 'Confrontation', 'Avoidance', 'Complete Denial']],
            ['Coping Mechanisms', ['Meditation / Self-Reflection', 'Exercise / Hobbies', 'Over-Socializing / Distraction', 'Unhealthy Habits / Addiction']],
            ['Self-Confidence Level', ['Grounded & High', 'Moderate', 'Arrogant / Overconfident', 'Low / Insecure']],
            ['Sleep Quality (Nidra)', ['Sound, wakes up refreshed', 'Light sleeper, easily disturbed', 'Difficulty falling asleep / Insomnia', 'Excessive sleep / Lethargy']],
            ['Communication Style', ['Assertive & Respectful', 'Passive', 'Passive-Aggressive', 'Aggressive']],
            ['Response to Criticism', ['Reflective & Accepting', 'Mildly Defensive', 'Resentful', 'Hostile']],
            ['Handling Conflict', ['Diplomatic', 'Avoidant', 'Confrontational', 'Escalating']],
            ['Family & Social Dynamics', ['Harmonious', 'Supportive but Distant', 'Strained', 'Highly Dependent / Toxic']],
            ['Forgiveness & Compassion (Kshama / Daya)', ['Easily forgives and lets go', 'Holds mild grudges but moves on', 'Highly resentful', 'Vengeful']],
            ['Decision-Making Style', ['Logical / Rational', 'Intuitive', 'Emotional', 'Impulsive']],
            ['Adaptability to Change', ['Embracing & Flexible', 'Adaptive with time', 'Reactive', 'Highly Resistant']],
            ['Time Management Skills', ['Excellent', 'Average', 'Poor', 'Chaotic / Absent']],
            ['Memory & Retention (Smriti)', ['Excellent / Long-term retention', 'Good, but needs occasional prompting', 'Forgetful of recent events', 'Poor memory / Easily confused']],
            ['Attachment & Desires (Kama / Lobha)', ['Content with what one has', 'Ambitious but ethical', 'Highly materialistic / attached', 'Greedy / Never satisfied']],
        ];
        $parts = [
            ['Part A: Psychological Characteristics', 0, 6],
            ['Part B: Social and Interpersonal Relations', 6, 11],
            ['Part C: Cognitive & Work Habits', 11, 16],
        ];
        $sections = [];
        foreach ($parts as [$title, $start, $end]) {
            $fields = [];
            for ($i = $start; $i < $end; $i++) {
                [$question, $labels] = $questions[$i];
                $fields[] = $this->question(
                    $question,
                    $this->choices(array_map(fn (string $label, int $score) => [$label, $score, (string) $score], $labels, array_keys($labels))),
                    ['assessment_scored' => true]
                );
            }
            $sections[] = ['title' => $title, 'fields' => $fields];
        }

        return [
            'title' => 'Form 2 - Manas Bhav Prakruti Pariksha',
            'description' => 'Psychological constitution examination. For each parameter, choose the option that best describes consistent mental state and behavior.',
            'settings' => [
                'show_score' => true,
                'scoring_mode' => 'weighted_options',
                'assessment' => [
                    'type' => 'guna_majority',
                    'max_score' => 48,
                    'category_map' => ['sattva' => ['0'], 'rajas' => ['1', '2'], 'tamas' => ['3']],
                    'category_titles' => ['sattva' => 'Sattvic Prakruti', 'rajas' => 'Rajasic Prakruti', 'tamas' => 'Tamasic Prakruti'],
                    'category_summaries' => [
                        'sattva' => 'The majority of selections fall in the 0-point category.',
                        'rajas' => 'The majority of selections fall in the 1- and 2-point categories.',
                        'tamas' => 'The majority of selections fall in the 3-point category.',
                    ],
                    'mixed_title' => 'Dwandwaja (Mixed)',
                    'mixed_summary' => 'Two categories have an equal highest count.',
                    'balanced_title' => 'Balanced mixed profile',
                    'balanced_summary' => 'All categories have an equal count.',
                ],
            ],
            'sections' => $sections,
        ];
    }

    private function formThree(): array
    {
        $symptomScale = $this->choices([
            ['Never or almost never have the symptom', 0],
            ['Occasionally have it, effect is not severe', 1],
            ['Occasionally have it, effect is severe', 2],
            ['Frequently have it, effect is not severe', 3],
            ['Frequently have it, effect is severe', 4],
        ]);
        $sectionQuestions = [
            ['Part 1: Physical Symptoms', [
                'Erectile Dysfunction: Difficulty achieving or maintaining an erection.',
                'Loss of Morning Erections: Decrease in frequency or firmness of spontaneous morning erections.',
                'Decreased Libido: Reduced sexual desire or sex drive.',
                'Gynecomastia: Enlarged breast tissue or chest tenderness.',
                'Hair Loss (Scalp): Thinning hair or receding hairline.',
                'Reduction in Body or Facial Hair: Thinning of chest, arm, leg hair, or slower beard growth.',
                'Muscle Loss: Noticeable decrease in muscle mass or physical strength.',
                'Weight Gain: Unexplained weight gain, particularly increased abdominal fat.',
                'Joint & Bone Health: Unexplained joint aches or bone stiffness.',
            ]],
            ['Part 2: Emotional & Mental Symptoms', [
                'Fatigue: Persistent tiredness, lack of energy, or feeling sluggish.',
                'Brain Fog & Poor Concentration: Difficulty focusing, mental cloudiness, or memory lapses.',
                'Mood Swings: Rapid or unpredictable changes in mood.',
                'Irritability: Easily frustrated, angered, or annoyed.',
                'Depression: Persistent feelings of sadness, emptiness, or lack of motivation.',
                'Anxiety: Feelings of nervousness, restlessness, or tension.',
            ]],
            ['Part 3: Other Symptoms', [
                'Night Sweats: Waking up sweating excessively during the night.',
                'Hot Flashes: Sudden feelings of intense heat in the face, neck, or chest.',
                'Sleep Disturbances: Insomnia, difficulty falling asleep, or waking up frequently.',
            ]],
        ];
        $sections = [];
        foreach ($sectionQuestions as $sectionIndex => [$title, $questions]) {
            $fields = [];
            foreach ($questions as $question) {
                $fields[] = $this->question($question, $symptomScale, [
                    'assessment_scored' => true,
                    'assessment_section' => 'section_'.($sectionIndex + 1),
                ]);
            }
            $sections[] = ['title' => $title, 'fields' => $fields];
        }
        $sections[] = ['title' => 'Part 4: Lifestyle Factors', 'fields' => [
            $this->question('Dietary Habits', $this->choices([
                ['Balanced, nutrient-dense, home-cooked', 0],
                ['Mostly balanced, occasional junk food', 1],
                ['Frequently eat processed or unhealthy foods', 2],
                ['Highly imbalanced, poor nutritional intake', 3],
                ['Completely dependent on fast food/junk food', 4],
            ]), ['assessment_scored' => true, 'assessment_section' => 'section_4']),
            $this->question('Physical Activity', $this->choices([
                ['Highly Active (Regular intense exercise)', 0],
                ['Moderately Active (Regular light/moderate exercise)', 1],
                ['Lightly Active (Occasional movement)', 2],
                ['Inactive / Sedentary (Rarely exercise)', 3],
                ['Completely Immobile / Bedridden', 4],
            ]), ['assessment_scored' => true, 'assessment_section' => 'section_4']),
            $this->question('Current Stress Levels', $this->choices([
                ['Minimal to None', 0], ['Low (Easily manageable)', 1],
                ['Moderate (Noticeable but manageable)', 2],
                ['High (Difficult to manage, impacts daily life)', 3],
                ['Severe / Overwhelming', 4],
            ]), ['assessment_scored' => true, 'assessment_section' => 'section_4']),
        ]];

        return [
            'title' => 'Form 3 - Male Hormonal Imbalance Assessment',
            'description' => 'Rate each symptom and lifestyle factor from 0 to 4. This screening questionnaire is not a diagnosis.',
            'settings' => [
                'show_score' => true,
                'scoring_mode' => 'weighted_options',
                'assessment' => [
                    'type' => 'score_bands',
                    'max_score' => 84,
                    'sections' => [
                        'section_1' => 'Part 1: Physical Symptoms',
                        'section_2' => 'Part 2: Emotional & Mental Symptoms',
                        'section_3' => 'Part 3: Other Symptoms',
                        'section_4' => 'Part 4: Lifestyle Factors',
                    ],
                    'bands' => [
                        ['min' => 0, 'max' => 9, 'range' => '< 10', 'title' => 'Optimal', 'summary' => 'Hormones are likely balanced. Healthy lifestyle factors.'],
                        ['min' => 10, 'max' => 25, 'range' => '10–25', 'title' => 'Mild', 'summary' => 'Early signs of imbalance. May be corrected with lifestyle and dietary adjustments.'],
                        ['min' => 26, 'max' => 50, 'range' => '26–50', 'title' => 'Moderate', 'summary' => 'Significant symptoms present. Requires clinical investigation and potential treatment.'],
                        ['min' => 51, 'range' => '> 50', 'title' => 'Severe', 'summary' => 'Strong indication of possible hormonal imbalance. Immediate medical evaluation recommended.'],
                    ],
                ],
            ],
            'sections' => $sections,
        ];
    }

    private function formFour(): array
    {
        $questions = [
            ['Length of Menstrual Cycle', [['Regular (28–30 days)', 0], ['Irregular (Varying lengths)', 2], ['Not Applicable (e.g., Menopause)', 0]]],
            ['Menstrual Flow', [['Light / Normal', 0], ['Spotting', 1], ['Moderate', 2], ['Heavy Bleeding', 4]]],
            ['Menstrual Pain (Cramps)', [['None', 0], ['Mild', 1], ['Moderate', 2], ['Severe', 4]]],
            ['Clotting Size & Frequency', [['No clots', 0], ['Small clots (pea-sized, rare)', 1], ['Medium clots (marble-sized, occasional)', 2], ['Large clots (quarter-sized or larger, frequent)', 4]]],
            ['Color of Menstrual Bleeding', [['Bright Red', 0], ['Pinkish / Light', 1], ['Dark Red / Brown', 2], ['Blackish / Off-color', 3]]],
            ['Menstrual Odor', [['None / Normal', 0], ['Mild unpleasant smell', 2], ['Strong bad / foul smell', 4]]],
            ['White / Vaginal Discharge (During Period)', [['No', 0], ['Rarely', 1], ['Frequently', 3]]],
            ['White / Vaginal Discharge (Rest of the Month)', [['No / Normal clear discharge', 0], ['Rarely', 1], ['Frequently (Thick / Heavy white discharge)', 3]]],
            ['Acne / Skin Breakouts', [['None', 0], ['Mild', 1], ['Moderate', 2], ['Severe', 4]]],
            ['Unwanted Facial / Body Hair Growth', [['None', 0], ['Mild', 1], ['Moderate', 2], ['Severe', 4]]],
            ['Hair Loss (Scalp Thinning)', [['None', 0], ['Mild', 1], ['Moderate', 2], ['Severe', 4]]],
            ['Weight Changes', [['Stable', 0], ['Losing weight unexpectedly', 1], ['Fluctuating (Up and Down)', 2], ['Rapid Weight Gain / Hard to Lose', 4]]],
            ['Fatigue / Low Energy', [['Rarely', 0], ['Occasionally', 1], ['Frequently', 3], ['Constantly', 4]]],
            ['Breast Tenderness / Soreness', [['Rarely', 0], ['Occasionally', 1], ['Frequently', 3], ['Constantly', 4]]],
            ['Hot Flashes / Night Sweats', [['None', 0], ['Occasional', 1], ['Frequent', 3], ['Severe', 4]]],
            ['Temperature Sensitivity', [['Rarely', 0], ['Sensitive to Cold', 2], ['Sensitive to Heat', 2], ['Severe Hot & Cold Fluctuations', 4]]],
            ['Mood Swings', [['Rarely', 0], ['Occasionally', 1], ['Frequently', 3], ['Constantly', 4]]],
            ['Anxiety / Restlessness', [['Rarely', 0], ['Occasionally', 1], ['Frequently', 3], ['Constantly', 4]]],
            ['Depression / Low Mood', [['Rarely', 0], ['Occasionally', 1], ['Frequently', 3], ['Constantly', 4]]],
            ['Irritability / Frustration', [['Rarely', 0], ['Occasionally', 1], ['Frequently', 3], ['Constantly', 4]]],
            ['PMS Symptoms (Pre-Menstrual)', [['None', 0], ['Mild', 1], ['Moderate', 2], ['Severe', 4]]],
            ['Brain Fog / Poor Concentration', [['Rarely', 0], ['Occasionally', 1], ['Frequently', 3], ['Constantly', 4]]],
            ['Vaginal Dryness', [['Rarely / None', 0], ['Occasionally', 1], ['Frequently', 3], ['Constantly', 4]]],
            ['Difficulty Getting Pregnant (based on duration)', [['Not Applicable / Not Trying', 0], ['No difficulty', 0], ['Trying for 6–12 months without success', 2], ['Trying for > 12 months without success', 4]]],
            ['Low Sex Drive (Libido)', [['Normal / None', 0], ['Mild Loss', 1], ['Moderate Loss', 2], ['Severe Loss', 4]]],
        ];
        $parts = [
            ['Section 1: Menstrual Cycle & Vaginal Health', 'section_1', 0, 8],
            ['Section 2: Physical & Metabolic Symptoms', 'section_2', 8, 16],
            ['Section 3: Emotional & Cognitive Health', 'section_3', 16, 22],
            ['Section 4: Reproductive & Sexual Health', 'section_4', 22, 25],
        ];
        $sections = [];
        foreach ($parts as [$title, $key, $start, $end]) {
            $fields = [];
            for ($i = $start; $i < $end; $i++) {
                [$label, $options] = $questions[$i];
                $fields[] = $this->question(
                    $label,
                    $this->choices($options),
                    ['assessment_scored' => true, 'assessment_section' => $key]
                );
            }
            $sections[] = ['title' => $title, 'fields' => $fields];
        }

        return [
            'title' => 'Form 4 - Female Hormonal Imbalance Assessment Questionnaire',
            'description' => 'Comprehensive clinical symptom and health screening. Score each item using its listed options; the values are intentionally non-uniform.',
            'settings' => [
                'show_score' => true,
                'scoring_mode' => 'weighted_options',
                'assessment' => [
                    'type' => 'score_bands',
                    'sections' => [
                        'section_1' => 'Section 1: Menstrual Cycle & Vaginal Health',
                        'section_2' => 'Section 2: Physical & Metabolic Symptoms',
                        'section_3' => 'Section 3: Emotional & Cognitive Health',
                        'section_4' => 'Section 4: Reproductive & Sexual Health',
                    ],
                    'bands' => [
                        ['min' => 0, 'max' => 15, 'range' => '0–15', 'title' => 'Optimal Hormonal Balance', 'summary' => 'Hormonal balance appears healthy. Continue normal diet and lifestyle.'],
                        ['min' => 16, 'max' => 35, 'range' => '16–35', 'title' => 'Mild Imbalance', 'summary' => 'Slight hormonal fluctuations. Support with nutrition and stress management.'],
                        ['min' => 36, 'max' => 60, 'range' => '36–60', 'title' => 'Moderate Imbalance', 'summary' => 'Moderate symptoms are present. Targeted lifestyle changes and clinical discussion may be appropriate.'],
                        ['min' => 61, 'range' => '> 60', 'title' => 'Severe Imbalance', 'summary' => 'Significant symptoms are reported. Comprehensive blood testing and clinical consultation are recommended.'],
                    ],
                ],
            ],
            'sections' => $sections,
        ];
    }

    private function formFive(): array
    {
        $parts = [
            ['Part B: Physical Characteristics (Sharirika Rachana)', [
                ['Physique / Body Frame', 'Thin; Small frame', 'Medium; Medium frame', 'Obese; Large frame'],
                ['Body Weight', 'Underweight in relation to height', 'Optimum / Normal as per age and height', 'Overweight / Obese'],
                ['Complexion / Face', 'Dusky; Oval face', 'Fair; Angular face', 'Wheatish; Round face'],
                ['Hair Quality & Color', 'Jet black; Dry', 'Golden/brown; Oily', 'Black; Thick'],
                ['Eyes', 'Small, dry; Turbid white part', 'Small, yellowish/red white part; Brown iris', 'Big, attractive, black iris; Milky white part'],
                ['Skin Texture', 'Dry, rough, like dried twig', 'Delicate; Oily', 'Soft, delicate, smooth; Combination'],
                ['Lips & Tongue', 'Slightly thin; Coated tongue', 'Yellowish red (pink); Clean tongue', 'Reddish/pinkish, attractive'],
                ['Teeth & Gums', 'Very small/big, irregular, dry; Weak', 'Medium size, yellowish; Normal', 'Ivory white, attractive, even-sized; Strong'],
                ['Nails', 'Broken, uneven, dry, rough; Weak', 'Small, beautiful, reddish/pinkish; Normal', 'Long, shiny, reddish-white; Strong'],
                ['Hands / Palms & Soles', 'Rough, cracked; Cold', 'Reddish/pinkish; Warm/Sweaty', 'Delicate/small; Normal'],
                ['Joints & Neck', 'Crepitus (cracking) heard on movement; Stiff neck', 'Joints/bones cannot be seen; Flexible neck', 'Joints strong, well covered; Normal neck'],
                ['Stomach (Umbilicus)', 'Lean belly', 'Medium/optimum belly', 'Abdominal obesity (large belly)'],
            ]],
            ['Part C: Physiological Functions (Sharirika Kriya)', [
                ['Appetite / Hunger', 'Irregular; Variable', 'Intense hunger, cannot control; Strong', 'Low hunger, can skip meals; Poor'],
                ['Digestion', 'Erratic, excess burping; Slow/Irregular', 'Very good, food digests in 3–4 hrs; Fast', 'Low capacity, no desire for evening food; Normal'],
                ['Food Intake / Habits', 'Quantity varies greatly', 'Always eats too much; Fast eater', 'Moderate quantity'],
                ['Taste Cravings', 'Sweet, sour, salty; Bitter', 'Sweet, bitter, astringent; Spicy', 'Spicy, bitter, astringent; Sweet'],
                ['Temperature Preference', 'Prefers warm food/beverage; Prefers warm environment', 'Prefers cold food/beverage; Prefers cold environment', 'Sometimes warm, sometimes cold; Neutral'],
                ['Thirst', 'Not much / Variable', 'Excess thirst', 'Moderate'],
                ['Body Temperature & Sweat', 'Hands/feet cold; Cold body temp; Mild odor', 'Body feels warm; Warm/Sweaty; Strong odor', 'Normal temp; Normal sweating; None/mild odor'],
                ['Elimination (Feces)', 'Experience constipation; Hard feces', 'Always pass liquid stools; Loose feces', 'Not much constipation; Normal feces'],
                ['Urination', 'Frequent', 'Normal', 'Infrequent'],
                ['Physical Energy', 'Weak, tires easily; Low energy', 'Medium strength; High energy', 'Good endurance, works all day; Moderate energy'],
                ['Pace of Activities', 'Very fast', 'Medium speed', 'Very slow'],
                ['Movement & Walking', 'Brisk/rapid, walks like running; Quick', 'Fast; Graceful', 'Slow, steps firmly; Slow'],
                ['Speech', 'Very fast, words unclear; Fast', 'Fast; Loud', 'Slow, calm, emphasizing words; Slow/Soft'],
                ['Sleep Pattern', 'Less sleep, breaks in between; Light sleep', 'Moderate 5–7 hours; Alert sleep', 'Excessive, more than 8 hrs; Deep sleep'],
                ['Disease Tendency', 'Weak resistance; Respiratory issues', 'Variable resistance; Digestive disorders', 'Strong resistance; Allergies'],
            ]],
            ['Part D: Mental, Emotional & Social Traits (Manasika Bhavas)', [
                ['Mental Disposition', 'Anxious/Depressed', 'Energetic', 'Calm'],
                ['General Disposition', 'Introverted', 'Extroverted', 'Balanced'],
                ['Reaction to Incidents', 'Cannot tell, sometimes impulsive', 'Always impulsive', 'Thoughtful, reacts after due thinking'],
                ['Problem Solving', 'Stressed, confused, changes decisions', 'Very angrily, impulsive', 'After proper thinking, firm on decision'],
                ['Grasping Power', 'Very quick to grasp things', 'Easy to grasp and understand', 'Slow to grasp, but understands completely'],
                ['Memory & Learning', 'Low memory, needs repetition', 'Medium memory, needs revision', 'Long-term memory, remembers after reading once'],
                ['Dreams', 'Vivid; Scary, flying, running', 'Normal; Anger, fighting, bright light, gold', 'Forgettable; Ponds, lotuses, gardens, affection'],
                ['Social Relations', 'Prefer Solitude', 'Large Social Circle', 'Few Close Friends'],
                ['Love Relationships', 'Challenging', 'Variable', 'Romantic'],
                ['Attitude to Money', 'Variable; Variable spending', 'Neutral; Balanced spending', 'Saver; Cautious spending'],
                ['Life Goals', 'Personal Growth/Spiritual Enlightenment', 'Career Success/Personal Growth', 'Family Happiness'],
                ['Neurotic Tendencies', 'High', 'Variable', 'Low'],
                ['Mental Relation to Objects', 'Variable', 'Aversion', 'Attachment'],
            ]],
        ];
        $doshas = ['vata', 'pitta', 'kapha'];
        $sections = [];
        foreach ($parts as $partIndex => [$title, $questions]) {
            $fields = [];
            foreach ($questions as [$label, $vata, $pitta, $kapha]) {
                $fields[] = $this->question(
                    $label,
                    [
                        ['label' => $vata, 'value' => 'vata', 'score' => 1],
                        ['label' => $pitta, 'value' => 'pitta', 'score' => 1],
                        ['label' => $kapha, 'value' => 'kapha', 'score' => 1],
                    ],
                    ['assessment_scored' => true, 'assessment_section' => 'part_'.($partIndex + 2)]
                );
            }
            $sections[] = ['title' => $title, 'fields' => $fields];
        }
        $sections[0]['fields'] = array_merge([
            ['type' => 'short_text', 'label' => 'Full Name'],
            ['type' => 'email', 'label' => 'E-Mail ID'],
            ['type' => 'date', 'label' => 'D.O.B. (dd/mm/yyyy)'],
            ['type' => 'short_text', 'label' => 'Gender'],
            ['type' => 'short_text', 'label' => 'Blood Group'],
            ['type' => 'short_text', 'label' => 'City'],
            ['type' => 'phone', 'label' => 'WhatsApp No.'],
            ['type' => 'short_text', 'label' => 'Occupation'],
            ['type' => 'long_text', 'label' => 'Past illness (B.P, Diabetes, Thyroid etc.)', 'is_required' => false],
        ], $sections[0]['fields']);

        return [
            'title' => 'Form 5 - Comprehensive Prakriti Analysis (Vata, Pitta, Kapha)',
            'description' => 'Select one lifelong or long-term trait for each item. Temporary current conditions should not determine this constitution assessment.',
            'settings' => [
                'show_score' => true,
                'scoring_mode' => 'weighted_options',
                'assessment' => [
                    'type' => 'prakriti_vpk',
                    'max_score' => 40,
                    'sections' => ['part_2' => $parts[0][0], 'part_3' => $parts[1][0], 'part_4' => $parts[2][0]],
                    'category_map' => ['vata' => ['vata'], 'pitta' => ['pitta'], 'kapha' => ['kapha']],
                    'category_titles' => ['vata' => 'Vata', 'pitta' => 'Pitta', 'kapha' => 'Kapha'],
                    'dual_tolerance' => 2,
                    'balanced_spread_percent' => 10,
                    'balanced_title' => 'Sama Prakriti (Tridoshaja)',
                    'balanced_summary' => 'All three dosha proportions are nearly equal.',
                    'dual_summary' => 'The two leading dosha proportions are close; this is a dual constitution.',
                    'single_summary' => 'One dosha is significantly higher and is the primary Prakriti.',
                ],
            ],
            'sections' => $sections,
        ];
    }

    private function formSix(): array
    {
        $parts = [
            ['Part 1: Physical Characteristics', [
                ['How would you describe your overall body structure?', 'Underweight', 'Medium frame', 'Overweight'],
                ['What is your natural skin texture?', 'Dry', 'Soft', 'Oily'],
                ['What is your natural skin complexion?', 'Brownish', 'Wheatish', 'Fair'],
                ['How would you describe your hair type?', 'Thin', 'Thick', 'Medium'],
                ['What is the condition of your nails?', 'Thin', 'Thick', 'Brittle'],
                ['How do your eyes generally appear?', 'Yellowish tint', 'Clear/Whitish', 'Watery/Moist'],
            ]],
            ['Part 2: Physiological Habits & Digestion', [
                ['How well do you tolerate hunger?', 'Unable to bear hunger', 'Can bear hunger well', 'Normal tolerance'],
                ['What are your typical dietary cravings?', 'Spicy foods', 'Sweet & Spicy mix', 'Sweet foods'],
                ['How would you describe your typical bowel movements?', 'Hard stool', 'Well-formed (Like peeled banana)', 'Loose formation'],
                ['How is your typical urination pattern?', 'Yellowish color', 'Normal', 'Frequent urination'],
                ['How much do you naturally sweat?', 'Excessive', 'Moderate', 'Less/Minimal'],
                ['What is your typical sleep pattern like?', 'Disturbed sleep', 'Moderate sleep', 'Sound/Deep sleep'],
            ]],
            ['Part 3: Psychological & Social Traits', [
                ['How would you describe your general temperament?', 'Perfectionist, likes cleanliness', 'Happy, high tolerance', 'Stubborn, unanimous'],
                ['How do you typically express your thoughts and opinions? (Rephrased from "Speech")', 'Suspicious of everything', 'Highly opinionated', 'Eloquent and outspoken'],
                ['How do you prefer your social life?', 'Prefer solitude', 'Few close friends', 'Large social circle'],
                ['What kind of professional environment suits you best?', 'Managerial jobs', 'Any kind of business', 'Teaching & other professions'],
                ['How is your ability to learn and remember new information?', 'Quick to learn but easily forget', 'Moderate learning, steady memory', 'Slow to learn but never forget'],
                ['How do you typically react under high pressure or stress?', 'Get anxious and worried', 'Stay calm and balanced', 'Get frustrated or irritable'],
                ['How would you rate your physical endurance and stamina?', 'Low, tire easily', 'Excellent, steady energy', 'Moderate, energy comes in bursts'],
            ]],
        ];
        $sections = [];
        foreach ($parts as $partIndex => [$title, $questions]) {
            $fields = [];
            foreach ($questions as [$label, $kshar, $sama, $amla]) {
                $fields[] = $this->question(
                    $label,
                    [
                        ['label' => $kshar, 'value' => 'kshar', 'score' => 1],
                        ['label' => $sama, 'value' => 'sama', 'score' => 1],
                        ['label' => $amla, 'value' => 'amla', 'score' => 1],
                    ],
                    ['assessment_scored' => true, 'assessment_section' => 'part_'.($partIndex + 1)]
                );
            }
            $sections[] = ['title' => $title, 'fields' => $fields];
        }

        return [
            'title' => 'Form 6 - Prakruti Parikshan (Kshar, Sama, Amla)',
            'description' => 'Select one answer that best describes your lifelong tendencies.',
            'settings' => [
                'show_score' => true,
                'scoring_mode' => 'weighted_options',
                'assessment' => [
                    'type' => 'prakriti_ksa',
                    'max_score' => 19,
                    'sections' => ['part_1' => $parts[0][0], 'part_2' => $parts[1][0], 'part_3' => $parts[2][0]],
                    'category_map' => ['kshar' => ['kshar'], 'sama' => ['sama'], 'amla' => ['amla']],
                    'category_titles' => ['kshar' => 'Kshar', 'sama' => 'Sama', 'amla' => 'Amla'],
                    'balanced_tolerance' => 1,
                    'dual_tolerance' => 2,
                    'balanced_title' => 'Balanced (Tridoshic)',
                    'balanced_summary' => 'All three category counts are relatively equal.',
                    'dual_summary' => 'Two leading categories have equal counts or differ by one or two.',
                    'single_summary' => 'The category with the highest score is the dominant Prakriti.',
                ],
            ],
            'sections' => $sections,
        ];
    }

    private function formSeven(): array
    {
        $parts = [
            ['Section 1: Cognitive Vitality & Memory (Smriti & Dhriti)', [
                ['Have you noticed any decline in your ability to concentrate, focus, or follow complex conversations?', ['No / Never', 'Occasionally', 'Frequently', 'Constantly']],
                ['Do you find yourself forgetting important dates, daily tasks, or recently learned information?', ['Rarely or never', 'Occasionally', 'Frequently', 'Constantly']],
                ['Do you feel that your overall intellectual abilities or memory have declined over the past year?', ['No, they have improved', 'No change', 'Yes, declined somewhat', 'Yes, declined significantly']],
                ['How often do you engage in activities that require deep, uninterrupted concentration?', ['Daily', 'Several times a week', 'Occasionally', 'Rarely or never']],
            ]],
            ['Section 2: Lifelong Learning & Curiosity (Dhi)', [
                ['How often do you actively seek out opportunities to learn new skills, languages, or acquire new knowledge?', ['Regularly', 'Occasionally', 'Rarely', 'Never']],
                ['How often do you read books, articles, or research materials outside of your usual interests or daily routine?', ['Daily', 'Several times a week', 'Occasionally', 'Rarely or never']],
                ['When confronted with an opposing viewpoint, how often do you actively listen or challenge your own beliefs?', ['Frequently', 'Occasionally', 'Rarely', 'Never']],
                ['How often do you engage in mentally stimulating activities such as puzzles, strategic games, or creative writing?', ['Daily', 'Several times a week', 'Occasionally', 'Rarely or never']],
            ]],
            ['Section 3: Problem Solving & Critical Thinking', [
                ['How would you rate your current ability to analyze data and solve complex, unexpected problems?', ['Excellent', 'Good', 'Fair', 'Poor']],
                ['Do you actively seek out opportunities to learn from your mistakes or analyze your past failures?', ['Yes, regularly', 'Occasionally', 'Rarely', 'No, never']],
                ['How often do you engage in discussions or debates that require critical, abstract thinking?', ['Frequently', 'Occasionally', 'Rarely', 'Never']],
            ]],
            ['Section 4: Lifestyle & Environment', [
                ['Do you feel your current daily environment (work, college, or home) adequately challenges you intellectually?', ['Yes, appropriately challenged', 'Yes, but could achieve more', 'No, feeling stagnant', 'No, completely unfulfilled']],
                ['How would you rate your typical sleep quality, which is vital for cognitive function and memory consolidation?', ['Excellent / Restorative', 'Good', 'Fair / Disturbed', 'Poor / Deprived']],
                ['How often do high stress levels negatively impact your ability to think clearly or make rational decisions?', ['Rarely or never', 'Occasionally', 'Frequently', 'Constantly']],
            ]],
            ['Section 5: Manasika Guna (Quality of Mind & Intellect)', [
                ['Processing Information (Dhi): How do you typically react when trying to understand a difficult or complex concept?', ['Calmly study until I understand it (Sattva)', 'Put in intense, sometimes stressful effort (Rajas)', 'Get restless and frequently switch tasks (High Rajas)', 'Give up quickly, feeling it is too difficult (Tamas)']],
                ['Mental Control & Patience (Dhriti): When working on an intellectual task, how easily are you distracted by external stimuli or emotions?', ['Rarely; my focus is steady and grounded', 'Occasionally; I get distracted but pull myself back', 'Frequently; my mind wanders to multiple thoughts', 'Constantly; I feel dull, drowsy, or unmotivated']],
                ['Memory Recall (Smriti): When you need to recall previously learned information during an exam or discussion, how does your mind function?', ['Clear, accurate, and effortless recall', 'Mostly accurate, but requires effort/prompting', 'Jumbled, anxious, or recalling incorrect details', 'Complete blankness, confusion, or forgetfulness']],
                ['Approach to Knowledge: What is your primary internal motivation for learning and seeking intellectual growth?', ['For truth, self-improvement, and helping others', 'For career advancement and social recognition', 'Driven by competition, pressure, or fear of failure', 'I have no desire to learn; it feels like a burden']],
            ]],
        ];
        $sectionKeys = ['cognitive_memory', 'learning_curiosity', 'problem_solving', 'lifestyle_environment', 'manasika_guna'];
        $sections = [];
        foreach ($parts as $sectionIndex => [$title, $questions]) {
            $fields = [];
            foreach ($questions as [$label, $options]) {
                $fields[] = $this->question(
                    $label,
                    $this->choices(array_map(fn (string $option, int $score) => [$option, $score], $options, array_keys($options))),
                    ['assessment_scored' => true, 'assessment_section' => $sectionKeys[$sectionIndex]]
                );
            }
            $sections[] = ['title' => $title, 'fields' => $fields];
        }

        return [
            'title' => 'Form 7 - Intellectual Health Assessment Form',
            'description' => 'Select the option that best describes cognitive habits and abilities. Higher scores indicate a decline or lack of intellectual stimulation. Self-assessment only, not a medical diagnosis.',
            'settings' => [
                'show_score' => true,
                'scoring_mode' => 'weighted_options',
                'assessment' => [
                    'type' => 'score_bands',
                    'max_score' => 54,
                    'sections' => [
                        'cognitive_memory' => $parts[0][0],
                        'learning_curiosity' => $parts[1][0],
                        'problem_solving' => $parts[2][0],
                        'lifestyle_environment' => $parts[3][0],
                        'manasika_guna' => $parts[4][0],
                    ],
                    'bands' => [
                        ['min' => 0, 'max' => 11, 'range' => '< 12', 'title' => 'Sattva Dominant (Optimal)', 'summary' => 'High cognitive vitality. Dhi, Dhriti, and Smriti function harmoniously; the intellect is clear, focused, and capable of deep, unbiased thought.'],
                        ['min' => 12, 'max' => 26, 'range' => '12–26', 'title' => 'Rajasic Influence (Mild)', 'summary' => 'Good intellectual capacity, but the mind may be active, restless, or goal-driven, with vulnerability to overthinking, mental fatigue, and distraction.'],
                        ['min' => 27, 'max' => 40, 'range' => '27–40', 'title' => 'High Rajas / Early Tamas (Moderate)', 'summary' => 'Intellectual turbulence or stress may affect memory, learning, and sustained focus.'],
                        ['min' => 41, 'max' => 54, 'range' => '> 40', 'title' => 'Tamas Dominant (Severe)', 'summary' => 'Lower intellectual engagement, lethargy, or memory concerns may benefit from professional support and lifestyle changes.'],
                    ],
                ],
            ],
            'sections' => $sections,
        ];
    }

    private function formEight(): array
    {
        $dhatus = [
            ['rasa', 'Rasa (Fluids & Hydration)', [
                'Does your skin remain naturally soft, smooth, and clear across all seasons, without excessive dryness or frequent acne?',
                'Are your body hairs fine, deep-rooted, delicate, and sparse?',
                'Can you endure delayed meals or fasting without feeling excessively fatigued, weak, or giddy?',
            ], ['Balanced / Normal (Sarata)', 'Feeling Heavy / Excess Thirst (Vriddhi)', 'Feeling Dry / Dehydrated (Kshaya)']],
            ['rakta', 'Rakta (Blood & Circulation)', [
                'Are your lips, nails, palms, soles, palate, and the corners of your eyes naturally glowing and reddish or deep pink in color?',
                'Can you comfortably tolerate heat and sun exposure without your eyes becoming red or developing allergic skin rashes/urticaria?',
                'Do you possess a brilliant/bright physical appearance alongside moderate physical strength and a balanced energy level?',
            ], ['Balanced / Normal (Sarata)', 'Feeling Hot / Inflamed (Vriddhi)', 'Feeling Cold / Pale (Kshaya)']],
            ['mamsa', 'Mamsa (Muscles)', [
                'Are your shoulders, chest, neck, temples, and joints well-covered with smooth, supple, and firm muscles?',
                'Do your bodily wounds heal properly and quickly after an injury?',
                'Mentally, do you naturally exhibit forgiveness (Kshama), patience, and an absence of greed?',
            ], ['Balanced / Normal (Sarata)', 'Aching / Tender (Vriddhi)', 'Weak / Stiff (Kshaya)']],
            ['meda', 'Meda (Fat & Lubrication)', [
                'Are your hair naturally soft and silky, requiring minimal oil to prevent dryness?',
                'Is your stool consistency naturally soft and well-formed, and do you rarely suffer from constipation?',
                'Are you naturally inclined towards charity, simplicity, and enjoying a comfortable, well-lubricated lifestyle?',
            ], ['Balanced / Normal (Sarata)', 'Sluggish / Sudden Weight Gain (Vriddhi)', 'Extremely Dry / Sudden Weight Loss (Kshaya)']],
            ['asthi', 'Asthi (Bones & Joints)', [
                'Are your bones, joints, heels, and teeth physically prominent, dense, and strong?',
                'Are your joints flexible and free of stiffness, even in cold climates?',
                'Can you endure physical hardships and perform physical work for a prolonged time without feeling fatigued?',
            ], ['Balanced / Normal (Sarata)', 'Aching Joints / Bone Pain (Vriddhi)', 'Brittle / Weak Bones (Kshaya)']],
            ['majja', 'Majja (Nerves & Marrow)', [
                'Are your physical joints thick, long, and naturally rounded?',
                'Is your voice naturally soft, deep, and resonant, rather than rough or harsh?',
                'Do you easily grasp new scientific knowledge (Vijnana), and do you generally receive respect and support from your colleagues when presenting new ideas?',
            ], ['Balanced / Normal (Sarata)', 'Heaviness / Diagnosed Anemia (Vriddhi)', 'Extreme Fatigue / Restless (Kshaya)']],
            ['shukra', 'Shukra (Reproductive & Vitality)', [
                'Do your eyes appear gentle, loving, and bright (milky white), reflecting internal joy?',
                'Do you experience healthy sexual function, balanced hormones, and a strong sense of physical vitality?',
                'Are you highly sincere in your tasks, and do you remain calm and stable rather than getting angry during stressful situations (like examinations)?',
            ], ['Balanced / Normal (Sarata)', 'High Hormonal Fluctuations (Vriddhi)', 'Very Low Energy / Low Libido (Kshaya)']],
        ];
        $sections = [];
        foreach ($dhatus as [$key, $title, $questions, $conditions]) {
            $fields = [];
            foreach ($questions as $question) {
                $fields[] = $this->question(
                    $question,
                    $this->choices([['Avara (Poor / Rarely applies)', 0], ['Madhya (Moderate / Sometimes applies)', 1], ['Pravara (Excellent / Strongly applies)', 2]]),
                    ['assessment_scored' => true, 'assessment_group' => $key]
                );
            }
            $fields[] = $this->question(
                'Current condition: '.$title,
                [
                    ['label' => $conditions[0], 'value' => 'normal', 'score' => null],
                    ['label' => $conditions[1], 'value' => 'excess', 'score' => null],
                    ['label' => $conditions[2], 'value' => 'depleted', 'score' => null],
                ],
                ['assessment_group' => $key, 'assessment_current_condition' => true]
            );
            $sections[] = ['title' => $title, 'fields' => $fields];
        }
        $groups = [];
        $conditionMap = ['normal' => 'Sarata', 'excess' => 'Vriddhi', 'depleted' => 'Kshaya'];
        foreach ($dhatus as [$key, $title]) {
            $groups[$key] = $title;
        }

        return [
            'title' => 'Form 8 - Dhatu Sarata Parikshana (Tissue Excellence Assessment)',
            'description' => 'Rate how accurately each characteristic describes your lifelong physical and mental constitution. This profile is not a disease diagnosis.',
            'settings' => [
                'show_score' => true,
                'scoring_mode' => 'weighted_options',
                'assessment' => [
                    'type' => 'dhatu_sarata',
                    'max_score' => 42,
                    'groups' => $groups,
                    'condition_map' => $conditionMap,
                    'summary' => 'Dhatu scores reflect a self-reported constitution profile and are not a diagnosis.',
                ],
            ],
            'sections' => $sections,
        ];
    }
}
