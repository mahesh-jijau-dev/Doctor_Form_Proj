<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormResponse;
use App\Models\FormResponseValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormResponseService
{
    /**
     * Store a complete form response along with all field values.
     */
    public function storeResponse(Form $form, array $data, Request $request, ?int $doctorId = null): FormResponse
    {
        return DB::transaction(function () use ($form, $data, $request, $doctorId) {
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
                    $file                              = $request->file($fieldKey);
                    $path                              = $file->store('responses/files', 'local');
                    $valueData['file_path']            = $path;
                    $valueData['file_original_name']   = $file->getClientOriginalName();
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
     * Build a structured array of headers + rows suitable for CSV export.
     */
    public function getExportData(Form $form, ?int $doctorId = null): array
    {
        $query = FormResponse::with(['values', 'assignedDoctor'])
            ->where('form_id', $form->id);

        if ($doctorId) {
            $query->where('assigned_doctor_id', $doctorId);
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
}
