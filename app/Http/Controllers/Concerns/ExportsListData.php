<?php

namespace App\Http\Controllers\Concerns;

use App\Services\TableExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExportsListData
{
    /**
     * @param  iterable<int, mixed>  $rows
     * @param  array<string, string|callable>  $columns
     */
    protected function exportTableData(
        Request $request,
        iterable $rows,
        array $columns,
        string $basename,
        ?string $permission = null
    ): StreamedResponse {
        if ($permission && ! auth()->user()->can($permission)) {
            abort(403, 'Unauthorized to export this data.');
        }

        $format = $request->get('format', 'csv');

        return app(TableExportService::class)->stream($rows, $columns, $basename, $format);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  array<string, string|callable>  $columns
     */
    protected function exportFromQuery(
        Request $request,
        $query,
        array $columns,
        string $basename,
        ?string $permission = null
    ): StreamedResponse {
        return $this->exportTableData($request, $query->get(), $columns, $basename, $permission);
    }

    protected function formatExportDate(mixed $date, string $format = 'Y-m-d'): string
    {
        if (! $date) {
            return '';
        }

        if ($date instanceof \DateTimeInterface) {
            return $date->format($format);
        }

        try {
            return \Carbon\Carbon::parse($date)->format($format);
        } catch (\Exception) {
            return (string) $date;
        }
    }

    protected function formatExportUserName(?object $user): string
    {
        if (! $user) {
            return '';
        }

        return trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
    }
}
