<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle ?? 'Accounting Report' }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #333; margin: 0; padding: 16px; }
        h2 { font-size: 13px; color: #1e3a5f; margin: 18px 0 8px; border-bottom: 1px solid #dee2e6; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #dee2e6; padding: 6px 8px; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        .text-end { text-align: right; }
        .text-muted { color: #666; font-size: 9px; }
        .summary-box { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; margin-bottom: 14px; }
        .summary-box strong { color: #1e3a5f; }
        tfoot th, tfoot td { background: #eef2f7; font-weight: bold; }
    </style>
</head>
<body>
    @include('pdf.branding-header', [
        'branding' => $branding ?? [],
        'branch' => $branch ?? null,
        'documentTitle' => $documentTitle ?? 'Accounting Report',
        'documentDate' => $filterSummary ?? null,
    ])

    @if(!empty($summaryLines))
        <div class="summary-box">
            @foreach($summaryLines as $line)
                <div><strong>{{ $line['label'] }}:</strong> {{ $line['value'] }}</div>
            @endforeach
        </div>
    @endif

    @foreach($sections ?? [] as $section)
        <h2>{{ $section['title'] ?? 'Section' }}</h2>
        <table>
            @if(!empty($section['headers']))
                <thead>
                    <tr>
                        @foreach($section['headers'] as $header)
                            <th class="{{ ($section['align'][$loop->index] ?? '') === 'right' ? 'text-end' : '' }}">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody>
                @forelse($section['rows'] ?? [] as $row)
                    <tr>
                        @foreach($row as $cellIndex => $cell)
                            <td class="{{ ($section['align'][$cellIndex] ?? '') === 'right' ? 'text-end' : '' }}">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($section['headers'] ?? [1]) }}">No data for this report.</td></tr>
                @endforelse
            </tbody>
            @if(!empty($section['footer']))
                <tfoot>
                    <tr>
                        @foreach($section['footer'] as $cellIndex => $cell)
                            <th class="{{ ($section['align'][$cellIndex] ?? '') === 'right' ? 'text-end' : '' }}">{{ $cell }}</th>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    @endforeach

    <p class="text-muted">Generated {{ $generated_at ?? now()->format('Y-m-d H:i:s') }} by {{ $generated_by ?? 'System' }}</p>
</body>
</html>
