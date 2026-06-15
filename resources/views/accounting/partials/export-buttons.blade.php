@php
    $exportRoute = $exportRoute ?? url()->current();
    $queryParams = collect($params ?? request()->query())
        ->except(['page', 'export'])
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->all();
@endphp

<div class="btn-group">
    <a href="{{ $exportRoute }}?{{ http_build_query(array_merge($queryParams, ['export' => 'csv'])) }}"
       class="btn btn-outline-primary btn-sm">
        <i class="bi bi-filetype-csv"></i> Export CSV
    </a>
    <a href="{{ $exportRoute }}?{{ http_build_query(array_merge($queryParams, ['export' => 'pdf'])) }}"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-filetype-pdf"></i> Export PDF
    </a>
</div>
