<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Doctor;
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
        $doctors = User::where('role', 'doctor')->get();

        // FORM 1: Patient Registration
        $form1 = Form::firstOrCreate(
            ['title' => 'Patient Registration Form'],
            [
                'description' => 'Complete this form to register as a new patient.',
                'status' => 'published',
                'created_by' => $admin->id,
                'submit_button_text' => 'Register Patient',
                'confirmation_message' => 'Thank you! Your registration has been received.',
                'is_multi_section' => false,
                'version' => 1,
            ]
        );

        if ($form1->fields()->count() === 0) {
            $f = $form1->fields()->create(['type'=>'short_text','label'=>'Full Name','placeholder'=>'Enter full name','is_required'=>true,'order_index'=>0]);
            $f2 = $form1->fields()->create(['type'=>'email','label'=>'Email Address','placeholder'=>'email@example.com','is_required'=>true,'order_index'=>1]);
            $f3 = $form1->fields()->create(['type'=>'phone','label'=>'Phone Number','placeholder'=>'+91 98765 43210','is_required'=>true,'order_index'=>2]);
            $f4 = $form1->fields()->create(['type'=>'date','label'=>'Date of Birth','is_required'=>true,'order_index'=>3]);
            $f5 = $form1->fields()->create(['type'=>'multiple_choice','label'=>'Gender','is_required'=>true,'order_index'=>4]);
            $f5->options()->createMany([
                ['label'=>'Male','value'=>'male','order_index'=>0],
                ['label'=>'Female','value'=>'female','order_index'=>1],
                ['label'=>'Other','value'=>'other','order_index'=>2],
            ]);
            $f6 = $form1->fields()->create(['type'=>'number','label'=>'Age','placeholder'=>'Age in years','is_required'=>true,'order_index'=>5]);
            $f7 = $form1->fields()->create(['type'=>'dropdown','label'=>'Blood Group','is_required'=>false,'order_index'=>6]);
            $f7->options()->createMany([
                ['label'=>'A+','value'=>'a_pos','order_index'=>0],
                ['label'=>'A-','value'=>'a_neg','order_index'=>1],
                ['label'=>'B+','value'=>'b_pos','order_index'=>2],
                ['label'=>'B-','value'=>'b_neg','order_index'=>3],
                ['label'=>'O+','value'=>'o_pos','order_index'=>4],
                ['label'=>'O-','value'=>'o_neg','order_index'=>5],
                ['label'=>'AB+','value'=>'ab_pos','order_index'=>6],
                ['label'=>'AB-','value'=>'ab_neg','order_index'=>7],
            ]);
            $form1->fields()->create(['type'=>'long_text','label'=>'Address','placeholder'=>'Full address','is_required'=>false,'order_index'=>7]);
        }

        // FORM 2: Medical History with Conditional Logic
        $form2 = Form::firstOrCreate(
            ['title' => 'Medical History'],
            [
                'description' => 'Please provide your complete medical history.',
                'status' => 'published',
                'created_by' => $admin->id,
                'submit_button_text' => 'Submit History',
                'is_multi_section' => false,
                'version' => 1,
            ]
        );

        if ($form2->fields()->count() === 0) {
            $g1 = $form2->fields()->create(['type'=>'short_text','label'=>'Patient Name','placeholder'=>'Full name','is_required'=>true,'order_index'=>0]);
            $g2 = $form2->fields()->create(['type'=>'checkbox','label'=>'Existing Conditions','description'=>'Select all that apply','is_required'=>false,'order_index'=>1]);
            $g2->options()->createMany([
                ['label'=>'Diabetes','value'=>'diabetes','order_index'=>0],
                ['label'=>'Hypertension','value'=>'hypertension','order_index'=>1],
                ['label'=>'Heart Disease','value'=>'heart_disease','order_index'=>2],
                ['label'=>'Asthma','value'=>'asthma','order_index'=>3],
                ['label'=>'None','value'=>'none','order_index'=>4],
            ]);
            $g3 = $form2->fields()->create(['type'=>'multiple_choice','label'=>'Do you have Diabetes?','is_required'=>true,'order_index'=>2]);
            $g3->options()->createMany([
                ['label'=>'Yes','value'=>'yes','order_index'=>0],
                ['label'=>'No','value'=>'no','order_index'=>1],
            ]);
            // Conditional field: show only if diabetes = yes
            $g4 = $form2->fields()->create(['type'=>'short_text','label'=>'Since when do you have Diabetes?','placeholder'=>'e.g. 2015','is_required'=>false,'order_index'=>3]);
            FormFieldCondition::create([
                'field_id' => $g4->id,
                'condition_field_id' => $g3->id,
                'operator' => 'equals',
                'condition_value' => 'yes',
                'action' => 'show',
            ]);
            $g5 = $form2->fields()->create(['type'=>'multiple_choice','label'=>'Smoker?','is_required'=>true,'order_index'=>4]);
            $g5->options()->createMany([
                ['label'=>'Yes','value'=>'yes','order_index'=>0],
                ['label'=>'No','value'=>'no','order_index'=>1],
                ['label'=>'Former Smoker','value'=>'former','order_index'=>2],
            ]);
            $form2->fields()->create(['type'=>'long_text','label'=>'Current Medications','placeholder'=>'List all medications you currently take','is_required'=>false,'order_index'=>5]);
            $form2->fields()->create(['type'=>'long_text','label'=>'Allergies','placeholder'=>'List any known allergies','is_required'=>false,'order_index'=>6]);
            $form2->fields()->create(['type'=>'rating','label'=>'How would you rate your overall health?','settings'=>['max'=>5],'is_required'=>false,'order_index'=>7]);
        }

        // FORM 3: Current Symptoms
        $form3 = Form::firstOrCreate(
            ['title' => 'Current Symptoms Assessment'],
            [
                'description' => 'Describe your current symptoms so the doctor can assess your condition.',
                'status' => 'published',
                'created_by' => $admin->id,
                'submit_button_text' => 'Submit Symptoms',
                'is_multi_section' => false,
                'version' => 1,
            ]
        );

        if ($form3->fields()->count() === 0) {
            $form3->fields()->create(['type'=>'short_text','label'=>'Patient Name','is_required'=>true,'order_index'=>0]);
            $h2 = $form3->fields()->create(['type'=>'checkbox','label'=>'Current Symptoms','description'=>'Check all symptoms you are experiencing','is_required'=>true,'order_index'=>1]);
            $h2->options()->createMany([
                ['label'=>'Fever','value'=>'fever','order_index'=>0],
                ['label'=>'Headache','value'=>'headache','order_index'=>1],
                ['label'=>'Cough','value'=>'cough','order_index'=>2],
                ['label'=>'Shortness of Breath','value'=>'breathlessness','order_index'=>3],
                ['label'=>'Chest Pain','value'=>'chest_pain','order_index'=>4],
                ['label'=>'Fatigue','value'=>'fatigue','order_index'=>5],
                ['label'=>'Nausea','value'=>'nausea','order_index'=>6],
                ['label'=>'Body Pain','value'=>'body_pain','order_index'=>7],
            ]);
            $form3->fields()->create(['type'=>'linear_scale','label'=>'Pain Level','description'=>'Rate your pain from 1 (mild) to 10 (severe)','settings'=>['min'=>1,'max'=>10,'min_label'=>'No Pain','max_label'=>'Severe Pain'],'is_required'=>true,'order_index'=>2]);
            $form3->fields()->create(['type'=>'date','label'=>'Symptom Start Date','is_required'=>true,'order_index'=>3]);
            $h5 = $form3->fields()->create(['type'=>'dropdown','label'=>'Symptom Duration','is_required'=>true,'order_index'=>4]);
            $h5->options()->createMany([
                ['label'=>'Less than 1 day','value'=>'<1d','order_index'=>0],
                ['label'=>'1-3 days','value'=>'1-3d','order_index'=>1],
                ['label'=>'4-7 days','value'=>'4-7d','order_index'=>2],
                ['label'=>'1-2 weeks','value'=>'1-2w','order_index'=>3],
                ['label'=>'More than 2 weeks','value'=>'>2w','order_index'=>4],
            ]);
            $form3->fields()->create(['type'=>'long_text','label'=>'Additional Notes','placeholder'=>'Any other relevant information','is_required'=>false,'order_index'=>5]);
            $form3->fields()->create(['type'=>'file','label'=>'Upload Reports/Documents','description'=>'Upload any relevant medical reports','is_required'=>false,'order_index'=>6]);
        }

        // Assign all 3 forms to all 5 doctors
        foreach ([$form1, $form2, $form3] as $form) {
            foreach ($doctors as $doctor) {
                FormAssignment::firstOrCreate(
                    ['form_id' => $form->id, 'doctor_id' => $doctor->id],
                    [
                        'assigned_by' => $admin->id,
                        'assigned_at' => now(),
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('3 forms created and assigned to all doctors.');
    }
}
