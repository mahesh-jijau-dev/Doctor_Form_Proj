<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\User;
use App\Services\FormResponseService;
use App\Services\ExportService;
use Illuminate\Http\Request;

class ResponseController extends Controller
{
    public function __construct(
        private FormResponseService $responseService,
        private ExportService $exportService
    ) {}

    public function index(Request $request)
    {
        $query = FormResponse::with(['form', 'assignedDoctor'])->latest('submitted_at');

        if ($formId = $request->input('form_id')) {
            $query->where('form_id', $formId);
        }
        if ($doctorId = $request->input('doctor_id')) {
            $query->where(function ($doctorQuery) use ($doctorId) {
                $doctorQuery->where('assigned_doctor_id', $doctorId)
                    ->orWhereHas('form.assignments', function ($assignmentQuery) use ($doctorId) {
                        $assignmentQuery->where('doctor_id', $doctorId)
                            ->where('is_active', true);
                    });
            });
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
        $forms = Form::orderBy('title')->get(['id', 'title']);
        $doctors = User::where('role', 'doctor')->orderBy('name')->get(['id', 'name']);

        return view('admin.responses.index', compact('responses', 'forms', 'doctors'));
    }

    public function show(FormResponse $response)
    {
        $response->load(['form.fields', 'values', 'assignedDoctor']);
        return view('admin.responses.show', compact('response'));
    }

    public function exportFiltered(Request $request)
    {
        $data = $this->responseService->getFilteredExportData($request->integer('form_id') ?: null, null, $request->only(['search', 'doctor_id', 'from', 'to']));
        return $this->exportService->exportFilteredResponsesCsv($data);
    }

    public function exportFilteredPdf(Request $request)
    {
        $data = $this->responseService->getFilteredExportData($request->integer('form_id') ?: null, null, $request->only(['search', 'doctor_id', 'from', 'to']));
        return $this->exportService->exportFilteredResponsesPdf($data);
    }

    public function export(Request $request, Form $form)
    {
        $exportData = $this->responseService->getExportData($form, null, $request->only(['search', 'from', 'to', 'doctor_id']));
        return $this->exportService->exportResponsesCsv($form, $exportData);
    }

    public function exportPdf(Request $request, Form $form)
    {
        $exportData = $this->responseService->getExportData($form, null, $request->only(['search', 'from', 'to', 'doctor_id']));
        return $this->exportService->exportResponsesPdf($form, $exportData);
    }

    public function exportResponse(FormResponse $response)
    {
        $response->load(['form', 'values', 'assignedDoctor']);
        return $this->exportService->exportResponseCsv($response);
    }

    public function exportResponsePdf(FormResponse $response)
    {
        $response->load(['form', 'values', 'assignedDoctor']);
        return $this->exportService->exportResponsePdf($response);
    }

    public function destroy(FormResponse $response)
    {
        $response->values()->delete();
        $response->delete();
        return back()->with('success', 'Response deleted.');
    }
}
