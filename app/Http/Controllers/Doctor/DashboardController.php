<?php
namespace App\Http\Controllers\Doctor;
use App\Http\Controllers\Controller;
use App\Models\FormResponse;
use App\Models\FormAssignment;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $assignedFormIds = FormAssignment::where('doctor_id', $user->id)
            ->where('is_active', true)
            ->pluck('form_id');

        $stats = [
            'assigned_forms' => $assignedFormIds->count(),
            'total_responses' => FormResponse::where('assigned_doctor_id', $user->id)->count(),
            'today_responses' => FormResponse::where('assigned_doctor_id', $user->id)->whereDate('submitted_at', today())->count(),
            'this_week_responses' => FormResponse::where('assigned_doctor_id', $user->id)->where('submitted_at', '>=', now()->startOfWeek())->count(),
        ];

        $recentResponses = FormResponse::with('form')
            ->where('assigned_doctor_id', $user->id)
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        $assignedForms = FormAssignment::with(['form' => fn($q) => $q->withCount('responses')])
            ->where('doctor_id', $user->id)
            ->where('is_active', true)
            ->latest()
            ->limit(5)
            ->get();

        return view('doctor.dashboard', compact('stats', 'recentResponses', 'assignedForms'));
    }
}
