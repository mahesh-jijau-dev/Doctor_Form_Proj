<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $form->title }} - Responses</title>
    <style>
        @page { margin: 26px 28px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        .masthead { padding: 0 0 14px; border-bottom: 3px solid #0f766e; }
        .brand { color: #0f766e; font-size: 10px; font-weight: bold; letter-spacing: 1.6px; text-transform: uppercase; }
        h1 { margin: 6px 0 4px; font-size: 21px; color: #0f172a; }
        .meta { color: #64748b; font-size: 9px; }
        .summary { width: 100%; margin: 16px 0 20px; border-collapse: collapse; }
        .summary td { width: 33.33%; padding: 9px 11px; background: #f1f5f9; border-right: 4px solid #fff; }
        .summary td:last-child { border-right: 0; }
        .summary-label { color: #64748b; font-size: 8px; text-transform: uppercase; letter-spacing: .8px; }
        .summary-value { margin-top: 3px; color: #0f172a; font-size: 13px; font-weight: bold; }
        .response { padding: 0 0 16px; margin-bottom: 18px; border-bottom: 2px solid #cbd5e1; page-break-inside: avoid; }
        .response:last-child { border-bottom: 0; }
        .response-title { margin: 0 0 8px; color: #0f766e; font-size: 12px; font-weight: bold; }
        .response-meta { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .response-meta td { width: 33.33%; padding: 6px 8px; background: #f1f5f9; border-right: 3px solid #fff; }
        .response-meta td:last-child { border-right: 0; }
        .response-meta-label { color: #64748b; font-size: 7px; text-transform: uppercase; letter-spacing: .6px; }
        .response-meta-value { margin-top: 2px; color: #0f172a; font-size: 9px; }
        table.answers { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .answers th { padding: 7px; color: #fff; background: #0f766e; text-align: left; font-size: 8px; }
        .answers td { padding: 6px 7px; border: 1px solid #e2e8f0; vertical-align: top; word-wrap: break-word; }
        .answers td:first-child { width: 28%; font-weight: bold; }
        .answers tr:nth-child(even) td { background: #f8fafc; }
        .empty { padding: 30px; color: #64748b; text-align: center; border: 1px solid #e2e8f0; }
        .footer { margin-top: 14px; color: #94a3b8; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <div class="masthead">
        <div class="brand">MediForm · Response Export</div>
        <h1>{{ $form->title }}</h1>
        <div class="meta">Generated {{ $generatedAt->format('M d, Y H:i') }}</div>
    </div>

    <table class="summary">
        <tr>
            <td><div class="summary-label">Total Responses</div><div class="summary-value">{{ count($exportData['rows']) }}</div></td>
            <td><div class="summary-label">Form Fields</div><div class="summary-value">{{ max(count($exportData['headers']) - 3, 0) }}</div></td>
            <td><div class="summary-label">Export Date</div><div class="summary-value">{{ $generatedAt->format('M d, Y') }}</div></td>
        </tr>
    </table>

    @if(count($exportData['rows']))
        @foreach($exportData['rows'] as $row)
            @php
                $responseHeaders = array_slice($exportData['headers'], 3);
                $responseAnswers = array_slice($row, 3);
            @endphp
            <div class="response">
                <h2 class="response-title">Response #{{ $row[0] ?? '-' }}</h2>
                <table class="response-meta">
                    <tr>
                        <td><div class="response-meta-label">Response ID</div><div class="response-meta-value">{{ $row[0] ?? '-' }}</div></td>
                        <td><div class="response-meta-label">Submitted At</div><div class="response-meta-value">{{ $row[1] ?? '-' }}</div></td>
                        <td><div class="response-meta-label">Doctor</div><div class="response-meta-value">{{ $row[2] ?? '-' }}</div></td>
                    </tr>
                </table>
                <table class="answers">
                    <thead><tr><th>Field</th><th>Answer</th></tr></thead>
                    <tbody>
                        @foreach($responseHeaders as $index => $header)
                            <tr>
                                <td>{{ $header }}</td>
                                <td>{{ $responseAnswers[$index] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <div class="empty">No responses are available for this form.</div>
    @endif

    <div class="footer">Confidential medical response data · MediForm</div>
</body>
</html>
