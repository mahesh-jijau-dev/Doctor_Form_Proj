<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = [
            [
                'name' => 'Dr. Rahul Sharma',
                'email' => 'rahul.sharma@mediform.com',
                'phone' => '+91 98765 43210',
                'specialty' => 'Cardiology',
                'qualification' => 'MBBS, MD (Cardiology)',
                'license_number' => 'MH-2024-001',
                'bio' => 'Senior cardiologist with 15 years of experience in interventional cardiology.',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
            ],
            [
                'name' => 'Dr. Priya Patel',
                'email' => 'priya.patel@mediform.com',
                'phone' => '+91 87654 32109',
                'specialty' => 'Neurology',
                'qualification' => 'MBBS, DM (Neurology)',
                'license_number' => 'GJ-2024-002',
                'bio' => 'Neurologist specializing in epilepsy and movement disorders.',
                'city' => 'Ahmedabad',
                'state' => 'Gujarat',
            ],
            [
                'name' => 'Dr. Amit Verma',
                'email' => 'amit.verma@mediform.com',
                'phone' => '+91 76543 21098',
                'specialty' => 'Orthopedics',
                'qualification' => 'MBBS, MS (Orthopedics)',
                'license_number' => 'DL-2024-003',
                'bio' => 'Orthopedic surgeon with expertise in joint replacement surgery.',
                'city' => 'New Delhi',
                'state' => 'Delhi',
            ],
            [
                'name' => 'Dr. Sneha Kulkarni',
                'email' => 'sneha.kulkarni@mediform.com',
                'phone' => '+91 65432 10987',
                'specialty' => 'Pediatrics',
                'qualification' => 'MBBS, MD (Pediatrics)',
                'license_number' => 'MH-2024-004',
                'bio' => 'Pediatrician with 10 years experience in child health and development.',
                'city' => 'Pune',
                'state' => 'Maharashtra',
            ],
            [
                'name' => 'Dr. Vikram Singh',
                'email' => 'vikram.singh@mediform.com',
                'phone' => '+91 54321 09876',
                'specialty' => 'General Medicine',
                'qualification' => 'MBBS, MD (General Medicine)',
                'license_number' => 'RJ-2024-005',
                'bio' => 'General physician with expertise in diabetes and hypertension management.',
                'city' => 'Jaipur',
                'state' => 'Rajasthan',
            ],
        ];

        $admin = User::where('email', 'admin@mediform.com')->first();

        foreach ($doctors as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'name' => $d['name'],
                    'password' => Hash::make('password'),
                    'phone' => $d['phone'],
                    'role' => 'doctor',
                    'is_active' => true,
                ]
            );

            Doctor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'specialty' => $d['specialty'],
                    'qualification' => $d['qualification'],
                    'license_number' => $d['license_number'],
                    'bio' => $d['bio'],
                    'city' => $d['city'],
                    'state' => $d['state'],
                    'country' => 'India',
                ]
            );
        }

        $this->command->info('5 doctors created. Password: password');
    }
}
