<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveFormBuilderRequest;
use App\Models\Form;
use App\Models\FormField;
use App\Services\FormBuilderService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FormBuilderController extends Controller
{
    public function __construct(
        private FormBuilderService $builder,
        private AuditLogService $auditLog
    ) {}

    public function show(Form $form)
    {
        $form->load(['sections' => fn($q) => $q->orderBy('order_index'), 'fields' => fn($q) => $q->orderBy('order_index'), 'fields.options', 'fields.conditions']);
        $fieldTypes = $this->getFieldTypes();
        return view('admin.forms.builder', compact('form', 'fieldTypes'));
    }

    public function save(SaveFormBuilderRequest $request, Form $form): JsonResponse
    {
        $this->builder->saveFormStructure($form, $request->validated());
        $this->auditLog->log('form.builder_saved', 'Form', $form->id, "Form builder saved for '{$form->title}'");
        return response()->json(['success' => true, 'message' => 'Form saved successfully.']);
    }

    public function addField(Request $request, Form $form): JsonResponse
    {
        $request->validate(['type' => 'required|string']);
        $field = $form->fields()->create([
            'type' => $request->type,
            'label' => $this->getDefaultLabel($request->type),
            'order_index' => $form->fields()->max('order_index') + 1,
            'is_required' => false,
            'is_visible' => true,
            'width' => 'full',
        ]);

        // Add default options for option-type fields
        if (in_array($request->type, ['multiple_choice', 'checkbox', 'dropdown'])) {
            $field->options()->create(['label' => 'Option 1', 'value' => 'option_1', 'order_index' => 0]);
        }

        $field->load('options', 'conditions');
        return response()->json(['success' => true, 'field' => $field]);
    }

    public function deleteField(Form $form, FormField $field): JsonResponse
    {
        abort_if($field->form_id !== $form->id, 403);
        $field->options()->delete();
        $field->conditions()->delete();
        $field->conditionTargets()->delete();
        $field->delete();
        return response()->json(['success' => true]);
    }

    public function reorderFields(Request $request, Form $form): JsonResponse
    {
        $request->validate(['fields' => 'required|array', 'fields.*.id' => 'required|integer', 'fields.*.order_index' => 'required|integer']);
        foreach ($request->fields as $fieldData) {
            $form->fields()->where('id', $fieldData['id'])->update(['order_index' => $fieldData['order_index']]);
        }
        return response()->json(['success' => true]);
    }

    private function getDefaultLabel(string $type): string
    {
        return match($type) {
            'short_text' => 'Short Answer Question',
            'long_text' => 'Paragraph Question',
            'email' => 'Email Address',
            'phone' => 'Phone Number',
            'number' => 'Number Question',
            'multiple_choice' => 'Multiple Choice Question',
            'checkbox' => 'Checkbox Question',
            'dropdown' => 'Dropdown Question',
            'date' => 'Date Question',
            'time' => 'Time Question',
            'file' => 'File Upload',
            'rating' => 'Rating Question',
            'linear_scale' => 'Linear Scale Question',
            'section_header' => 'Section Title',
            'heading' => 'Heading',
            'description' => 'Description Text',
            default => 'Question',
        };
    }

    private function getFieldTypes(): array
    {
        return [
            'basic' => [
                ['type' => 'short_text', 'label' => 'Short Answer', 'icon' => 'fa-minus'],
                ['type' => 'long_text', 'label' => 'Paragraph', 'icon' => 'fa-align-left'],
                ['type' => 'multiple_choice', 'label' => 'Multiple Choice', 'icon' => 'fa-circle-dot'],
                ['type' => 'checkbox', 'label' => 'Checkboxes', 'icon' => 'fa-square-check'],
                ['type' => 'dropdown', 'label' => 'Dropdown', 'icon' => 'fa-caret-down'],
                ['type' => 'date', 'label' => 'Date', 'icon' => 'fa-calendar'],
                ['type' => 'time', 'label' => 'Time', 'icon' => 'fa-clock'],
                ['type' => 'file', 'label' => 'File Upload', 'icon' => 'fa-upload'],
                ['type' => 'number', 'label' => 'Number', 'icon' => 'fa-hashtag'],
                ['type' => 'email', 'label' => 'Email', 'icon' => 'fa-envelope'],
                ['type' => 'phone', 'label' => 'Phone', 'icon' => 'fa-phone'],
            ],
            'advanced' => [
                ['type' => 'rating', 'label' => 'Rating', 'icon' => 'fa-star'],
                ['type' => 'linear_scale', 'label' => 'Linear Scale', 'icon' => 'fa-sliders'],
                ['type' => 'section_header', 'label' => 'Section', 'icon' => 'fa-heading'],
                ['type' => 'heading', 'label' => 'Heading', 'icon' => 'fa-h'],
                ['type' => 'description', 'label' => 'Description', 'icon' => 'fa-align-justify'],
            ],
        ];
    }
}
