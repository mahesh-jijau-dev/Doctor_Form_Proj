<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\AuditLog;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_doctors' => User::where('role', 'doctor')->count(),
            'active_doctors' => User::where('role', 'doctor')->where('is_active', true)->count(),
            'total_forms' => Form::count(),
            'published_forms' => Form::where('status', 'published')->count(),
            'total_responses' => FormResponse::count(),
            'today_responses' => FormResponse::whereDate('submitted_at', today())->count(),
            'this_month_responses' => FormResponse::whereMonth('submitted_at', now()->month)->count(),
        ];

        $recentResponses = FormResponse::with(['form', 'assignedDoctor'])
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        $recentActivity = AuditLog::with('user')
            ->latest()
            ->limit(10)
            ->get();

        // Responses last 7 days for chart
        $chartData = FormResponse::select(
                DB::raw('DATE(submitted_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('submitted_at', '>=', now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartLabels = [];
        $chartValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('M d');
            $chartValues[] = $chartData[$date]->count ?? 0;
        }

        $topForms = Form::withCount('responses')
            ->orderByDesc('responses_count')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentResponses', 'recentActivity', 'chartLabels', 'chartValues', 'topForms'));
    }
}
