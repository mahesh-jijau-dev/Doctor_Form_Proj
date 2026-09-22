<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $response->form?->title ?? 'Response' }} - Response {{ $response->id }}</title>
    <style>
        @page { margin: 28px 30px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .masthead { padding-bottom: 14px; border-bottom: 3px solid #0f766e; }
        .brand { color: #0f766e; font-size: 10px; font-weight: bold; letter-spacing: 1.6px; text-transform: uppercase; }
        h1 { margin: 6px 0 4px; font-size: 21px; color: #0f172a; }
        .meta { color: #64748b; font-size: 9px; }
        .details { width: 100%; margin: 16px 0; border-collapse: collapse; }
        .details td { width: 50%; padding: 7px 10px; border: 1px solid #e2e8f0; }
        .label { color: #64748b; font-size: 8px; text-transform: uppercase; letter-spacing: .8px; }
        .value { margin-top: 3px; color: #0f172a; font-size: 10px; }
        table.answers { width: 100%; border-collapse: collapse; }
        .answers th { padding: 8px 7px; color: #fff; background: #0f766e; text-align: left; font-size: 8px; }
        .answers td { padding: 8px 7px; border: 1px solid #e2e8f0; vertical-align: top; word-wrap: break-word; }
        .answers tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 14px; color: #94a3b8; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <div class="masthead">
        <div class="brand">MediForm · Individual Response</div>
        <h1>{{ $response->form?->title ?? 'Response Detail' }}</h1>
        <div class="meta">Response #{{ $response->id }} · Generated {{ $generatedAt->format('M d, Y H:i') }}</div>
    </div>

    <table class="details">
        <tr>
            <td><div class="label">Submitted By</div><div class="value">{{ $response->submitted_by_name ?? 'Anonymous' }}</div></td>
            <td><div class="label">Email</div><div class="value">{{ $response->submitted_by_email ?? '-' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Doctor</div><div class="value">{{ $response->assignedDoctor?->name ?? '-' }}</div></td>
            <td><div class="label">Submitted At</div><div class="value">{{ $response->submitted_at?->format('M d, Y H:i') ?? '-' }}</div></td>
        </tr>
    </table>

    <table class="answers">
        <thead><tr><th>Field</th><th>Answer</th></tr></thead>
        <tbody>
            @forelse($response->values as $value)
                <tr><td>{{ $value->field_label }}</td><td>{{ $value->getDisplayValue() ?: '-' }}</td></tr>
            @empty
                <tr><td colspan="2">No field values recorded for this response.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Confidential medical response data · MediForm</div>
</body>
</html>