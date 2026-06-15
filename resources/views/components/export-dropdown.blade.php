@php
    $exportRoute = $exportRoute ?? null;
    $params = $params ?? request()->query();
    $permission = $permission ?? null;
    $formats = $formats ?? app(\App\Services\TableExportService::class)->availableFormats();
    $label = $label ?? 'Export';
    $btnClass = $btnClass ?? 'btn btn-outline-success';
    $btnSize = $btnSize ?? '';

    $queryParams = collect($params)
        ->except(['page', 'format'])
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->all();
@endphp

@if($exportRoute && (!$permission || auth()->user()->can($permission)))
<div class="btn-group {{ $btnSize }}">
    <button type="button" class="{{ $btnClass }} dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-download"></i> {{ $label }}
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @foreach($formats as $format)
        <li>
            <a class="dropdown-item" href="{{ $exportRoute }}?{{ http_build_query(array_merge($queryParams, ['format' => $format])) }}">
                <i class="bi bi-filetype-{{ $format === 'xlsx' ? 'xlsx' : 'csv' }} me-2"></i>
                Export {{ strtoupper($format) }}
            </a>
        </li>
        @endforeach
        @if(!empty($extraLinks))
            @foreach($extraLinks as $link)
            <li>
                <a class="dropdown-item" href="{{ $link['url'] }}">
                    <i class="bi {{ $link['icon'] ?? 'bi-download' }} me-2"></i>
                    {{ $link['label'] }}
                </a>
            </li>
            @endforeach
        @endif
    </ul>
</div>
@endif
