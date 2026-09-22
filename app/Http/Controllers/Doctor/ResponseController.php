<?php
namespace App\Http\Controllers\Doctor;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormResponse;
use App\Services\FormResponseService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResponseController extends Controller
{
    public function __construct(
        private FormResponseService $responseService,
        private ExportService $exportService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = FormResponse::with('form')
            ->whereHas('form.assignments', function ($assignmentQuery) use ($user) {
                $assignmentQuery->where('doctor_id', $user->id)
                    ->where('is_active', true);
            })
            ->latest('submitted_at');

        if ($formId = $request->input('form_id')) {
            $query->where('form_id', $formId);
        }
        if ($from = $request->input('from')) {
            $query->whereDate('submitted_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('submitted_at', '<=', $to);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('submitted_by_name', 'like', "%{$search}%")
                  ->orWhere('submitted_by_email', 'like', "%{$search}%");
            });
        }

        $responses = $query->paginate(20)->withQueryString();
        $forms = \App\Models\FormAssignment::where('doctor_id', $user->id)
            ->where('is_active', true)
            ->with('form:id,title')
            ->get()
            ->pluck('form')
            ->filter();

        return view('doctor.responses.index', compact('responses', 'forms'));
    }

    public function show(FormResponse $response)
    {
        $this->authorize('view', $response);
        $response->load(['form.fields', 'values', 'assignedDoctor']);
        return view('doctor.responses.show', compact('response'));
    }

    public function exportFiltered(Request $request)
    {
        $data = $this->responseService->getFilteredExportData($request->integer('form_id') ?: null, Auth::id(), $request->only(['search', 'from', 'to']));
        return $this->exportService->exportFilteredResponsesCsv($data);
    }

    public function exportFilteredPdf(Request $request)
    {
        $data = $this->responseService->getFilteredExportData($request->integer('form_id') ?: null, Auth::id(), $request->only(['search', 'from', 'to']));
        return $this->exportService->exportFilteredResponsesPdf($data);
    }

    public function export(Request $request, Form $form)
    {
        $this->authorize('view', $form);
        $exportData = $this->responseService->getExportData($form, Auth::id(), $request->only(['search', 'from', 'to']));
        return $this->exportService->exportResponsesCsv($form, $exportData);
    }

    public function exportPdf(Request $request, Form $form)
    {
        $this->authorize('view', $form);
        $exportData = $this->responseService->getExportData($form, Auth::id(), $request->only(['search', 'from', 'to']));
        return $this->exportService->exportResponsesPdf($form, $exportData);
    }

    public function exportResponse(FormResponse $response)
    {
        $this->authorize('export', $response);
        $response->load(['form', 'values', 'assignedDoctor']);
        return $this->exportService->exportResponseCsv($response);
    }

    public function exportResponsePdf(FormResponse $response)
    {
        $this->authorize('export', $response);
        $response->load(['form', 'values', 'assignedDoctor']);
        return $this->exportService->exportResponsePdf($response);
    }
}
