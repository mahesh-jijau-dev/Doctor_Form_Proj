<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignFormRequest;
use App\Models\Form;
use App\Models\User;
use App\Services\FormAssignmentService;
use App\Services\AuditLogService;

class FormAssignmentController extends Controller
{
    public function __construct(
        private FormAssignmentService $assignmentService,
        private AuditLogService $auditLog
    ) {}

    public function show(Form $form)
    {
        $doctors = User::where('role', 'doctor')->where('is_active', true)->with('doctor')->get();
        $assignedIds = $form->assignments()->where('is_active', true)->pluck('doctor_id')->toArray();
        return view('admin.forms.assign', compact('form', 'doctors', 'assignedIds'));
    }

    public function update(AssignFormRequest $request, Form $form)
    {
        $this->assignmentService->syncAssignments($form, $request->doctor_ids);
        $this->auditLog->log('form.assigned', 'Form', $form->id, "Form '{$form->title}' assignments updated");
        return back()->with('success', 'Form assignments updated.');
    }
}
