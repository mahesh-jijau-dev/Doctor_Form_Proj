<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Stream a CSV download containing all response data for a form.
     *
     * @param  array  $exportData  Result of FormResponseService::getExportData()
     *                             Shape: ['headers' => [...], 'rows' => [[...]]]
     */
    public function exportResponsesCsv(Form $form, array $exportData): StreamedResponse
    {
        $filename = 'responses_'.$form->id.'_'.now()->format('Ymd_His').'.csv';

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
        $filename = 'responses_'.$form->id.'_'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('exports.responses-pdf', [
            'form' => $form,
            'exportData' => $exportData,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download($filename);
    }

    public function exportFilteredResponsesCsv(array $exportData): StreamedResponse
    {
        $filename = 'filtered_responses_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($exportData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $exportData['headers']);
            foreach ($exportData['rows'] as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportFilteredResponsesPdf(array $exportData)
    {
        $filename = 'filtered_responses_'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('exports.filtered-responses-pdf', compact('exportData'))
            ->setPaper('a4', 'landscape')->download($filename);
    }

    public function exportResponseCsv(FormResponse $response): StreamedResponse
    {
        $filename = 'response_'.$response->id.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($response) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Response ID', $response->id]);
            fputcsv($handle, ['Form', $response->form?->title ?? '']);
            fputcsv($handle, ['Submitted By', $response->submitted_by_name ?? 'Anonymous']);
            fputcsv($handle, ['Email', $response->submitted_by_email ?? '']);
            fputcsv($handle, ['Doctor', $response->assignedDoctor?->name ?? 'N/A']);
            fputcsv($handle, ['Submitted At', $response->submitted_at?->format('Y-m-d H:i:s') ?? '']);
            $hasMeaningfulScore = $response->quiz_max_score > 0
                && ($response->assessment_result['score_is_meaningful'] ?? true);
            if ($hasMeaningfulScore) {
                fputcsv($handle, [
                    'Total Score',
                    rtrim(rtrim(number_format((float) $response->quiz_score, 2), '0'), '.')
                        .' / '
                        .rtrim(rtrim(number_format((float) $response->quiz_max_score, 2), '0'), '.'),
                ]);
                if (! $response->assessment_result && $response->quiz_interpretation) {
                    fputcsv($handle, ['Assessment Result', $response->quiz_interpretation['title']]);
                    fputcsv($handle, ['Interpretation', $response->quiz_interpretation['summary']]);
                }
            }
            if ($response->assessment_result) {
                $assessmentResult = $response->assessment_result;
                $result = $assessmentResult['result'] ?? [];
                if (! empty($result['title'])) {
                    fputcsv($handle, ['Assessment Result', $result['title']]);
                    fputcsv($handle, ['Interpretation', $result['summary'] ?? '']);
                    if (! empty($result['recommendation'])) {
                        fputcsv($handle, ['Recommendation', $result['recommendation']]);
                    }
                }
                if (($assessmentResult['percentage'] ?? null) !== null) {
                    fputcsv($handle, ['Percentage of available points', $assessmentResult['percentage'].'%']);
                }
                if (array_key_exists('highest_groups', $result)) {
                    fputcsv($handle, ['Highest-scoring systems', implode(', ', $result['highest_groups'])]);
                }
                foreach (['sections' => 'Section total', 'groups' => 'System / category total'] as $collection => $label) {
                    if ($collection === 'sections' && ! ($assessmentResult['score_is_meaningful'] ?? in_array($assessmentResult['type'] ?? null, ['score_bands', 'dhatu_sarata'], true))) {
                        continue;
                    }
                    foreach ($assessmentResult[$collection] ?? [] as $group) {
                        fputcsv($handle, [$label, $group['label'], $group['score'], $group['max_score']]);
                    }
                }
                foreach ($result['profiles'] ?? [] as $profile) {
                    fputcsv($handle, [
                        'Dhatu profile',
                        $profile['label'],
                        $profile['score'],
                        $profile['status'],
                        $profile['current_condition'] ?? '',
                    ]);
                }
                foreach ($assessmentResult['categories'] ?? [] as $category => $count) {
                    fputcsv($handle, ['Category count', $category, $count]);
                }
                foreach ($assessmentResult['section_categories'] ?? [] as $section) {
                    foreach ($section['categories'] ?? [] as $category) {
                        fputcsv($handle, [
                            'Section category count',
                            $section['label'],
                            $category['label'],
                            $category['count'],
                        ]);
                    }
                }
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Field', 'Answer', 'Correct Answer', 'Score']);

            foreach ($response->values as $value) {
                $score = $hasMeaningfulScore || ! $response->assessment_result
                    ? $value->getAwardedScore()
                    : null;
                fputcsv($handle, [
                    $value->field_label,
                    $value->getDisplayValue(),
                    $value->getCorrectAnswerDisplay() ?? '',
                    $score === null ? '' : rtrim(rtrim(number_format($score, 2), '0'), '.'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportResponsePdf(FormResponse $response)
    {
        $filename = 'response_'.$response->id.'_'.now()->format('Ymd_His').'.pdf';

        return Pdf::loadView('exports.response-pdf', [
            'response' => $response,
            'generatedAt' => now(),
        ])->setPaper('a4')->download($filename);
    }
}
