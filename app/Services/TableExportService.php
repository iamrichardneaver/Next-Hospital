<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TableExportService
{
    public function supportsXlsx(): bool
    {
        return class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class);
    }

    /**
     * @return array<int, string>
     */
    public function availableFormats(): array
    {
        return $this->supportsXlsx() ? ['csv', 'xlsx'] : ['csv'];
    }

    /**
     * @param  iterable<int, mixed>  $rows
     * @param  array<string, string|callable>  $columns
     */
    public function stream(iterable $rows, array $columns, string $basename, string $format = 'csv'): StreamedResponse
    {
        $format = strtolower($format);

        if (! in_array($format, $this->availableFormats(), true)) {
            $format = 'csv';
        }

        return match ($format) {
            'xlsx' => $this->streamXlsx($rows, $columns, $basename),
            default => $this->streamCsv($rows, $columns, $basename),
        };
    }

    /**
     * @param  iterable<int, mixed>  $rows
     * @param  array<string, string|callable>  $columns
     */
    protected function streamCsv(iterable $rows, array $columns, string $basename): StreamedResponse
    {
        $headers = array_keys($columns);
        $filename = $this->buildFilename($basename, 'csv');

        $callback = function () use ($rows, $columns, $headers) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $headers);

            foreach ($rows as $row) {
                fputcsv($file, $this->resolveRow($row, $columns));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * @param  iterable<int, mixed>  $rows
     * @param  array<string, string|callable>  $columns
     */
    protected function streamXlsx(iterable $rows, array $columns, string $basename): StreamedResponse
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $headers = array_keys($columns);

        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $header);
        }

        $rowIndex = 2;
        foreach ($rows as $row) {
            $values = $this->resolveRow($row, $columns);
            foreach ($values as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex, $value);
            }
            $rowIndex++;
        }

        $filename = $this->buildFilename($basename, 'xlsx');

        $callback = function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * @param  array<string, string|callable>  $columns
     * @return array<int, string>
     */
    protected function resolveRow(mixed $row, array $columns): array
    {
        return array_map(function ($column) use ($row) {
            if (is_callable($column)) {
                return $this->stringify($column($row));
            }

            return $this->stringify(data_get($row, $column, ''));
        }, $columns);
    }

    protected function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn ($v) => $this->stringify($v), $value));
        }

        return (string) $value;
    }

    protected function buildFilename(string $basename, string $extension): string
    {
        return Str::slug($basename).'-'.now()->format('Y-m-d-His').'.'.$extension;
    }
}
