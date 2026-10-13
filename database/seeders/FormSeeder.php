<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Form;
use App\Models\FormSection;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormFieldCondition;
use App\Models\FormAssignment;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'super_admin')->first();
        $doctors = User::where('role', 'doctor')
            ->where('is_active', true)
            ->get();

        if (! $admin) {
            throw new \RuntimeException('A super admin must exist before seeding demo forms.');
        }

        if ($doctors->isEmpty()) {
            throw new \RuntimeException('At least one active doctor must exist to assign the demo forms.');
        }

        // FORM 1: Patient Registration
        $form1 = Form::firstOrCreate(
            ['title' => 'Patient Registration Form'],
            [
                'description' => 'Complete this form to register as a new patient.',
                'status' => 'published',
                'created_by' => $admin->id,
                'submit_button_text' => 'Register Patient',
                'confirmation_message' => 'Thank you! Your registration has been received.',
                'settings' => ['show_score' => false],
                'is_multi_section' => false,
                'version' => 1,
            ]
        );

        if ($form1->fields()->count() === 0) {
            $f = $form1->fields()->create(['type' => 'short_text', 'label' => 'Full Name', 'placeholder' => 'Enter full name', 'is_required' => true, 'order_index' => 0]);
            $f2 = $form1->fields()->create(['type' => 'email', 'label' => 'Email Address', 'placeholder' => 'email@example.com', 'is_required' => true, 'order_index' => 1]);
            $f3 = $form1->fields()->create(['type' => 'phone', 'label' => 'Phone Number', 'placeholder' => '+91 98765 43210', 'is_required' => true, 'order_index' => 2]);
            $f4 = $form1->fields()->create(['type' => 'date', 'label' => 'Date of Birth', 'is_required' => true, 'order_index' => 3]);
            $f5 = $form1->fields()->create(['type' => 'multiple_choice', 'label' => 'Gender', 'is_required' => true, 'order_index' => 4]);
            $f5->options()->createMany([
                ['label' => 'Male', 'value' => 'male', 'order_index' => 0],
                ['label' => 'Female', 'value' => 'female', 'order_index' => 1],
                ['label' => 'Other', 'value' => 'other', 'order_index' => 2],
            ]);
            $f6 = $form1->fields()->create(['type' => 'number', 'label' => 'Age', 'placeholder' => 'Age in years', 'is_required' => true, 'order_index' => 5]);
            $f7 = $form1->fields()->create(['type' => 'dropdown', 'label' => 'Blood Group', 'is_required' => false, 'order_index' => 6]);
            $f7->options()->createMany([
                ['label' => 'A+', 'value' => 'a_pos', 'order_index' => 0],
                ['label' => 'A-', 'value' => 'a_neg', 'order_index' => 1],
                ['label' => 'B+', 'value' => 'b_pos', 'order_index' => 2],
                ['label' => 'B-', 'value' => 'b_neg', 'order_index' => 3],
                ['label' => 'O+', 'value' => 'o_pos', 'order_index' => 4],
                ['label' => 'O-', 'value' => 'o_neg', 'order_index' => 5],
                ['label' => 'AB+', 'value' => 'ab_pos', 'order_index' => 6],
                ['label' => 'AB-', 'value' => 'ab_neg', 'order_index' => 7],
            ]);
            $form1->fields()->create(['type' => 'long_text', 'label' => 'Address', 'placeholder' => 'Full address', 'is_required' => false, 'order_index' => 7]);
        }

        // FORM 2: Medical History with Conditional Logic
        $form2 = Form::firstOrCreate(
            ['title' => 'Medical History'],
            [
                'description' => 'Please provide your complete medical history.',
                'status' => 'published',
                'created_by' => $admin->id,
                'submit_button_text' => 'Submit History',
                'confirmation_message' => 'Thank you. Your medical history has been shared with your care team.',
                'settings' => ['show_score' => false],
                'is_multi_section' => false,
                'version' => 1,
            ]
        );

        if ($form2->fields()->count() === 0) {
            $g1 = $form2->fields()->create(['type' => 'short_text', 'label' => 'Patient Name', 'placeholder' => 'Full name', 'is_required' => true, 'order_index' => 0]);
            $g2 = $form2->fields()->create(['type' => 'checkbox', 'label' => 'Existing Conditions', 'description' => 'Select all that apply', 'is_required' => false, 'order_index' => 1]);
            $g2->options()->createMany([
                ['label' => 'Diabetes', 'value' => 'diabetes', 'order_index' => 0],
                ['label' => 'Hypertension', 'value' => 'hypertension', 'order_index' => 1],
                ['label' => 'Heart Disease', 'value' => 'heart_disease', 'order_index' => 2],
                ['label' => 'Asthma', 'value' => 'asthma', 'order_index' => 3],
                ['label' => 'None', 'value' => 'none', 'order_index' => 4],
            ]);
            $g3 = $form2->fields()->create(['type' => 'multiple_choice', 'label' => 'Do you have Diabetes?', 'is_required' => true, 'order_index' => 2]);
            $g3->options()->createMany([
                ['label' => 'Yes', 'value' => 'yes', 'order_index' => 0],
                ['label' => 'No', 'value' => 'no', 'order_index' => 1],
            ]);
            // Conditional field: show only if diabetes = yes
            $g4 = $form2->fields()->create(['type' => 'short_text', 'label' => 'Since when do you have Diabetes?', 'placeholder' => 'e.g. 2015', 'is_required' => false, 'order_index' => 3]);
            FormFieldCondition::create([
                'field_id' => $g4->id,
                'condition_field_id' => $g3->id,
                'operator' => 'equals',
                'condition_value' => 'yes',
                'action' => 'show',
            ]);
            $g5 = $form2->fields()->create(['type' => 'multiple_choice', 'label' => 'Smoker?', 'is_required' => true, 'order_index' => 4]);
            $g5->options()->createMany([
                ['label' => 'Yes', 'value' => 'yes', 'order_index' => 0],
                ['label' => 'No', 'value' => 'no', 'order_index' => 1],
                ['label' => 'Former Smoker', 'value' => 'former', 'order_index' => 2],
            ]);
            $form2->fields()->create(['type' => 'long_text', 'label' => 'Current Medications', 'placeholder' => 'List all medications you currently take', 'is_required' => false, 'order_index' => 5]);
            $form2->fields()->create(['type' => 'long_text', 'label' => 'Allergies', 'placeholder' => 'List any known allergies', 'is_required' => false, 'order_index' => 6]);
            $form2->fields()->create(['type' => 'rating', 'label' => 'How would you rate your overall health?', 'settings' => ['max' => 5], 'is_required' => false, 'order_index' => 7]);
        }

        // FORM 3: Current Symptoms
        $form3 = Form::firstOrCreate(
            ['title' => 'Current Symptoms Assessment'],
            [
                'description' => 'Describe your current symptoms so the doctor can assess your condition.',
                'status' => 'published',
                'created_by' => $admin->id,
                'submit_button_text' => 'Submit Symptoms',
                'confirmation_message' => 'Thank you. Your symptom information has been shared with your care team.',
                'settings' => ['show_score' => false],
                'is_multi_section' => false,
                'version' => 1,
            ]
        );

        if ($form3->fields()->count() === 0) {
            $form3->fields()->create(['type' => 'short_text', 'label' => 'Patient Name', 'is_required' => true, 'order_index' => 0]);
            $h2 = $form3->fields()->create(['type' => 'checkbox', 'label' => 'Current Symptoms', 'description' => 'Check all symptoms you are experiencing', 'is_required' => true, 'order_index' => 1]);
            $h2->options()->createMany([
                ['label' => 'Fever', 'value' => 'fever', 'order_index' => 0],
                ['label' => 'Headache', 'value' => 'headache', 'order_index' => 1],
                ['label' => 'Cough', 'value' => 'cough', 'order_index' => 2],
                ['label' => 'Shortness of Breath', 'value' => 'breathlessness', 'order_index' => 3],
                ['label' => 'Chest Pain', 'value' => 'chest_pain', 'order_index' => 4],
                ['label' => 'Fatigue', 'value' => 'fatigue', 'order_index' => 5],
                ['label' => 'Nausea', 'value' => 'nausea', 'order_index' => 6],
                ['label' => 'Body Pain', 'value' => 'body_pain', 'order_index' => 7],
            ]);
            $form3->fields()->create(['type' => 'linear_scale', 'label' => 'Pain Level', 'description' => 'Rate your pain from 1 (mild) to 10 (severe)', 'settings' => ['min' => 1, 'max' => 10, 'min_label' => 'No Pain', 'max_label' => 'Severe Pain'], 'is_required' => true, 'order_index' => 2]);
            $form3->fields()->create(['type' => 'date', 'label' => 'Symptom Start Date', 'is_required' => true, 'order_index' => 3]);
            $h5 = $form3->fields()->create(['type' => 'dropdown', 'label' => 'Symptom Duration', 'is_required' => true, 'order_index' => 4]);
            $h5->options()->createMany([
                ['label' => 'Less than 1 day', 'value' => '<1d', 'order_index' => 0],
                ['label' => '1-3 days', 'value' => '1-3d', 'order_index' => 1],
                ['label' => '4-7 days', 'value' => '4-7d', 'order_index' => 2],
                ['label' => '1-2 weeks', 'value' => '1-2w', 'order_index' => 3],
                ['label' => 'More than 2 weeks', 'value' => '>2w', 'order_index' => 4],
            ]);
            $form3->fields()->create(['type' => 'long_text', 'label' => 'Additional Notes', 'placeholder' => 'Any other relevant information', 'is_required' => false, 'order_index' => 5]);
            $form3->fields()->create(['type' => 'file', 'label' => 'Upload Reports/Documents', 'description' => 'Upload any relevant medical reports', 'is_required' => false, 'order_index' => 6]);
        }

        // FORM 4: Healthy Habits Quiz
        $quizForm = Form::firstOrCreate(
            ['title' => 'Healthy Habits Knowledge Quiz'],
            [
                'description' => 'A short general health-awareness quiz. This educational quiz is not a medical assessment.',
                'status' => 'published',
                'created_by' => $admin->id,
                'submit_button_text' => 'See My Score',
                'confirmation_message' => 'Thanks for taking the health-awareness quiz.',
                'settings' => ['show_score' => true],
                'is_multi_section' => false,
                'version' => 1,
            ]
        );

        if ($quizForm->fields()->count() === 0) {
            $questions = [
                [
                    'label' => 'What is a good way to help prevent the spread of germs?',
                    'points' => 2,
                    'correct' => 'wash_hands',
                    'options' => [
                        ['label' => 'Wash your hands regularly', 'value' => 'wash_hands'],
                        ['label' => 'Share personal towels', 'value' => 'share_towels'],
                        ['label' => 'Avoid cleaning frequently touched surfaces', 'value' => 'avoid_cleaning'],
                    ],
                ],
                [
                    'label' => 'Which habit supports better sleep?',
                    'points' => 2,
                    'correct' => 'consistent_schedule',
                    'options' => [
                        ['label' => 'Keep a consistent sleep schedule', 'value' => 'consistent_schedule'],
                        ['label' => 'Use bright screens right before bed', 'value' => 'bright_screens'],
                        ['label' => 'Change bedtime by several hours each night', 'value' => 'changing_bedtime'],
                    ],
                ],
                [
                    'label' => 'When exercising, what should you do if you feel unwell or unusually short of breath?',
                    'points' => 3,
                    'correct' => 'stop_and_seek_advice',
                    'options' => [
                        ['label' => 'Stop and seek appropriate medical advice', 'value' => 'stop_and_seek_advice'],
                        ['label' => 'Push through regardless of how you feel', 'value' => 'push_through'],
                        ['label' => 'Ignore it and exercise harder', 'value' => 'exercise_harder'],
                    ],
                ],
                [
                    'label' => 'What is a balanced approach to everyday meals?',
                    'points' => 2,
                    'correct' => 'variety',
                    'options' => [
                        ['label' => 'Include a variety of foods where possible', 'value' => 'variety'],
                        ['label' => 'Eat only one type of food', 'value' => 'one_food'],
                        ['label' => 'Skip all meals when busy', 'value' => 'skip_meals'],
                    ],
                ],
                [
                    'label' => 'Who can help answer questions about your personal medicines?',
                    'points' => 3,
                    'correct' => 'health_professional',
                    'options' => [
                        ['label' => 'A doctor or pharmacist', 'value' => 'health_professional'],
                        ['label' => 'An online poll', 'value' => 'online_poll'],
                        ['label' => 'A friend without checking with a professional', 'value' => 'unverified_advice'],
                    ],
                ],
            ];

            foreach ($questions as $index => $question) {
                $field = $quizForm->fields()->create([
                    'type' => 'multiple_choice',
                    'label' => $question['label'],
                    'description' => 'Choose one answer.',
                    'is_required' => true,
                    'order_index' => $index,
                    'settings' => [
                        'points' => $question['points'],
                        'correct_answers' => [$question['correct']],
                    ],
                ]);
                $field->options()->createMany(array_map(
                    fn(array $option, int $optionIndex) => $option + ['order_index' => $optionIndex],
                    $question['options'],
                    array_keys($question['options'])
                ));
            }
        }

        // Randomize the doctor order, then assign one doctor to each form.
        $randomDoctors = $doctors->shuffle()->values();
        foreach ([$form1, $form2, $form3, $quizForm] as $index => $form) {
            $doctor = $randomDoctors[$index % $randomDoctors->count()];

            FormAssignment::firstOrCreate(
                ['form_id' => $form->id],
                [
                    'doctor_id' => $doctor->id,
                    'assigned_by' => $admin->id,
                    'assigned_at' => now(),
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('3 demo forms and 1 scored quiz created and assigned to active doctors.');
    }
}
