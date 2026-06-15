@extends('layouts.app')

@section('title', 'Lab Request Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1" style="color: #1e3a5f;">Lab Request Details</h1><p class="text-secondary mb-0">{{ $lab->request_number }}</p></div>
        <div>
            <a href="{{ route('lab.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            @can('edit_lab_requests')
            <a href="{{ route('lab.edit', $lab) }}" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>
            @endcan
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><h6 class="mb-0">Request Information</h6></div>
                <div class="card-body">
                    <p class="mb-2"><strong>Request #:</strong><br><span class="badge bg-primary">{{ $lab->request_number }}</span></p>
                    <p class="mb-2"><strong>Test Type:</strong><br>{{ $lab->test_type }}</p>
                    <p class="mb-2"><strong>Category:</strong><br><span class="badge bg-info">{{ $lab->test_category ?? 'N/A' }}</span></p>
                    <p class="mb-2"><strong>Status:</strong><br>
                        <span class="badge bg-{{ $lab->status === 'completed' ? 'success' : ($lab->status === 'pending' ? 'warning' : 'info') }}">
                            {{ ucfirst($lab->status) }}
                        </span>
                    </p>
                    <p class="mb-0"><strong>Requested:</strong><br>{{ $lab->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white"><h6 class="mb-0">Patient Information</h6></div>
                <div class="card-body">
                    @if($lab->patient)
                    <h6>{{ $lab->patient->full_name }}</h6>
                    <p class="mb-1 small"><strong>ID:</strong> {{ $lab->patient->patient_number }}</p>
                    <p class="mb-1 small"><strong>Age:</strong> {{ $lab->patient->age }} years</p>
                    <p class="mb-0 small"><strong>Gender:</strong> {{ $lab->patient->gender }}</p>
                    <a href="{{ route('patients.show', $lab->patient) }}" class="btn btn-sm btn-outline-success w-100 mt-3"><i class="bi bi-eye"></i> View Patient</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white"><h6 class="mb-0">Results</h6></div>
                <div class="card-body">
                    @if($lab->results && $lab->results->count() > 0)
                        <p class="text-success"><i class="bi bi-check-circle"></i> {{ $lab->results->count() }} Result(s) Available</p>
                        
                        @php
                            $verifiedCount = $lab->results->whereNotNull('result_verified_at')->count();
                            $approvedCount = $lab->results->whereNotNull('result_approved_at')->count();
                        @endphp
                        
                        <div class="mb-2">
                            <small><strong>Verified:</strong> {{ $verifiedCount }}/{{ $lab->results->count() }}</small><br>
                            <small><strong>Approved:</strong> {{ $approvedCount }}/{{ $lab->results->count() }}</small>
                        </div>
                        
                        @can('verify_lab_results')
                        @if($verifiedCount < $lab->results->count())
                        <form action="{{ route('lab.verify-results', $lab) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning w-100" onclick="return confirm('Verify all results?');">
                                <i class="bi bi-check-square"></i> Verify Results
                            </button>
                        </form>
                        @endif
                        @endcan
                        
                        @can('approve_lab_results')
                        @if($verifiedCount === $lab->results->count() && $approvedCount < $lab->results->count())
                        <form action="{{ route('lab.approve-results', $lab) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success w-100" onclick="return confirm('Approve all results?');">
                                <i class="bi bi-check-circle"></i> Approve Results
                            </button>
                        </form>
                        @endif
                        @endcan
                        
                        @can('print_lab_results')
                        <a href="{{ route('lab.generate-pdf', $lab) }}" class="btn btn-sm btn-info w-100" target="_blank">
                            <i class="bi bi-download"></i> Download PDF
                        </a>
                        @endcan
                    @else
                        <p class="text-secondary">No results available yet</p>
                        @can('enter_lab_results')
                        <a href="{{ route('lab.enter-results', $lab) }}" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-plus"></i> Enter Results
                        </a>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    @if($lab->results && $lab->results->count() > 0)
    <!-- Results Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 text-dark">Test Results</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Parameter</th>
                            <th>Result Value</th>
                            <th>Unit</th>
                            <th>Reference Range</th>
                            <th>Status</th>
                            <th>Flag</th>
                            <th>Performed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lab->results as $result)
                        <tr>
                            <td><strong>{{ $result->parameter_name }}</strong></td>
                            <td>{{ $result->result_value }}</td>
                            <td>{{ $result->unit ?? '-' }}</td>
                            <td><small class="text-muted">{{ $result->getReferenceRangeText() }}</small></td>
                            <td>
                                <span class="badge bg-{{ $result->result_status === 'normal' ? 'success' : ($result->result_status === 'critical' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($result->result_status) }}
                                </span>
                            </td>
                            <td>
                                @if($result->abnormal_flag)
                                    <span class="badge bg-danger">{{ $result->abnormal_flag }}</span>
                                @else
                                    <span class="badge bg-success">Normal</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $result->performedBy->firstname ?? 'N/A' }} {{ $result->performedBy->lastname ?? '' }}</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

@if(session('warning'))
<div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('success'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    alert('{{ session("success") }}');
});
</script>
@endif
@endsection
