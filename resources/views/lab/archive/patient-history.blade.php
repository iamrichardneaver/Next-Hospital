@extends('layouts.app')

@section('title', 'Patient Lab History')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-person-lines-fill"></i> Patient Lab History
            </h1>
            <p class="text-secondary mb-0">{{ $patient->full_name }} - {{ $patient->patient_number }}</p>
        </div>
        <div>
            <a href="{{ route('lab.archive.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Archive
            </a>
            <a href="{{ route('lab.archive.export-patient-history', $patient) }}" class="btn btn-success">
                <i class="bi bi-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>

    <!-- Patient Info Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Patient:</strong> {{ $patient->full_name }}<br>
                    <strong>ID:</strong> {{ $patient->patient_number }}<br>
                    <strong>Age:</strong> {{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : 'N/A' }} years
                </div>
                <div class="col-md-3">
                    <strong>Gender:</strong> {{ $patient->gender ?? 'N/A' }}<br>
                    <strong>Contact:</strong> {{ $patient->contact ?? 'N/A' }}<br>
                    <strong>NHIS:</strong> {{ $patient->nhis_number ?? 'N/A' }}
                </div>
                <div class="col-md-6">
                    <strong>Address:</strong> {{ $patient->address ?? 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Test Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-clipboard-data"></i>
                </div>
                <div class="stat-label">Total Requests</div>
                <div class="stat-value">{{ number_format($testSummary['total_requests']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Total Results</div>
                <div class="stat-value">{{ number_format($testSummary['total_results']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-label">Abnormal Results</div>
                <div class="stat-value">{{ number_format($testSummary['abnormal_results']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-shield-exclamation"></i>
                </div>
                <div class="stat-label">Critical Results</div>
                <div class="stat-value">{{ number_format($testSummary['critical_results']) }}</div>
            </div>
        </div>
    </div>

    <!-- Trending Analysis -->
    @if(count($trendingAnalysis['trending_up']) > 0 || count($trendingAnalysis['trending_down']) > 0)
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="bi bi-graph-up-arrow"></i> Trending Analysis (Last 6 Months)
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                @if(count($trendingAnalysis['trending_up']) > 0)
                <div class="col-md-6">
                    <h6 class="text-danger">
                        <i class="bi bi-arrow-up"></i> Trending Up (>10% increase)
                    </h6>
                    <div class="list-group list-group-flush">
                        @foreach($trendingAnalysis['trending_up'] as $trend)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $trend['parameter']->parameter_name }}</strong><br>
                                <small class="text-muted">
                                    {{ number_format($trend['first_value'], 2) }} → {{ number_format($trend['last_value'], 2) }}
                                </small>
                            </div>
                            <span class="badge bg-danger">{{ round($trend['change'], 1) }}%</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if(count($trendingAnalysis['trending_down']) > 0)
                <div class="col-md-6">
                    <h6 class="text-success">
                        <i class="bi bi-arrow-down"></i> Trending Down (>10% decrease)
                    </h6>
                    <div class="list-group list-group-flush">
                        @foreach($trendingAnalysis['trending_down'] as $trend)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $trend['parameter']->parameter_name }}</strong><br>
                                <small class="text-muted">
                                    {{ number_format($trend['first_value'], 2) }} → {{ number_format($trend['last_value'], 2) }}
                                </small>
                            </div>
                            <span class="badge bg-success">{{ round($trend['change'], 1) }}%</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="bi bi-funnel"></i> Filter History
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('lab.archive.patient-history', $patient) }}">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control" name="date_from" 
                               value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Date To</label>
                        <input type="date" class="form-control" name="date_to" 
                               value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Test Type</label>
                        <select class="form-select" name="test_type">
                            <option value="">All Types</option>
                            <option value="quantitative" {{ request('test_type') == 'quantitative' ? 'selected' : '' }}>Quantitative</option>
                            <option value="qualitative" {{ request('test_type') == 'qualitative' ? 'selected' : '' }}>Qualitative</option>
                            <option value="narrative" {{ request('test_type') == 'narrative' ? 'selected' : '' }}>Narrative</option>
                            <option value="combined" {{ request('test_type') == 'combined' ? 'selected' : '' }}>Combined</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('lab.archive.patient-history', $patient) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lab Requests History -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Lab Test History</h6>
            <span class="badge bg-primary">{{ $labRequests->total() }} requests found</span>
        </div>
        <div class="card-body p-0">
            @forelse($labRequests as $labRequest)
            <div class="border-bottom p-3">
                <div class="row">
                    <div class="col-md-8">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">
                                    <strong>{{ $labRequest->template->template_name ?? 'Unknown Test' }}</strong>
                                    <span class="badge bg-primary ms-2">{{ $labRequest->request_number }}</span>
                                </h6>
                                <p class="text-muted mb-1">
                                    <i class="bi bi-calendar"></i> 
                                    @if($labRequest->completed_at)
                                        {{ $labRequest->completed_at->format('M d, Y h:i A') }}
                                    @else
                                        <span class="text-muted">Not recorded</span>
                                    @endif
                                    @if($labRequest->doctor)
                                        | <i class="bi bi-person"></i> {{ $labRequest->doctor->first_name }} {{ $labRequest->doctor->last_name }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-end">
                                @php
                                    $hasCritical = $labRequest->results->contains('result_status', 'critical');
                                    $hasAbnormal = $labRequest->results->contains('result_status', 'abnormal');
                                @endphp
                                @if($hasCritical)
                                    <span class="badge bg-danger">Critical</span>
                                @elseif($hasAbnormal)
                                    <span class="badge bg-warning">Abnormal</span>
                                @else
                                    <span class="badge bg-success">Normal</span>
                                @endif
                            </div>
                        </div>

                        @if($labRequest->results->count() > 0)
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Parameter</th>
                                                <th>Result</th>
                                                <th>Reference Range</th>
                                                <th>Status</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($labRequest->results->take(5) as $result)
                                            <tr>
                                                <td>
                                                    <strong>{{ $result->parameter_name }}</strong>
                                                    @if($result->parameter && $result->parameter->is_critical)
                                                        <span class="badge bg-danger badge-sm ms-1">Critical</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $result->getFormattedValue() }}
                                                    @if($result->abnormal_flag)
                                                        <span class="badge badge-sm {{ $result->getFlagBadgeClass() }}">
                                                            {{ $result->abnormal_flag }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ $result->reference_range ?? 'N/A' }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-sm {{ $result->getStatusBadgeClass() }}">
                                                        {{ ucfirst($result->result_status) }}
                                                    </span>
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
                                @if($labRequest->results->count() > 5)
                                <small class="text-muted">
                                    Showing 5 of {{ $labRequest->results->count() }} results. 
                                    <a href="{{ route('lab.show', $labRequest) }}">View all</a>
                                </small>
                                @endif
                            </div>
                        </div>
                        @else
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> No results available for this request.
                        </div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex flex-column gap-2">
                            <a href="{{ route('lab.show', $labRequest) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                            @if($labRequest->results->count() > 0)
                            <a href="{{ route('lab.generate-pdf', $labRequest) }}" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-file-pdf"></i> Download PDF
                            </a>
                            @endif
                            <a href="#" class="btn btn-outline-info btn-sm" 
                               onclick="compareWithRequest({{ $labRequest->id }})">
                                <i class="bi bi-arrow-left-right"></i> Compare
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-5">
                <i class="bi bi-clipboard-x display-4 text-muted"></i>
                <p class="mt-3 text-muted">No lab test history found for this patient.</p>
            </div>
            @endforelse
        </div>
        
        @if($labRequests->hasPages())
        <div class="card-footer">
            {{ $labRequests->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Compare Modal -->
<div class="modal fade" id="compareModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Compare Lab Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="GET" action="{{ route('lab.archive.compare-results') }}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">First Lab Request</label>
                            <select class="form-select" name="request1" id="request1Select" required>
                                <option value="">Select first request</option>
                                @foreach($labRequests as $request)
                                <option value="{{ $request->id }}">{{ $request->request_number }} - {{ $request->template->template_name ?? 'Unknown' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Second Lab Request</label>
                            <select class="form-select" name="request2" id="request2Select" required>
                                <option value="">Select second request</option>
                                @foreach($labRequests as $request)
                                <option value="{{ $request->id }}">{{ $request->request_number }} - {{ $request->template->template_name ?? 'Unknown' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Compare</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function compareWithRequest(requestId) {
    document.getElementById('request1Select').value = requestId;
    new bootstrap.Modal(document.getElementById('compareModal')).show();
}
</script>
@endsection
