<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Form;
use App\Models\FormResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Stream a CSV download containing all response data for a form.
     *
     * @param  Form   $form
     * @param  array  $exportData  Result of FormResponseService::getExportData()
     *                             Shape: ['headers' => [...], 'rows' => [[...]]]
     */
    public function exportResponsesCsv(Form $form, array $exportData): StreamedResponse
    {
        $filename = 'responses_' . $form->id . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($exportData) {
            $handle = fopen('php://output', 'w');

            // Write header row
            fputcsv($handle, $exportData['headers']);

            // Write data rows
            foreach ($exportData['rows'] as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportResponsesPdf(Form $form, array $exportData)
    {
        $filename = 'responses_' . $form->id . '_' . now()->format('Ymd_His') . '.pdf';

        return Pdf::loadView('exports.responses-pdf', [
            'form' => $form,
            'exportData' => $exportData,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download($filename);
    }

    public function exportFilteredResponsesCsv(array $exportData): StreamedResponse
    {
        $filename = 'filtered_responses_' . now()->format('Ymd_His') . '.csv';
        return response()->streamDownload(function () use ($exportData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $exportData['headers']);
            foreach ($exportData['rows'] as $row) fputcsv($handle, $row);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportFilteredResponsesPdf(array $exportData)
    {
        $filename = 'filtered_responses_' . now()->format('Ymd_His') . '.pdf';
        return Pdf::loadView('exports.filtered-responses-pdf', compact('exportData'))
            ->setPaper('a4', 'landscape')->download($filename);
    }

    public function exportResponseCsv(FormResponse $response): StreamedResponse
    {
        $filename = 'response_' . $response->id . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($response) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Response ID', $response->id]);
            fputcsv($handle, ['Form', $response->form?->title ?? '']);
            fputcsv($handle, ['Submitted By', $response->submitted_by_name ?? 'Anonymous']);
            fputcsv($handle, ['Email', $response->submitted_by_email ?? '']);
            fputcsv($handle, ['Doctor', $response->assignedDoctor?->name ?? 'N/A']);
            fputcsv($handle, ['Submitted At', $response->submitted_at?->format('Y-m-d H:i:s') ?? '']);
            fputcsv($handle, []);
            fputcsv($handle, ['Field', 'Answer']);

            foreach ($response->values as $value) {
                fputcsv($handle, [$value->field_label, $value->getDisplayValue()]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportResponsePdf(FormResponse $response)
    {
        $filename = 'response_' . $response->id . '_' . now()->format('Ymd_His') . '.pdf';

        return Pdf::loadView('exports.response-pdf', [
            'response' => $response,
            'generatedAt' => now(),
        ])->setPaper('a4')->download($filename);
    }
}
