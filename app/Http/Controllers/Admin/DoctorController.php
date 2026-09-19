<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    public function __construct(private AuditLogService $auditLog) {}

    public function index(Request $request)
    {
        $query = Doctor::with('user')
            ->whereHas('user');

        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            })->orWhere('specialty', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->whereHas('user', fn($q) => $q->where('is_active', $status === 'active'));
        }

        $doctors = $query->latest()->paginate(15)->withQueryString();
        return view('admin.doctors.index', compact('doctors'));
    }

    public function create()
    {
        return view('admin.doctors.create');
    }

    public function store(StoreDoctorRequest $request)
    {
        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'doctor',
                'is_active' => true,
            ]);

            Doctor::create([
                'user_id' => $user->id,
                'specialty' => $request->specialty,
                'qualification' => $request->qualification,
                'license_number' => $request->license_number,
                'bio' => $request->bio,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country ?? 'India',
                'notes' => $request->notes,
            ]);

            $this->auditLog->log('doctor.created', 'User', $user->id, "Doctor {$user->name} created");
        });

        return redirect()->route('admin.doctors.index')->with('success', 'Doctor created successfully.');
    }

    public function show(Doctor $doctor)
    {
        $doctor->load(['user', 'assignments.form']);
        $stats = [
            'assigned_forms' => $doctor->assignments()->where('is_active', true)->count(),
            'total_responses' => $doctor->responses()->count(),
        ];
        return view('admin.doctors.show', compact('doctor', 'stats'));
    }

    public function edit(Doctor $doctor)
    {
        $doctor->load('user');
        return view('admin.doctors.edit', compact('doctor'));
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor)
    {
        DB::transaction(function () use ($request, $doctor) {
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ];
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }
            $doctor->user->update($userData);

            $doctor->update([
                'specialty' => $request->specialty,
                'qualification' => $request->qualification,
                'license_number' => $request->license_number,
                'bio' => $request->bio,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country ?? 'India',
                'notes' => $request->notes,
            ]);

            $this->auditLog->log('doctor.updated', 'Doctor', $doctor->id, "Doctor {$doctor->user->name} updated");
        });

        return redirect()->route('admin.doctors.show', $doctor)->with('success', 'Doctor updated successfully.');
    }

    public function destroy(Doctor $doctor)
    {
        $name = $doctor->user->name;
        $doctor->user->update(['is_active' => false]);
        $doctor->delete();
        $this->auditLog->log('doctor.deleted', 'Doctor', $doctor->id, "Doctor {$name} deleted");
        return redirect()->route('admin.doctors.index')->with('success', 'Doctor deleted successfully.');
    }

    public function toggleStatus(Doctor $doctor)
    {
        $user = $doctor->user;
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';
        $this->auditLog->log("doctor.{$status}", 'Doctor', $doctor->id, "Doctor {$user->name} {$status}");
        return back()->with('success', "Doctor {$status} successfully.");
    }
}
