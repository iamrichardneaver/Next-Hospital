@extends('layouts.app')

@section('title', 'Insurance Analytics')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Insurance Analytics</h1>
            <p class="text-secondary mb-0">Comprehensive insurance performance and financial analytics</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="exportReport()">
                <i class="bi bi-download"></i> Export Report
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#dateRangeModal">
                <i class="bi bi-calendar"></i> Date Range
            </button>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('insurance.analytics') }}" class="row g-3">
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
                        <i class="bi bi-search"></i> Update Analytics
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Overview Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3>{{ $analytics['overview']['total_providers'] ?? 0 }}</h3>
                    <small>Insurance Providers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3>{{ $analytics['overview']['total_policies'] ?? 0 }}</h3>
                    <small>Active Policies</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3>{{ $analytics['overview']['total_claims'] ?? 0 }}</h3>
                    <small>Total Claims</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3>₵{{ number_format($analytics['financial']['total_claim_amount'] ?? 0, 2) }}</h3>
                    <small>Total Claim Amount</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Claims Analytics -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Claims Status Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="claimsStatusChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Provider Performance -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Insurance Providers</h5>
                </div>
                <div class="card-body">
                    <canvas id="providerPerformanceChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Financial Analytics -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Financial Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <h4 class="text-success">₵{{ number_format($analytics['financial']['total_covered_amount'] ?? 0, 2) }}</h4>
                                <small class="text-muted">Total Covered Amount</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <h4 class="text-warning">₵{{ number_format($analytics['financial']['total_co_pay_amount'] ?? 0, 2) }}</h4>
                                <small class="text-muted">Total Co-pay Amount</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <h4 class="text-info">{{ number_format($analytics['financial']['average_coverage_percentage'] ?? 0, 1) }}%</h4>
                                <small class="text-muted">Average Coverage %</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <canvas id="financialTrendChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pre-Authorization Analytics -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Pre-Authorization Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Pending</span>
                            <span class="badge bg-warning">{{ $analytics['pre_authorizations']['pending'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Approved</span>
                            <span class="badge bg-success">{{ $analytics['pre_authorizations']['approved'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Rejected</span>
                            <span class="badge bg-danger">{{ $analytics['pre_authorizations']['rejected'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Expired</span>
                            <span class="badge bg-secondary">{{ $analytics['pre_authorizations']['expired'] ?? 0 }}</span>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h6>Approval Rate</h6>
                        <h4 class="text-success">
                            {{ $analytics['pre_authorizations']['approval_rate'] ?? 0 }}%
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Tables -->
    <div class="row">
        <!-- Top Claims -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent High-Value Claims</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Provider</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($analytics['claims']['recent_high_value']))
                                    @foreach($analytics['claims']['recent_high_value'] as $claim)
                                    <tr>
                                        <td>{{ $claim['patient_name'] ?? 'N/A' }}</td>
                                        <td>{{ $claim['provider_name'] ?? 'N/A' }}</td>
                                        <td>₵{{ number_format($claim['total_amount'] ?? 0, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $claim['status'] === 'approved' ? 'success' : ($claim['status'] === 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($claim['status'] ?? 'Unknown') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No data available</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Provider Performance -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Provider Performance Metrics</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Provider</th>
                                    <th>Claims</th>
                                    <th>Avg Processing</th>
                                    <th>Approval Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($analytics['providers']['performance']))
                                    @foreach($analytics['providers']['performance'] as $provider)
                                    <tr>
                                        <td>{{ $provider['name'] ?? 'N/A' }}</td>
                                        <td>{{ $provider['total_claims'] ?? 0 }}</td>
                                        <td>{{ $provider['avg_processing_days'] ?? 0 }} days</td>
                                        <td>{{ $provider['approval_rate'] ?? 0 }}%</td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No data available</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trends Analysis -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Trends</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendsChart" width="400" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Date Range Modal -->
<div class="modal fade" id="dateRangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Date Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Quick Select</label>
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="setDateRange(7)">Last 7 Days</button>
                        <button type="button" class="btn btn-outline-primary" onclick="setDateRange(30)">Last 30 Days</button>
                        <button type="button" class="btn btn-outline-primary" onclick="setDateRange(90)">Last 3 Months</button>
                        <button type="button" class="btn btn-outline-primary" onclick="setDateRange(365)">Last Year</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" id="modalDateFrom">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" id="modalDateTo">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="applyDateRange()">Apply</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Initialize charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
});

function initializeCharts() {
    // Claims Status Chart
    const claimsCtx = document.getElementById('claimsStatusChart').getContext('2d');
    new Chart(claimsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Pending', 'Rejected'],
            datasets: [{
                data: [
                    {{ $analytics['claims']['approved'] ?? 0 }},
                    {{ $analytics['claims']['pending'] ?? 0 }},
                    {{ $analytics['claims']['rejected'] ?? 0 }}
                ],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Provider Performance Chart
    const providerCtx = document.getElementById('providerPerformanceChart').getContext('2d');
    new Chart(providerCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($analytics['providers']['names'] ?? []) !!},
            datasets: [{
                label: 'Claims Count',
                data: {!! json_encode($analytics['providers']['claims_count'] ?? []) !!},
                backgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Financial Trend Chart
    const financialCtx = document.getElementById('financialTrendChart').getContext('2d');
    new Chart(financialCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($analytics['trends']['months'] ?? []) !!},
            datasets: [{
                label: 'Covered Amount',
                data: {!! json_encode($analytics['trends']['covered_amounts'] ?? []) !!},
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                fill: true
            }, {
                label: 'Co-pay Amount',
                data: {!! json_encode($analytics['trends']['co_pay_amounts'] ?? []) !!},
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Monthly Trends Chart
    const monthlyCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($analytics['trends']['months'] ?? []) !!},
            datasets: [{
                label: 'Total Claims',
                data: {!! json_encode($analytics['trends']['total_claims'] ?? []) !!},
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function setDateRange(days) {
    const toDate = new Date();
    const fromDate = new Date();
    fromDate.setDate(fromDate.getDate() - days);
    
    document.getElementById('modalDateFrom').value = fromDate.toISOString().split('T')[0];
    document.getElementById('modalDateTo').value = toDate.toISOString().split('T')[0];
}

function applyDateRange() {
    const fromDate = document.getElementById('modalDateFrom').value;
    const toDate = document.getElementById('modalDateTo').value;
    
    if (fromDate && toDate) {
        window.location.href = `{{ route('insurance.analytics') }}?date_from=${fromDate}&date_to=${toDate}`;
    }
}

function exportReport() {
    const dateFrom = '{{ $dateFrom }}';
    const dateTo = '{{ $dateTo }}';
    window.open(`{{ route('insurance.export-report') }}?date_from=${dateFrom}&date_to=${dateTo}&report_type=analytics`, '_blank');
}

// Set default dates in modal
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('modalDateFrom').value = '{{ $dateFrom }}';
    document.getElementById('modalDateTo').value = '{{ $dateTo }}';
});
</script>
@endpush
