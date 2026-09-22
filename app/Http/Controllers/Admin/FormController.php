<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Models\Form;
use App\Models\User;
use App\Services\FormBuilderService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function __construct(
        private FormBuilderService $builder,
        private AuditLogService $auditLog
    ) {}

    public function index(Request $request)
    {
        $query = Form::withCount('responses')
            ->withCount(['assignments' => fn ($assignmentQuery) => $assignmentQuery->where('is_active', true)])
            ->with('creator');

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $forms = $query->latest()->paginate(15)->withQueryString();
        return view('admin.forms.index', compact('forms'));
    }

    public function create()
    {
        return view('admin.forms.create');
    }

    public function store(StoreFormRequest $request)
    {
        $form = Form::create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'draft',
            'created_by' => auth()->id(),
            'is_multi_section' => $request->boolean('is_multi_section'),
            'submit_button_text' => $request->submit_button_text ?? 'Submit',
        ]);

        $this->auditLog->log('form.created', 'Form', $form->id, "Form '{$form->title}' created");
        return redirect()->route('admin.forms.builder', $form)->with('success', 'Form created. Now add your fields.');
    }

    public function show(Form $form)
    {
        $form->load(['fields.options', 'sections', 'assignments.doctor.doctor']);
        return view('admin.forms.show', compact('form'));
    }

    public function edit(Form $form)
    {
        return view('admin.forms.edit', compact('form'));
    }

    public function update(UpdateFormRequest $request, Form $form)
    {
        $form->update($request->only(['title', 'description', 'submit_button_text', 'confirmation_message', 'allow_multiple_responses']));
        $this->auditLog->log('form.updated', 'Form', $form->id, "Form '{$form->title}' updated");
        return redirect()->route('admin.forms.show', $form)->with('success', 'Form updated.');
    }

    public function destroy(Form $form)
    {
        $title = $form->title;
        $form->delete();
        $this->auditLog->log('form.deleted', 'Form', $form->id, "Form '{$title}' deleted");
        return redirect()->route('admin.forms.index')->with('success', 'Form deleted.');
    }

    public function publish(Form $form)
    {
        $form->update(['status' => 'published']);
        $this->auditLog->log('form.published', 'Form', $form->id, "Form '{$form->title}' published");
        return back()->with('success', 'Form published successfully.');
    }

    public function unpublish(Form $form)
    {
        $form->update(['status' => 'unpublished']);
        $this->auditLog->log('form.unpublished', 'Form', $form->id, "Form '{$form->title}' unpublished");
        return back()->with('success', 'Form unpublished.');
    }

    public function duplicate(Form $form)
    {
        $newForm = $this->builder->duplicateForm($form);
        $this->auditLog->log('form.duplicated', 'Form', $newForm->id, "Form '{$form->title}' duplicated");
        return redirect()->route('admin.forms.builder', $newForm)->with('success', 'Form duplicated.');
    }

    public function preview(Form $form)
    {
        $form->load(['fields.options', 'fields.conditions', 'sections']);
        return view('admin.forms.preview', compact('form'));
    }
}
