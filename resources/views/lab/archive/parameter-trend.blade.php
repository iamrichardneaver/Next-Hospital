@extends('layouts.app')

@section('title', 'Parameter Trend Analysis')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-graph-up"></i> Parameter Trend Analysis
            </h1>
            <p class="text-secondary mb-0">{{ $patient->full_name }} - {{ $parameter->parameter_name }}</p>
        </div>
        <div>
            <a href="{{ route('lab.archive.patient-history', $patient) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Patient History
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('lab.archive.parameter-trend', [$patient, $parameter]) }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Update Range
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Parameter Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Parameter Details</h6>
                </div>
                <div class="card-body">
                    <p><strong>Parameter:</strong> {{ $parameter->parameter_name }}</p>
                    <p><strong>Unit:</strong> {{ $parameter->unit ?? 'N/A' }}</p>
                    <p><strong>Data Type:</strong> {{ ucfirst($parameter->data_type) }}</p>
                    @if($parameter->is_critical)
                        <span class="badge bg-danger">Critical Parameter</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Reference Ranges</h6>
                </div>
                <div class="card-body">
                    @if($referenceRanges->count() > 0)
                        @foreach($referenceRanges as $range)
                            <div class="mb-2">
                                <strong>{{ $range->gender ?? 'All' }}:</strong>
                                <span class="text-muted">{{ $range->min_value }} - {{ $range->max_value }} {{ $parameter->unit ?? '' }}</span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">No reference ranges defined</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Trend Chart -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="bi bi-graph-up"></i> Trend Analysis
            </h6>
        </div>
        <div class="card-body">
            @if($results->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Value</th>
                                <th>Flag</th>
                                <th>Status</th>
                                <th>Reference Range</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                            <tr>
                                <td>{{ $result->result_entered_at ? $result->result_entered_at->format('M d, Y') : 'Not recorded' }}</td>
                                <td>
                                    <strong>{{ $result->getFormattedValue() }}</strong>
                                </td>
                                <td>
                                    @if($result->abnormal_flag)
                                        <span class="badge {{ $result->getFlagBadgeClass() }}">
                                            {{ $result->abnormal_flag }}
                                        </span>
                                    @else
                                        <span class="badge bg-success">N</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $result->getStatusBadgeClass() }}">
                                        {{ ucfirst($result->result_status) }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $result->reference_range ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    @if($result->clinical_interpretation)
                                        <small>{{ Str::limit($result->clinical_interpretation, 50) }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Trend Statistics -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="bi bi-clipboard-data"></i>
                            </div>
                            <div class="stat-label">Total Tests</div>
                            <div class="stat-value">{{ $results->count() }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card {{ $results->where('result_status', 'abnormal')->count() > 0 ? 'warning' : 'success' }}">
                            <div class="stat-icon">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="stat-label">Abnormal Results</div>
                            <div class="stat-value">{{ $results->where('result_status', 'abnormal')->count() }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card {{ $results->where('result_status', 'critical')->count() > 0 ? 'danger' : 'success' }}">
                            <div class="stat-icon">
                                <i class="bi bi-shield-exclamation"></i>
                            </div>
                            <div class="stat-label">Critical Results</div>
                            <div class="stat-value">{{ $results->where('result_status', 'critical')->count() }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="bi bi-calendar-range"></i>
                            </div>
                            <div class="stat-label">Date Range</div>
                            <div class="stat-value">{{ $results->count() > 0 ? $results->first()->result_entered_at->diffInDays($results->last()->result_entered_at) : 0 }} days</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-graph-up display-4 text-muted"></i>
                    <p class="text-muted mt-2">No results found for this parameter in the selected date range.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Clinical Summary -->
    @if($results->count() > 0)
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="bi bi-file-medical"></i> Clinical Summary
            </h6>
        </div>
        <div class="card-body">
            @php
                $abnormalResults = $results->where('result_status', 'abnormal');
                $criticalResults = $results->where('result_status', 'critical');
                $normalResults = $results->where('result_status', 'normal');
            @endphp
            
            <div class="row">
                <div class="col-md-6">
                    <h6>Trend Analysis</h6>
                    @if($results->count() >= 2)
                        @php
                            $firstResult = $results->first();
                            $lastResult = $results->last();
                            $firstValue = floatval($firstResult->result_value);
                            $lastValue = floatval($lastResult->result_value);
                            $change = $firstValue != 0 ? (($lastValue - $firstValue) / $firstValue) * 100 : 0;
                        @endphp
                        
                        <p><strong>Overall Change:</strong> 
                            @if($change > 0)
                                <span class="text-danger">+{{ round($change, 1) }}%</span> (Increased)
                            @elseif($change < 0)
                                <span class="text-success">{{ round($change, 1) }}%</span> (Decreased)
                            @else
                                <span class="text-muted">No significant change</span>
                            @endif
                        </p>
                        
                        <p><strong>First Value:</strong> {{ $firstResult->getFormattedValue() }} ({{ $firstResult->result_entered_at ? $firstResult->result_entered_at->format('M d, Y') : 'Not recorded' }})</p>
                        <p><strong>Latest Value:</strong> {{ $lastResult->getFormattedValue() }} ({{ $lastResult->result_entered_at ? $lastResult->result_entered_at->format('M d, Y') : 'Not recorded' }})</p>
                    @else
                        <p class="text-muted">Insufficient data for trend analysis (need at least 2 results)</p>
                    @endif
                </div>
                
                <div class="col-md-6">
                    <h6>Result Distribution</h6>
                    <div class="mb-2">
                        <span class="badge bg-success">{{ $normalResults->count() }}</span> Normal Results
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-warning">{{ $abnormalResults->count() }}</span> Abnormal Results
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-danger">{{ $criticalResults->count() }}</span> Critical Results
                    </div>
                    
                    @if($abnormalResults->count() > 0 || $criticalResults->count() > 0)
                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Alert:</strong> This parameter has shown abnormal or critical values that may require clinical attention.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
