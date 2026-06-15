@extends('layouts.app')

@section('title', 'Compare Lab Results')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-arrow-left-right"></i> Compare Lab Results
            </h1>
            <p class="text-secondary mb-0">{{ $labRequest1->patient->full_name }} - {{ $labRequest1->patient->patient_number }}</p>
        </div>
        <div>
            <a href="{{ route('lab.archive.patient-history', $labRequest1->patient) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Patient History
            </a>
        </div>
    </div>

    <!-- Request Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">First Test</h6>
                </div>
                <div class="card-body">
                    <p><strong>Request #:</strong> {{ $labRequest1->request_number }}</p>
                    <p><strong>Test:</strong> {{ $labRequest1->template->template_name ?? 'Unknown' }}</p>
                    <p><strong>Date:</strong> {{ $labRequest1->completed_at ? $labRequest1->completed_at->format('M d, Y h:i A') : 'Not recorded' }}</p>
                    <p><strong>Doctor:</strong> {{ $labRequest1->doctor->first_name ?? 'N/A' }} {{ $labRequest1->doctor->last_name ?? '' }}</p>
                    <p><strong>Results:</strong> {{ $labRequest1->results->count() }} parameters</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">Second Test</h6>
                </div>
                <div class="card-body">
                    <p><strong>Request #:</strong> {{ $labRequest2->request_number }}</p>
                    <p><strong>Test:</strong> {{ $labRequest2->template->template_name ?? 'Unknown' }}</p>
                    <p><strong>Date:</strong> {{ $labRequest2->completed_at ? $labRequest2->completed_at->format('M d, Y h:i A') : 'Not recorded' }}</p>
                    <p><strong>Doctor:</strong> {{ $labRequest2->doctor->first_name ?? 'N/A' }} {{ $labRequest2->doctor->last_name ?? '' }}</p>
                    <p><strong>Results:</strong> {{ $labRequest2->results->count() }} parameters</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Time Difference -->
    @if($labRequest1->completed_at && $labRequest2->completed_at)
        <div class="alert alert-info">
            <i class="bi bi-clock"></i>
            <strong>Time Difference:</strong> 
            {{ $labRequest1->completed_at->diffForHumans($labRequest2->completed_at) }}
            ({{ $labRequest1->completed_at->diffInDays($labRequest2->completed_at) }} days)
        </div>
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Note:</strong> Time difference cannot be calculated - completion date not recorded for one or both tests.
        </div>
    @endif

    <!-- Comparison Results -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="bi bi-bar-chart"></i> Parameter Comparison
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="20%">Parameter</th>
                            <th width="15%">First Test</th>
                            <th width="15%">Second Test</th>
                            <th width="10%">Change</th>
                            <th width="10%">Status</th>
                            <th width="15%">Reference Range</th>
                            <th width="15%">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparison as $item)
                        <tr>
                            <td>
                                <strong>{{ $item['parameter']->parameter_name }}</strong>
                                @if($item['parameter']->is_critical)
                                    <span class="badge bg-danger badge-sm ms-1">Critical</span>
                                @endif
                            </td>
                            <td>
                                @if($item['result1'])
                                    <div>
                                        {{ $item['result1']->getFormattedValue() }}
                                        @if($item['result1']->abnormal_flag)
                                            <span class="badge badge-sm {{ $item['result1']->getFlagBadgeClass() }}">
                                                {{ $item['result1']->abnormal_flag }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($item['result1']->result_entered_at)
                                        <small class="text-muted">{{ $item['result1']->result_entered_at->format('M d, Y') }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">Not tested</span>
                                @endif
                            </td>
                            <td>
                                @if($item['result2'])
                                    <div>
                                        {{ $item['result2']->getFormattedValue() }}
                                        @if($item['result2']->abnormal_flag)
                                            <span class="badge badge-sm {{ $item['result2']->getFlagBadgeClass() }}">
                                                {{ $item['result2']->abnormal_flag }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($item['result2']->result_entered_at)
                                        <small class="text-muted">{{ $item['result2']->result_entered_at->format('M d, Y') }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">Not tested</span>
                                @endif
                            </td>
                            <td>
                                @if($item['change'])
                                    @php
                                        $changeClass = 'text-muted';
                                        if (is_string($item['change']) && strpos($item['change'], '+') === 0) {
                                            $changeClass = 'text-danger';
                                        } elseif (is_string($item['change']) && strpos($item['change'], '-') === 0) {
                                            $changeClass = 'text-success';
                                        }
                                    @endphp
                                    <span class="{{ $changeClass }}">
                                        {{ $item['change'] }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($item['status_change'])
                                    @switch($item['status_change'])
                                        @case('improved')
                                            <span class="badge bg-success">
                                                <i class="bi bi-arrow-down"></i> Improved
                                            </span>
                                            @break
                                        @case('worsened')
                                            <span class="badge bg-danger">
                                                <i class="bi bi-arrow-up"></i> Worsened
                                            </span>
                                            @break
                                        @case('stable')
                                            <span class="badge bg-info">
                                                <i class="bi bi-dash"></i> Stable
                                            </span>
                                            @break
                                        @case('new')
                                            <span class="badge bg-primary">
                                                <i class="bi bi-plus"></i> New
                                            </span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-arrow-repeat"></i> Changed
                                            </span>
                                    @endswitch
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($item['result1'] && $item['result1']->reference_range)
                                    <small class="text-muted">{{ $item['result1']->reference_range }}</small>
                                @elseif($item['result2'] && $item['result2']->reference_range)
                                    <small class="text-muted">{{ $item['result2']->reference_range }}</small>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($item['result1'] && $item['result1']->clinical_interpretation)
                                    <small>{{ Str::limit($item['result1']->clinical_interpretation, 30) }}</small>
                                @elseif($item['result2'] && $item['result2']->clinical_interpretation)
                                    <small>{{ Str::limit($item['result2']->clinical_interpretation, 30) }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-arrow-up"></i>
                </div>
                <div class="stat-label">Worsened</div>
                <div class="stat-value">{{ collect($comparison)->where('status_change', 'worsened')->count() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-arrow-down"></i>
                </div>
                <div class="stat-label">Improved</div>
                <div class="stat-value">{{ collect($comparison)->where('status_change', 'improved')->count() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-dash"></i>
                </div>
                <div class="stat-label">Stable</div>
                <div class="stat-value">{{ collect($comparison)->where('status_change', 'stable')->count() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card secondary">
                <div class="stat-icon">
                    <i class="bi bi-plus"></i>
                </div>
                <div class="stat-label">New Tests</div>
                <div class="stat-value">{{ collect($comparison)->where('status_change', 'new')->count() }}</div>
            </div>
        </div>
    </div>

    <!-- Clinical Interpretation -->
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="bi bi-file-medical"></i> Clinical Summary
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Key Improvements</h6>
                    @php $improvements = collect($comparison)->where('status_change', 'improved'); @endphp
                    @if($improvements->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($improvements->take(3) as $improvement)
                            <li class="list-group-item">
                                <strong>{{ $improvement['parameter']->parameter_name }}</strong>
                                <span class="badge bg-success ms-2">{{ $improvement['change'] }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">No significant improvements noted.</p>
                    @endif
                </div>
                <div class="col-md-6">
                    <h6>Areas of Concern</h6>
                    @php $concerns = collect($comparison)->where('status_change', 'worsened'); @endphp
                    @if($concerns->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($concerns->take(3) as $concern)
                            <li class="list-group-item">
                                <strong>{{ $concern['parameter']->parameter_name }}</strong>
                                <span class="badge bg-danger ms-2">{{ $concern['change'] }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">No significant concerns noted.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
