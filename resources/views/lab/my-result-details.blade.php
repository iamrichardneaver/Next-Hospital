@extends('layouts.app')

@section('title', 'Lab Result Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-clipboard-check"></i> Lab Result Details
            </h1>
            <p class="text-secondary mb-0">Request #{{ $labRequest->lab_request_number ?? $labRequest->request_number }}</p>
        </div>
        <div>
            <a href="{{ route('lab.my-results') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Results
            </a>
            @if($labRequest->status === 'completed' && $labRequest->results && $labRequest->results->count() > 0)
                <a href="{{ route('lab.my-result-pdf', $labRequest) }}" class="btn btn-success" target="_blank">
                    <i class="bi bi-download"></i> Download PDF
                </a>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Test Information -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Test Information</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Request Number:</strong><br>
                        <span class="badge bg-primary">{{ $labRequest->lab_request_number ?? $labRequest->request_number }}</span>
                    </p>
                    <p class="mb-2">
                        <strong>Test Type:</strong><br>
                        {{ $labRequest->testType->template->template_name ?? $labRequest->testType->test_name ?? $labRequest->test_type_name ?? $labRequest->test_type ?? 'N/A' }}
                    </p>
                    @if($labRequest->testType && $labRequest->testType->category)
                    <p class="mb-2">
                        <strong>Category:</strong><br>
                        <span class="badge bg-info">{{ $labRequest->testType->category->name ?? 'N/A' }}</span>
                    </p>
                    @endif
                    <p class="mb-2">
                        <strong>Status:</strong><br>
                        @if($labRequest->status === 'completed')
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i> Completed
                            </span>
                        @elseif($labRequest->status === 'in_progress')
                            <span class="badge bg-info">
                                <i class="bi bi-arrow-repeat"></i> In Progress
                            </span>
                        @else
                            <span class="badge bg-warning">
                                <i class="bi bi-hourglass-split"></i> Pending
                            </span>
                        @endif
                    </p>
                    <p class="mb-2">
                        <strong>Requested Date:</strong><br>
                        {{ $labRequest->created_at->format('M d, Y h:i A') }}
                    </p>
                    @if($labRequest->completed_at)
                    <p class="mb-0">
                        <strong>Completed Date:</strong><br>
                        {{ $labRequest->completed_at->format('M d, Y h:i A') }}
                    </p>
                    @endif
                </div>
            </div>

            <!-- Doctor Information -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-person-badge"></i> Doctor Information</h6>
                </div>
                <div class="card-body">
                    @if($labRequest->doctor)
                        <p class="mb-1"><strong>Name:</strong> {{ $labRequest->doctor->name }}</p>
                        @if($labRequest->doctor->email)
                            <p class="mb-0"><strong>Email:</strong> {{ $labRequest->doctor->email }}</p>
                        @endif
                    @else
                        <p class="text-muted mb-0">Not specified</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Results -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-clipboard-data"></i> Test Results</h6>
                </div>
                <div class="card-body">
                    @if($labRequest->status === 'completed' && $labRequest->results && $labRequest->results->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Result</th>
                                        <th>Unit</th>
                                        <th>Reference Range</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($labRequest->results as $result)
                                    <tr>
                                        <td>
                                            <strong>{{ $result->parameter_name ?? $result->parameter->parameter_name ?? 'N/A' }}</strong>
                                            @if($result->parameter_code)
                                                <br><small class="text-muted">Code: {{ $result->parameter_code }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $result->result_value ?? $result->formatted_value ?? 'N/A' }}</strong>
                                        </td>
                                        <td>{{ $result->unit ?? 'N/A' }}</td>
                                        <td>{{ $result->reference_range ?? 'N/A' }}</td>
                                        <td>
                                            @if($result->result_status === 'normal')
                                                <span class="badge bg-success">Normal</span>
                                            @elseif($result->result_status === 'abnormal')
                                                <span class="badge bg-warning">
                                                    Abnormal
                                                    @if($result->abnormal_flag)
                                                        ({{ $result->abnormal_flag }})
                                                    @endif
                                                </span>
                                            @elseif($result->result_status === 'critical')
                                                <span class="badge bg-danger">Critical</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($result->result_status ?? 'N/A') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($result->clinical_interpretation)
                                    <tr>
                                        <td colspan="5" class="bg-light">
                                            <small><strong>Clinical Interpretation:</strong> {{ $result->clinical_interpretation }}</small>
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Verification Status -->
                        @php
                            $verifiedCount = $labRequest->results->whereNotNull('result_verified_at')->count();
                            $approvedCount = $labRequest->results->whereNotNull('result_approved_at')->count();
                            $totalResults = $labRequest->results->count();
                        @endphp

                        <div class="mt-3 p-3 bg-light rounded">
                            <h6 class="mb-2">Verification Status</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong>Verified:</strong> 
                                        <span class="badge bg-{{ $verifiedCount === $totalResults ? 'success' : 'warning' }}">
                                            {{ $verifiedCount }}/{{ $totalResults }}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-0">
                                        <strong>Approved:</strong> 
                                        <span class="badge bg-{{ $approvedCount === $totalResults ? 'success' : 'warning' }}">
                                            {{ $approvedCount }}/{{ $totalResults }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @elseif($labRequest->status === 'completed')
                        <div class="text-center py-4">
                            <i class="bi bi-clipboard-x text-secondary" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-muted">Results Not Available</h5>
                            <p class="text-muted">The test has been completed but results are not yet available.</p>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-muted">Test In Progress</h5>
                            <p class="text-muted">Your test is currently being processed. Results will be available once completed.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Clinical Notes -->
            @if($labRequest->clinical_notes)
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="bi bi-file-text"></i> Clinical Notes</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $labRequest->clinical_notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
}

.table-bordered {
    border-radius: 10px;
    overflow: hidden;
}

.table thead {
    background-color: #f8f9fa;
}
</style>
@endpush
