<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormResponse;
use App\Models\FormResponseValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FormResponseService
{
    /**
     * Store a complete form response along with all field values.
     */
    public function storeResponse(Form $form, array $data, Request $request, ?int $doctorId = null): FormResponse
    {
        return DB::transaction(function () use ($form, $data, $request, $doctorId) {
            [$quizScore, $quizMaxScore] = $this->calculateQuizScore($form, $data);

            $response = FormResponse::create([
                'form_id'             => $form->id,
                'form_version'        => $form->version,
                'assigned_doctor_id'  => $doctorId,
                'submitted_by_name'   => $data['_name'] ?? null,
                'submitted_by_email'  => $data['_email'] ?? null,
                'ip_address'          => $request->ip(),
                'user_agent'          => $request->userAgent(),
                'submitted_at'        => now(),
                'is_complete'         => true,
                'quiz_score'          => $quizMaxScore > 0 ? $quizScore : null,
                'quiz_max_score'      => $quizMaxScore > 0 ? $quizMaxScore : null,
            ]);

            $fields = $form->fields()->with('options')->get();

            foreach ($fields as $field) {
                // Skip purely presentational fields
                if ($field->isLayoutType()) {
                    continue;
                }

                $fieldKey = 'field_' . $field->id;
                $rawValue = $data[$fieldKey] ?? null;

                $valueData = [
                    'response_id' => $response->id,
                    'field_id'    => $field->id,
                    'field_label' => $field->label,
                    'field_type'  => $field->type,
                ];

                if ($field->type === 'file' && $request->hasFile($fieldKey)) {
                    $file = $request->file($fieldKey);
                    $directory = 'uploads/responses/' . Str::slug($form->title) . '/response-' . $response->id;
                    $directoryPath = public_path($directory);
                    File::ensureDirectoryExists($directoryPath);

                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = strtolower($file->getClientOriginalExtension());
                    $filename = (Str::slug($originalName) ?: 'upload')
                        . '-field-' . $field->id
                        . '-' . Str::lower(Str::random(8))
                        . '.' . $extension;

                    $file->move($directoryPath, $filename);
                    $valueData['file_path'] = $directory . '/' . $filename;
                    $valueData['file_original_name'] = $file->getClientOriginalName();
                } elseif (is_array($rawValue)) {
                    $valueData['values'] = $rawValue;
                } else {
                    $valueData['value'] = $rawValue;
                }

                FormResponseValue::create($valueData);
            }

            return $response;
        });
    }

    /**
     * Calculate points only for configured choice questions; checkbox questions
     * require the respondent's complete selection to match the answer key.
     *
     * @return array{0: float, 1: float}
     */
    private function calculateQuizScore(Form $form, array $data): array
    {
        $score = 0.0;
        $maxScore = 0.0;
        $fields = $form->fields()->with('options')->get();

        foreach ($fields as $field) {
            if (! in_array($field->type, ['multiple_choice', 'checkbox', 'dropdown'], true)) {
                continue;
            }

            $settings = $field->settings ?? [];
            $correctAnswers = array_values(array_unique(array_map(
                'strval',
                $settings['correct_answers'] ?? []
            )));
            $points = (float) ($settings['points'] ?? 1);

            if ($correctAnswers === [] || $points <= 0) {
                continue;
            }

            $validOptionValues = $field->options
                ->map(fn ($option) => (string) ($option->value ?: $option->label))
                ->all();
            if (array_diff($correctAnswers, $validOptionValues) !== []) {
                continue;
            }

            $maxScore += $points;
            $rawAnswer = $data['field_' . $field->id] ?? null;
            $answers = is_array($rawAnswer)
                ? $rawAnswer
                : ($rawAnswer === null || $rawAnswer === '' ? [] : [$rawAnswer]);
            $answers = array_values(array_unique(array_map('strval', $answers)));

            sort($correctAnswers, SORT_STRING);
            sort($answers, SORT_STRING);

            if ($answers === $correctAnswers) {
                $score += $points;
            }
        }

        return [$score, $maxScore];
    }

    /**
     * Build a structured array of headers + rows suitable for CSV export.
     */
    public function getExportData(Form $form, ?int $doctorId = null, array $filters = []): array
    {
        $query = FormResponse::with(['values', 'assignedDoctor'])
            ->where('form_id', $form->id);

        $doctorId ??= ! empty($filters['doctor_id']) ? (int) $filters['doctor_id'] : null;

        if ($doctorId) {
            $query->whereHas('form.assignments', function ($assignmentQuery) use ($doctorId) {
                $assignmentQuery->where('doctor_id', $doctorId)
                    ->where('is_active', true);
            });
        }

        if (! empty($filters['from'])) {
            $query->whereDate('submitted_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('submitted_at', '<=', $filters['to']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('submitted_by_name', 'like', "%{$search}%")
                    ->orWhere('submitted_by_email', 'like', "%{$search}%");
            });
        }

        $responses = $query->latest()->get();

        $fields = $form->fields()
            ->whereNotIn('type', ['section_header', 'heading', 'description', 'image'])
            ->get();

        $headers = ['ID', 'Submitted At', 'Doctor'];
        foreach ($fields as $field) {
            $headers[] = $field->label;
        }

        $rows = [];
        foreach ($responses as $response) {
            $row = [
                $response->id,
                $response->submitted_at?->format('Y-m-d H:i:s'),
                $response->assignedDoctor?->name ?? 'N/A',
            ];

            foreach ($fields as $field) {
                $val   = $response->values->firstWhere('field_id', $field->id);
                $row[] = $val ? $val->getDisplayValue() : '';
            }

            $rows[] = $row;
        }

        return compact('headers', 'rows');
    }

    public function getFilteredExportData(?int $formId = null, ?int $doctorId = null, array $filters = []): array
    {
        $query = FormResponse::with(['form.fields', 'values', 'assignedDoctor'])->latest('submitted_at');

        if ($formId) {
            $query->where('form_id', $formId);
        }
        if ($doctorId) {
            $query->whereHas('form.assignments', function ($assignmentQuery) use ($doctorId) {
                $assignmentQuery->where('doctor_id', $doctorId)->where('is_active', true);
            });
        }
        if (! empty($filters['doctor_id'])) {
            $doctorId = $filters['doctor_id'];
            $query->where(function ($doctorQuery) use ($doctorId) {
                $doctorQuery->where('assigned_doctor_id', $doctorId)
                    ->orWhereHas('form.assignments', function ($assignmentQuery) use ($doctorId) {
                        $assignmentQuery->where('doctor_id', $doctorId)
                            ->where('is_active', true);
                    });
            });
        }
        if (! empty($filters['from'])) {
            $query->whereDate('submitted_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('submitted_at', '<=', $filters['to']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('submitted_by_name', 'like', "%{$search}%")
                    ->orWhere('submitted_by_email', 'like', "%{$search}%");
            });
        }

        $responses = $query->get();
        $fields = $responses->flatMap(fn ($response) => $response->form?->fields ?? collect())
            ->whereNotIn('type', ['section_header', 'heading', 'description', 'image'])
            ->unique('id')->values();

        $headers = ['ID', 'Form', 'Submitted At', 'Doctor', 'Patient'];
        foreach ($fields as $field) {
            $headers[] = $field->label;
        }

        $rows = $responses->map(function ($response) use ($fields) {
            $row = [$response->id, $response->form?->title ?? 'N/A', $response->submitted_at?->format('Y-m-d H:i:s'), $response->assignedDoctor?->name ?? 'N/A', $response->submitted_by_name ?? 'Anonymous'];
            foreach ($fields as $field) {
                $value = $response->values->firstWhere('field_id', $field->id);
                $row[] = $value ? $value->getDisplayValue() : '';
            }
            return $row;
        })->all();

        return compact('headers', 'rows');
    }
}
