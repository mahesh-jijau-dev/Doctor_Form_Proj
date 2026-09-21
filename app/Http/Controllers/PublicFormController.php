<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormResponse;
use App\Services\FormResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicFormController extends Controller
{
    public function __construct(private readonly FormResponseService $responseService)
    {
    }

    public function show(Form $form)
    {
        abort_unless($form->status === 'published', 404, 'This form is not publicly available.');

        $form->load(['fields.options', 'fields.conditions', 'sections']);

        return view('public.form', compact('form'));
    }

    public function submit(Request $request, Form $form)
    {
        abort_unless($form->status === 'published', 404, 'This form is not publicly available.');

        if (! $form->allow_multiple_responses) {
            $submitterEmail = trim((string) ($request->input('_email') ?: $request->input('email') ?: ''));

            if ($submitterEmail !== '' && FormResponse::where('form_id', $form->id)
                ->whereRaw('LOWER(submitted_by_email) = ?', [strtolower($submitterEmail)])
                ->exists()) {
                return back()->withErrors([
                    'email' => 'A response for this email has already been submitted for this form.',
                ])->withInput();
            }
        }

        $rules = $this->buildValidationRules($form);

        if (! empty($rules)) {
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
        }

        $this->responseService->storeResponse($form, $request->all(), $request);

        return view('public.thank-you', compact('form'));
    }

    protected function buildValidationRules(Form $form): array
    {
        $rules = [];

        foreach ($form->fields as $field) {
            if ($field->isLayoutType() || ! $field->is_visible) {
                continue;
            }

            $fieldName = 'field_' . $field->id;
            $fieldRules = [];

            if ($field->is_required) {
                $fieldRules[] = 'required';
            }

            if ($field->type === 'email') {
                $fieldRules[] = 'email';
            }

            if ($field->type === 'number') {
                $fieldRules[] = 'numeric';
            }

            if ($field->type === 'file') {
                $fieldRules[] = 'file';
                $fieldRules[] = 'mimes:pdf,doc,docx,txt,rtf,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,webp';
                $fieldRules[] = 'max:10240';
            }

            if (! empty($field->validation_rules) && is_array($field->validation_rules)) {
                foreach ($field->validation_rules as $key => $value) {
                    if ($key === 'required' && $value) {
                        $fieldRules[] = 'required';
                        continue;
                    }

                    if ($key === 'min' && is_numeric($value)) {
                        $fieldRules[] = 'min:' . $value;
                    }

                    if ($key === 'max' && is_numeric($value)) {
                        $fieldRules[] = 'max:' . $value;
                    }

                    if ($key === 'minLength' && is_numeric($value)) {
                        $fieldRules[] = 'min:' . $value;
                    }

                    if ($key === 'maxLength' && is_numeric($value)) {
                        $fieldRules[] = 'max:' . $value;
                    }
                }
            }

            if (! empty($fieldRules)) {
                $rules[$fieldName] = $fieldRules;
            }
        }

        return $rules;
    }
}
