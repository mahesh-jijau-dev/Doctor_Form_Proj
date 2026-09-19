<?php

namespace App\Services;

use App\Models\Form;
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
}
