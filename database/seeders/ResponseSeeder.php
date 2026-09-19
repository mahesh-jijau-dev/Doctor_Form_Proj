<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\FormResponseValue;
use Carbon\Carbon;

class ResponseSeeder extends Seeder
{
    private array $names = [
        'Rahul Patil','Sneha More','Arun Kumar','Pooja Sharma','Vijay Desai',
        'Anita Singh','Manoj Tiwari','Kavya Reddy','Suresh Nair','Deepika Shah',
        'Arjun Mehta','Priti Joshi','Nikhil Rao','Swati Gupta','Ravi Pandey',
    ];
    private array $emails = [
        'rahul.p@gmail.com','sneha.m@gmail.com','arun.k@gmail.com','pooja.s@gmail.com',
        'vijay.d@gmail.com','anita.s@gmail.com','manoj.t@gmail.com','kavya.r@gmail.com',
        'suresh.n@gmail.com','deepika.s@gmail.com','arjun.m@gmail.com','priti.j@gmail.com',
        'nikhil.r@gmail.com','swati.g@gmail.com','ravi.p@gmail.com',
    ];

    public function run(): void
    {
        $doctors = User::where('role', 'doctor')->get();
        $forms = Form::with('fields.options')->where('status', 'published')->get();

        if ($forms->isEmpty() || $doctors->isEmpty()) {
            $this->command->warn('No published forms or doctors found. Run FormSeeder first.');
            return;
        }

        $count = 0;
        foreach ($forms as $form) {
            foreach ($doctors as $doctor) {
                // 5-10 responses per doctor per form
                $numResponses = rand(5, 10);
                for ($i = 0; $i < $numResponses; $i++) {
                    $nameIdx = rand(0, count($this->names) - 1);
                    $response = FormResponse::create([
                        'form_id' => $form->id,
                        'form_version' => $form->version,
                        'assigned_doctor_id' => $doctor->id,
                        'submitted_by_name' => $this->names[$nameIdx],
                        'submitted_by_email' => $this->emails[$nameIdx],
                        'ip_address' => '127.0.0.1',
                        'submitted_at' => Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                        'is_complete' => true,
                    ]);

                    foreach ($form->fields as $field) {
                        if (in_array($field->type, ['section_header','heading','description','image'])) continue;

                        $val = $this->generateValue($field);
                        FormResponseValue::create([
                            'response_id' => $response->id,
                            'field_id' => $field->id,
                            'field_label' => $field->label,
                            'field_type' => $field->type,
                            'value' => is_array($val) ? null : $val,
                            'values' => is_array($val) ? $val : null,
                        ]);
                    }
                    $count++;
                }
            }
        }
        $this->command->info("Created {$count} sample responses.");
    }

    private function generateValue(\App\Models\FormField $field): mixed
    {
        return match($field->type) {
            'short_text' => $field->label === 'Full Name' || $field->label === 'Patient Name'
                ? $this->names[rand(0, count($this->names)-1)]
                : 'Sample answer for ' . $field->label,
            'long_text'  => 'This is a sample response for ' . $field->label . '. Patient provided relevant information.',
            'email'      => $this->emails[rand(0, count($this->emails)-1)],
            'phone'      => '+91 ' . rand(70000,99999) . ' ' . rand(10000,99999),
            'number'     => (string)rand(18, 75),
            'date'       => now()->subDays(rand(365*20, 365*60))->format('Y-m-d'),
            'time'       => sprintf('%02d:%02d', rand(8,18), rand(0,59)),
            'multiple_choice' => $field->options->first()?->value ?? 'option_1',
            'dropdown'   => $field->options->random()?->value ?? 'option_1',
            'checkbox'   => $field->options->take(rand(1, min(3, $field->options->count())))->pluck('value')->toArray(),
            'rating'     => (string)rand(3, 5),
            'linear_scale' => (string)rand(1, intval($field->settings['max'] ?? 5)),
            'file'       => null,
            default      => 'Sample value',
        };
    }
}
