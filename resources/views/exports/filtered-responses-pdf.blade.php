<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Filtered Responses</title>
    <style>
        @page { margin: 26px 28px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        .masthead { padding-bottom: 14px; border-bottom: 3px solid #0f766e; }
        .brand { color: #0f766e; font-size: 10px; font-weight: bold; letter-spacing: 1.6px; text-transform: uppercase; }
        h1 { margin: 6px 0 4px; font-size: 21px; color: #0f172a; }
        .meta { color: #64748b; font-size: 9px; }
        .summary { width: 100%; margin: 16px 0; border-collapse: collapse; }
        .summary td { width: 33.33%; padding: 9px 11px; background: #f1f5f9; border-right: 4px solid #fff; }
        .summary td:last-child { border-right: 0; }
        .summary-label { color: #64748b; font-size: 8px; text-transform: uppercase; letter-spacing: .8px; }
        .summary-value { margin-top: 3px; color: #0f172a; font-size: 13px; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .data th { padding: 7px 6px; color: #fff; background: #0f766e; text-align: left; font-size: 7px; }
        .data td { padding: 6px; border: 1px solid #e2e8f0; vertical-align: top; word-wrap: break-word; }
        .data tr:nth-child(even) td { background: #f8fafc; }
        .empty { padding: 30px; color: #64748b; text-align: center; border: 1px solid #e2e8f0; }
        .footer { margin-top: 14px; color: #94a3b8; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <div class="masthead"><div class="brand">MediForm · Filtered Response Export</div><h1>Responses</h1><div class="meta">Generated {{ now()->format('M d, Y H:i') }}</div></div>
    <table class="summary"><tr>
        <td><div class="summary-label">Total Responses</div><div class="summary-value">{{ count($exportData['rows']) }}</div></td>
        <td><div class="summary-label">Forms</div><div class="summary-value">{{ count(collect($exportData['rows'])->pluck(1)->unique()) }}</div></td>
        <td><div class="summary-label">Export Date</div><div class="summary-value">{{ now()->format('M d, Y') }}</div></td>
    </tr></table>
    @if(count($exportData['rows']))
        <table class="data"><thead><tr>@foreach($exportData['headers'] as $header)<th>{{ $header }}</th>@endforeach</tr></thead><tbody>
            @foreach($exportData['rows'] as $row)<tr>@foreach($row as $value)<td>{{ is_array($value) ? implode(', ', $value) : ($value ?: '-') }}</td>@endforeach</tr>@endforeach
        </tbody></table>
    @else <div class="empty">No responses match the selected filters.</div> @endif
    <div class="footer">Confidential medical response data · MediForm</div>
</body>
</html>