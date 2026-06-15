<?php

namespace App\Services;

use App\Models\Branch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingExportService
{
    public function __construct(
        protected SettingsService $settingsService
    ) {}

    public function resolveExport(?string $export): ?string
    {
        return in_array($export, ['csv', 'pdf'], true) ? $export : null;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, scalar|null>>  $rows
     */
    public function streamCsv(array $headers, iterable $rows, string $basename): StreamedResponse
    {
        $filename = Str::slug($basename) . '-' . now()->format('Y-m-d-His') . '.csv';

        $callback = function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function downloadPdf(string $view, array $data, string $filename)
    {
        $payload = array_merge([
            'branding' => $this->settingsService->getBrandingSettings(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'generated_by' => trim(
                (auth()->user()->first_name ?? '') . ' ' . (auth()->user()->last_name ?? '')
            ) ?: (auth()->user()->name ?? 'System'),
        ], $data);

        return Pdf::loadView($view, $payload)->download($filename);
    }

    public function buildFilterSummary(
        ?int $branchId,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $asOfDate = null,
        ?string $period = null
    ): string {
        $parts = [];

        if ($startDate && $endDate) {
            $parts[] = "Period: {$startDate} to {$endDate}";
        } elseif ($asOfDate) {
            $parts[] = 'As of: ' . $asOfDate;
        }

        if ($period) {
            $parts[] = 'Grouping: ' . ucfirst($period);
        }

        if ($branchId) {
            $branch = Branch::find($branchId);
            $parts[] = 'Branch: ' . ($branch?->name ?? $branchId);
        } else {
            $parts[] = 'Branch: All branches';
        }

        return implode(' | ', $parts);
    }

    /**
     * @param  array<int, array<string, scalar|null>>  $sections
     */
    public function pdfFilename(string $basename, ?string $startDate = null, ?string $endDate = null): string
    {
        $slug = Str::slug($basename);

        if ($startDate && $endDate) {
            return "{$slug}-{$startDate}-to-{$endDate}.pdf";
        }

        return "{$slug}-" . now()->format('Y-m-d') . '.pdf';
    }
}
