@extends('layouts.app')

@section('title', 'My Lab Results')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-clipboard-data"></i> My Lab Results
            </h1>
            <p class="text-secondary mb-0">View all your laboratory test results</p>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-clipboard-data"></i>
                </div>
                <div class="stat-label">Total Tests</div>
                <div class="stat-value">{{ number_format($statistics['total']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Completed</div>
                <div class="stat-value">{{ number_format($statistics['completed']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-label">Pending</div>
                <div class="stat-value">{{ number_format($statistics['pending']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div class="stat-label">In Progress</div>
                <div class="stat-value">{{ number_format($statistics['in_progress']) }}</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('lab.my-results') }}" class="row g-3">
                {{-- Status filter removed - Patients only see completed requests with approved results --}}
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <a href="{{ route('lab.my-results') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lab Results List -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0 text-dark">
                <i class="bi bi-list-ul"></i> Lab Test Results
            </h5>
        </div>
        <div class="card-body">
            @if($labRequests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Request #</th>
                                <th>Test Type</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Doctor</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($labRequests as $labRequest)
                            <tr>
                                <td>
                                    <strong>{{ $labRequest->lab_request_number ?? $labRequest->request_number }}</strong>
                                </td>
                                <td>
                                    {{ $labRequest->testType->template->template_name ?? $labRequest->testType->test_name ?? $labRequest->test_type_name ?? $labRequest->test_type ?? 'N/A' }}
                                </td>
                                <td>
                                    {{ $labRequest->created_at->format('M d, Y') }}
                                    <br>
                                    <small class="text-muted">{{ $labRequest->created_at->format('h:i A') }}</small>
                                </td>
                                <td>
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
                                </td>
                                <td>
                                    {{ $labRequest->doctor->name ?? 'N/A' }}
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        @if($labRequest->status === 'completed')
                                            <a href="{{ route('lab.my-result-details', $labRequest) }}" class="btn btn-sm btn-primary" title="View Results">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            @if($labRequest->results && $labRequest->results->count() > 0)
                                                <a href="{{ route('lab.my-result-pdf', $labRequest) }}" class="btn btn-sm btn-success" title="Download PDF" target="_blank">
                                                    <i class="bi bi-download"></i> PDF
                                                </a>
                                            @endif
                                        @else
                                            <span class="text-muted">Results pending</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $labRequests->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-x text-secondary" style="font-size: 4rem;"></i>
                    <h5 class="mt-3 text-muted">No Lab Results Found</h5>
                    <p class="text-muted">You don't have any lab test results at the moment.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 1.5rem;
    color: white;
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card.success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.stat-card.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card.info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
    margin-bottom: 1rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    border-radius: 15px 15px 0 0 !important;
}
</style>
@endpush
