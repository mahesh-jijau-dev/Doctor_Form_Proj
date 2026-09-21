<?php
namespace App\Http\Controllers\Doctor;
use App\Http\Controllers\Controller;
use App\Models\FormResponse;
use App\Models\FormAssignment;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $assignedFormIds = FormAssignment::where('doctor_id', $user->id)
            ->where('is_active', true)
            ->pluck('form_id');

        $stats = [
            'assigned_forms' => $assignedFormIds->count(),
            'total_responses' => FormResponse::whereHas('form.assignments', fn ($query) => $query
                ->where('doctor_id', $user->id)->where('is_active', true))->count(),
            'today_responses' => FormResponse::whereHas('form.assignments', fn ($query) => $query
                ->where('doctor_id', $user->id)->where('is_active', true))
                ->whereDate('submitted_at', today())->count(),
            'this_week_responses' => FormResponse::whereHas('form.assignments', fn ($query) => $query
                ->where('doctor_id', $user->id)->where('is_active', true))
                ->where('submitted_at', '>=', now()->startOfWeek())->count(),
        ];

        $recentResponses = FormResponse::with('form')
            ->whereHas('form.assignments', fn ($query) => $query
                ->where('doctor_id', $user->id)->where('is_active', true))
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
