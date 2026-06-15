@extends('layouts.app')

@section('title', 'Pharmacy Analytics')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-graph-up"></i> Pharmacy Analytics</h1>
            <p class="text-secondary mb-0">Prescription trends, drug analytics, and performance insights</p>
        </div>
        <div>
            <a href="{{ route('pharmacy.prescriptions') }}" class="btn btn-info me-2">
                <i class="bi bi-prescription"></i> Prescriptions
            </a>
            <a href="{{ route('pharmacy.stock') }}" class="btn btn-warning me-2">
                <i class="bi bi-box-seam"></i> Stock Management
            </a>
        </div>
    </div>

    @if(isset($error))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> {{ $error }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Date Range Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('pharmacy.analytics') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="date_range" class="form-label">Date Range</label>
                    <select name="date_range" id="date_range" class="form-select">
                        <option value="7" {{ $dateRange == '7' ? 'selected' : '' }}>Last 7 days</option>
                        <option value="30" {{ $dateRange == '30' ? 'selected' : '' }}>Last 30 days</option>
                        <option value="90" {{ $dateRange == '90' ? 'selected' : '' }}>Last 90 days</option>
                        <option value="365" {{ $dateRange == '365' ? 'selected' : '' }}>Last year</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-prescription"></i>
                </div>
                <div class="stat-label">Total Prescriptions</div>
                <div class="stat-value">{{ number_format($prescriptionTrends->sum('count') ?? 0) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">GH₵{{ number_format($financialData['total_prescription_value'] ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-calculator"></i>
                </div>
                <div class="stat-label">Avg Prescription Value</div>
                <div class="stat-value">GH₵{{ number_format($financialData['average_prescription_value'] ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-capsule-pill"></i>
                </div>
                <div class="stat-label">Unique Drugs</div>
                <div class="stat-value">{{ number_format($topDrugs->count() ?? 0) }}</div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Prescription Trends Chart -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up"></i> Prescription Trends
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="prescriptionTrendsChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Status Distribution Chart -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pie-chart"></i> Prescription Status
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="statusDistributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Tables Row -->
    <div class="row mb-4">
        <!-- Top Prescribed Drugs -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-trophy"></i> Top Prescribed Drugs
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Drug Name</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topDrugs as $index => $drug)
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        @if($drug->drug)
                                            <strong>{{ $drug->drug->name }}</strong>
                                            @if(isset($drug->drug->generic_name) && $drug->drug->generic_name)
                                                <br><small class="text-muted">{{ $drug->drug->generic_name }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">Unknown Drug</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($drug->drug && isset($drug->drug->category))
                                            <span class="badge bg-info">{{ $drug->drug->category }}</span>
                                        @else
                                            <span class="badge bg-secondary">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format($drug->total_quantity ?? 0) }}</strong>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doctor Prescription Patterns -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-badge"></i> Doctor Prescription Patterns
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Prescriptions</th>
                                    <th>Avg per Day</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($doctorPrescriptions as $doctor)
                                <tr>
                                    <td>
                                        @if($doctor->doctor)
                                            <strong>{{ $doctor->doctor->first_name }} {{ $doctor->doctor->last_name }}</strong>
                                            @if(isset($doctor->doctor->specialization))
                                                <br><small class="text-muted">{{ $doctor->doctor->specialization }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">Unknown Doctor</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $doctor->count }}</span>
                                    </td>
                                    <td>
                                        {{ number_format($doctor->count / max(1, $dateRange), 1) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Movement Analysis -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-box-seam"></i> Stock Movement Analysis
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Drug Name</th>
                                    <th>Current Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Dispensed ({{ $dateRange }} days)</th>
                                    <th>Stock Health</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockMovement as $stock)
                                <tr>
                                    <td>
                                        <strong>{{ $stock['drug_name'] }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ number_format($stock['current_stock']) }}</span>
                                    </td>
                                    <td>
                                        {{ number_format($stock['reorder_level']) }}
                                    </td>
                                    <td>
                                        <strong>{{ number_format($stock['dispensed_quantity']) }}</strong>
                                    </td>
                                    <td>
                                        @if($stock['stock_health'] == 'healthy')
                                            <span class="badge bg-success">Healthy</span>
                                        @else
                                            <span class="badge bg-warning">Low Stock</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($stock['current_stock'] == 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif($stock['current_stock'] <= $stock['reorder_level'])
                                            <span class="badge bg-warning">Reorder Needed</span>
                                        @else
                                            <span class="badge bg-success">Adequate</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No stock movement data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prescription Trends Chart
const prescriptionTrendsCtx = document.getElementById('prescriptionTrendsChart').getContext('2d');
const prescriptionTrendsData = @json($prescriptionTrends);

if (prescriptionTrendsData && prescriptionTrendsData.length > 0) {
    new Chart(prescriptionTrendsCtx, {
        type: 'line',
        data: {
            labels: prescriptionTrendsData.map(item => new Date(item.date).toLocaleDateString()),
            datasets: [{
                label: 'Prescriptions',
                data: prescriptionTrendsData.map(item => item.count),
                borderColor: '#1e3a5f',
                backgroundColor: 'rgba(30, 58, 95, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
} else {
    // Display "No data" message
    const canvas = document.getElementById('prescriptionTrendsChart');
    const parent = canvas.parentElement;
    parent.innerHTML = '<div class="d-flex align-items-center justify-content-center" style="height: 300px;"><div class="text-center text-muted"><i class="bi bi-inbox" style="font-size: 3rem;"></i><p class="mt-2">No prescription data available for the selected period</p></div></div>';
}

// Status Distribution Chart
const statusDistributionCtx = document.getElementById('statusDistributionChart').getContext('2d');
const statusDistributionData = @json($statusDistribution);

if (statusDistributionData && statusDistributionData.length > 0) {
    new Chart(statusDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: statusDistributionData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1)),
            datasets: [{
                data: statusDistributionData.map(item => item.count),
                backgroundColor: [
                    '#1e3a5f',
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#17a2b8'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
} else {
    // Display "No data" message
    const canvas = document.getElementById('statusDistributionChart');
    const parent = canvas.parentElement;
    parent.innerHTML = '<div class="d-flex align-items-center justify-content-center" style="height: 300px;"><div class="text-center text-muted"><i class="bi bi-inbox" style="font-size: 3rem;"></i><p class="mt-2">No status distribution data available</p></div></div>';
}
</script>
@endpush
@endsection
