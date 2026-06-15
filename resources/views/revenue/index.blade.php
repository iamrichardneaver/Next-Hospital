@extends('layouts.app')

@section('title', 'Revenue Analytics & Streams')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <!-- Toolbar -->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                    Revenue Analytics & Streams
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Revenue Analytics</li>
                </ul>
        </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
            <a href="{{ route('revenue.export', ['start_date' => $startDate, 'end_date' => $endDate, 'branch_id' => $branchId]) }}" 
                   class="btn btn-sm btn-primary">
                    <i class="bi bi-download fs-4"></i> Export Report
            </a>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="bi bi-funnel fs-4"></i> Filter Period
            </button>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">

    <!-- Filter Info -->
            <div class="alert alert-light-info border-0 mb-5">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                        <i class="bi bi-calendar-event me-2"></i>
                <strong>Period:</strong> {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} 
                - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                @if($branchId)
                    | <strong>Branch:</strong> {{ $branches->find($branchId)->name ?? 'All' }}
                @endif
            </div>
                    <a href="{{ route('revenue.index') }}" class="btn btn-sm btn-light-info">
                        <i class="bi bi-arrow-clockwise"></i> Reset Filters
            </a>
        </div>
    </div>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-5">
                <!-- Total Revenue -->
                <div class="col-xl-3">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value">GHS {{ number_format($overallStats['total_revenue'], 2) }}</div>
                        <div class="small opacity-75 mt-2">
                            @if($overallStats['revenue_growth'] != 0)
                                <i class="bi bi-arrow-{{ $overallStats['revenue_growth'] > 0 ? 'up' : 'down' }}"></i>
                                {{ abs($overallStats['revenue_growth']) }}% vs previous period
                            @else
                                No previous data
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Total Invoices -->
                <div class="col-xl-3">
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="stat-label">Total Invoices</div>
                        <div class="stat-value">{{ number_format($overallStats['total_invoices']) }}</div>
                        <div class="small opacity-75 mt-2">
                            Payment Rate: {{ number_format($overallStats['collection_rate'], 1) }}% | Paid: {{ number_format($overallStats['paid_invoices']) }}
                        </div>
                    </div>
                </div>

                <!-- Average Invoice -->
                <div class="col-xl-3">
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="stat-label">Average Invoice</div>
                        <div class="stat-value">GHS {{ number_format($overallStats['average_transaction_value'], 2) }}</div>
                        <div class="small opacity-75 mt-2">
                            Per transaction | Total transactions: {{ number_format($overallStats['total_transactions']) }}
                        </div>
                    </div>
                </div>

                <!-- Pending Amount -->
                <div class="col-xl-3">
                    <div class="stat-card danger">
                        <div class="stat-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="stat-label">Pending Amount</div>
                        <div class="stat-value">GHS {{ number_format($overallStats['pending_amount'], 2) }}</div>
                        <div class="small opacity-75 mt-2">
                            Outstanding invoices | Requires follow-up
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue by Department -->
            <div class="row g-4 mb-5">
        <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header border-0 pt-5">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold fs-4 mb-1">Revenue by Department/Service</span>
                                <span class="text-muted mt-1 fw-semibold fs-8">Click on any department to view transaction details</span>
                            </h3>
                        </div>
                        <div class="card-body py-3">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Department</th>
                                            <th class="text-end">Revenue (GHS)</th>
                                            <th>Contribution</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                            <tbody>
                                @foreach($revenueByDepartment as $dept)
                                        <tr class="clickable-row" data-service-type="{{ $dept['service_type'] }}" style="cursor: pointer;">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar bg-primary me-2" style="width: 35px; height: 35px; font-size: 14px;">
                                                        <i class="bi bi-building text-white"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">{{ $dept['department'] }}</div>
                                                        <small class="text-muted">Click to view transactions</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-success">GHS {{ number_format($dept['revenue'], 2) }}</strong>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress w-100 me-2" style="height: 8px;">
                                                        <div class="progress-bar bg-primary" 
                                                             style="width: {{ $dept['percentage'] }}%"></div>
                                                    </div>
                                                    <span class="badge bg-primary">{{ $dept['percentage'] }}%</span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-sm btn-info view-transactions" data-service-type="{{ $dept['service_type'] }}" title="View Transactions">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-success" onclick="exportDepartmentData('{{ $dept['department'] }}')" title="Export Data">
                                                        <i class="bi bi-download"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Revenue by Payment Method -->
                    <div class="card">
                        <div class="card-header border-0 pt-5">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold fs-4 mb-1">Payment Methods</span>
                                <span class="text-muted mt-1 fw-semibold fs-8">Revenue breakdown by payment type</span>
                            </h3>
                        </div>
                        <div class="card-body py-3">
                            @foreach($revenueByPaymentMethod as $payment)
                            <div class="mb-3 clickable-payment-method" data-payment-method="{{ strtolower(str_replace(' ', '_', $payment['payment_method'])) }}" style="cursor: pointer;">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="user-avatar bg-info me-2" style="width: 35px; height: 35px; font-size: 14px;">
                                        @switch(strtolower($payment['payment_method']))
                                            @case('cash')
                                                <i class="bi bi-cash text-white"></i>
                                                @break
                                            @case('card')
                                                <i class="bi bi-credit-card text-white"></i>
                                                @break
                                            @case('momo')
                                                <i class="bi bi-phone text-white"></i>
                                                @break
                                            @case('insurance')
                                                <i class="bi bi-shield-check text-white"></i>
                                                @break
                                            @default
                                                <i class="bi bi-bank text-white"></i>
                                        @endswitch
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">{{ $payment['payment_method'] }}</div>
                                        <small class="text-muted">{{ $payment['count'] }} transactions</small>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-success">GHS {{ number_format($payment['amount'], 2) }}</strong>
                                    </div>
                                </div>
                                <div class="progress mb-1" style="height: 6px;">
                                    <div class="progress-bar bg-info" 
                                         style="width: {{ $payment['percentage'] }}%"></div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">{{ $payment['percentage'] }}% of total</small>
                                    <small class="text-muted">Click to view details</small>
                                </div>
                            </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Revenue Trend -->
            <div class="row g-4 mb-5">
        <div class="col-12">
                    <div class="card">
                        <div class="card-header border-0 pt-5">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold fs-4 mb-1">Daily Revenue Trend</span>
                                <span class="text-muted mt-1 fw-semibold fs-8">Revenue performance over time</span>
                            </h3>
                        </div>
                        <div class="card-body py-3">
                    <canvas id="dailyRevenueChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Services and Drugs -->
            <div class="row g-4 mb-5">
        <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header border-0 pt-5">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold fs-4 mb-1">Top Revenue Generating Services</span>
                                <span class="text-muted mt-1 fw-semibold fs-8">Most profitable services</span>
                            </h3>
                        </div>
                        <div class="card-body py-3">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Service</th>
                                            <th class="text-end">Times Used</th>
                                            <th class="text-end">Revenue (GHS)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topServices as $index => $service)
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">{{ $index + 1 }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar bg-warning me-2" style="width: 35px; height: 35px; font-size: 14px;">
                                                        <i class="bi bi-star-fill text-white"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">{{ $service['service_name'] }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <strong>{{ number_format($service['count']) }}</strong>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-success">GHS {{ number_format($service['revenue'], 2) }}</strong>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header border-0 pt-5">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold fs-4 mb-1">Top Revenue Generating Drugs</span>
                                <span class="text-muted mt-1 fw-semibold fs-8">Most profitable medications</span>
                            </h3>
                        </div>
                        <div class="card-body py-3">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Drug</th>
                                            <th class="text-end">Quantity Sold</th>
                                            <th class="text-end">Revenue (GHS)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topDrugs as $index => $drug)
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">{{ $index + 1 }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar bg-danger me-2" style="width: 35px; height: 35px; font-size: 14px;">
                                                        <i class="bi bi-capsule text-white"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">{{ $drug['drug_name'] }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <strong>{{ number_format($drug['quantity']) }}</strong>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-success">GHS {{ number_format($drug['revenue'], 2) }}</strong>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
            </div>
        </div>
    </div>

    <!-- Branch Comparison -->
    @if(count($branchComparison) > 1)
            <div class="row mb-5">
        <div class="col-12">
                    <div class="card">
                        <div class="card-header border-0 pt-5">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold fs-4 mb-1">Branch Performance Comparison</span>
                                <span class="text-muted mt-1 fw-semibold fs-8">Revenue comparison across branches</span>
                            </h3>
                        </div>
                        <div class="card-body py-3">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Branch</th>
                                            <th class="text-end">Revenue (GHS)</th>
                                            <th class="text-end">Invoices</th>
                                            <th class="text-end">Avg Invoice Value</th>
                                            <th>Performance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($branchComparison as $branch)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar bg-info me-2" style="width: 35px; height: 35px; font-size: 14px;">
                                                        <i class="bi bi-building text-white"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">{{ $branch->name }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-success">GHS {{ number_format($branch->revenue, 2) }}</strong>
                                            </td>
                                            <td class="text-end">
                                                <strong>{{ number_format($branch->invoice_count) }}</strong>
                                            </td>
                                            <td class="text-end">
                                                <strong>GHS {{ number_format($branch->invoice_count > 0 ? $branch->revenue / $branch->invoice_count : 0, 2) }}</strong>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress w-100 me-2" style="height: 8px;">
                                                        <div class="progress-bar bg-primary" 
                                                             style="width: {{ $branchComparison->max('revenue') > 0 ? ($branch->revenue / $branchComparison->max('revenue')) * 100 : 0 }}%"></div>
                                                    </div>
                                                    <small class="text-muted">{{ $branchComparison->max('revenue') > 0 ? round(($branch->revenue / $branchComparison->max('revenue')) * 100, 1) : 0 }}%</small>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Outstanding Payments -->
    @if(count($outstandingPayments) > 0)
            <div class="row mb-5">
        <div class="col-12">
                    <div class="card">
                        <div class="card-header border-0 pt-5">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold fs-4 mb-1">Outstanding Payments (Top 20)</span>
                                <span class="text-muted mt-1 fw-semibold fs-8">Invoices requiring follow-up</span>
                            </h3>
                        </div>
                        <div class="card-body py-3">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Patient</th>
                                            <th>Branch</th>
                                            <th>Invoice Date</th>
                                            <th class="text-end">Amount (GHS)</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($outstandingPayments as $invoice)
                                        <tr>
                                            <td>
                                                <strong class="text-primary">{{ $invoice->invoice_number }}</strong>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar bg-primary me-2" style="width: 35px; height: 35px; font-size: 14px;">
                                                        <i class="bi bi-person text-white"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">{{ $invoice->patient->first_name ?? '' }} {{ $invoice->patient->last_name ?? '' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong>{{ $invoice->branch->name ?? 'N/A' }}</strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $invoice->invoice_date->format('M d, Y') }}</small>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-warning">GHS {{ number_format($invoice->balance_amount ?? $invoice->total_amount, 2) }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">{{ ucfirst($invoice->status) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('billing.show', $invoice->id) }}" class="btn btn-sm btn-info" title="View Invoice">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('patients.show', $invoice->patient_id) }}" class="btn btn-sm btn-success" title="View Patient">
                                                        <i class="bi bi-person"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
            </div>
        </div>
    </div>
    @endif
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter Revenue Period</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="GET" action="{{ route('revenue.index') }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="{{ $startDate }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="{{ $endDate }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch (Optional)</label>
                        <select name="branch_id" class="form-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Transaction Trail Modal -->
<div class="modal fade" id="transactionTrailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-search me-2"></i>
                    Transaction Trail Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="transactionTrailContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading transaction details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="exportTransactionTrail">
                    <i class="bi bi-download"></i> Export Trail
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Daily Revenue Chart
    const ctx = document.getElementById('dailyRevenueChart').getContext('2d');
    const dailyData = @json($dailyRevenueTrend);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
            datasets: [{
                label: 'Daily Revenue (GHS)',
                data: dailyData.map(d => d.revenue),
                borderColor: 'rgb(13, 110, 253)',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    labels: {
                        color: '#fff'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#fff',
                        callback: function(value) {
                            return 'GHS ' + value.toLocaleString();
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#fff'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            }
        }
    });

    // Transaction Trail Functionality
    let currentTrailData = null;
    let currentTrailType = null;

    // Handle department/service type clicks
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function() {
            const serviceType = this.dataset.serviceType;
            loadTransactionTrail('service-type', serviceType);
        });
    });

    // Handle payment method clicks
    document.querySelectorAll('.clickable-payment-method').forEach(element => {
        element.addEventListener('click', function() {
            const paymentMethod = this.dataset.paymentMethod;
            loadTransactionTrail('payment-method', paymentMethod);
        });
    });

    function loadTransactionTrail(type, identifier) {
        currentTrailType = type;
        const modal = new bootstrap.Modal(document.getElementById('transactionTrailModal'));
        modal.show();

        let url = '';
        let params = new URLSearchParams({
            start_date: '{{ $startDate }}',
            end_date: '{{ $endDate }}'
        });

        if (type === 'service-type') {
            url = '{{ route("revenue.transactions.service-type") }}';
            params.append('service_type', identifier);
        } else if (type === 'payment-method') {
            url = '{{ route("revenue.transactions.payment-method") }}';
            params.append('payment_method', identifier);
        }

        fetch(`${url}?${params}`)
            .then(response => response.json())
            .then(data => {
                currentTrailData = data;
                displayTransactionTrail(data, type, identifier);
            })
            .catch(error => {
                console.error('Error loading transaction trail:', error);
                document.getElementById('transactionTrailContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error loading transaction details. Please try again.
                    </div>
                `;
            });
    }

    function displayTransactionTrail(data, type, identifier) {
        const content = document.getElementById('transactionTrailContent');
        
        let title = '';
        let summary = data.summary;
        
        if (type === 'service-type') {
            title = `Transactions for ${identifier.replace(/_/g, ' ').toUpperCase()}`;
        } else if (type === 'payment-method') {
            title = `Transactions via ${identifier.replace(/_/g, ' ').toUpperCase()}`;
        }

        let html = `
            <div class="mb-4">
                <h6 class="text-primary mb-3">
                    <i class="bi bi-graph-up me-2"></i>
                    ${title}
                </h6>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <div class="stat-label">Total Transactions</div>
                            <div class="stat-value">${summary.total_transactions}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="stat-label">Total Amount</div>
                            <div class="stat-value">GHS ${Number(summary.total_amount).toLocaleString()}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <div class="stat-label">Start Date</div>
                            <div class="stat-value">${summary.date_range.start}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="stat-label">End Date</div>
                            <div class="stat-value">${summary.date_range.end}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        if (data.transactions && data.transactions.length > 0) {
            html += `
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Transaction Type</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>User</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            data.transactions.forEach(transaction => {
                const date = new Date(transaction.created_at).toLocaleString();
                const amount = transaction.properties?.amount ? `GHS ${Number(transaction.properties.amount).toLocaleString()}` : '-';
                const user = transaction.causer?.name || 'System';
                const status = transaction.properties?.status || 'N/A';
                
                html += `
                    <tr>
                        <td><small class="text-muted">${date}</small></td>
                        <td>
                            <span class="badge bg-${transaction.subject_type === 'App\\\\Models\\\\Invoice' ? 'primary' : 'success'}">
                                ${transaction.subject_type === 'App\\\\Models\\\\Invoice' ? 'Invoice' : 'Payment'}
                            </span>
                        </td>
                        <td><strong>${transaction.description}</strong></td>
                        <td><strong class="text-success">${amount}</strong></td>
                        <td><strong>${user}</strong></td>
                        <td><span class="badge bg-info">${status}</span></td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            html += `
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    No transactions found for the selected criteria.
                </div>
            `;
        }

        content.innerHTML = html;
    }

    // Export transaction trail
    document.getElementById('exportTransactionTrail').addEventListener('click', function() {
        if (!currentTrailData) return;
        
        const data = currentTrailData;
        const csvContent = generateCSVFromTrailData(data);
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `transaction-trail-${currentTrailType}-${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    });

    function generateCSVFromTrailData(data) {
        let csv = 'Transaction Trail Export\n';
        csv += `Generated: ${new Date().toLocaleString()}\n`;
        csv += `Total Transactions: ${data.summary.total_transactions}\n`;
        csv += `Total Amount: GHS ${data.summary.total_amount}\n\n`;
        
        csv += 'Date,Type,Description,Amount,User,Status\n';
        
        if (data.transactions) {
            data.transactions.forEach(transaction => {
                const date = new Date(transaction.created_at).toLocaleString();
                const type = transaction.subject_type === 'App\\\\Models\\\\Invoice' ? 'Invoice' : 'Payment';
                const amount = transaction.properties?.amount || '';
                const user = transaction.causer?.name || 'System';
                const status = transaction.properties?.status || 'N/A';
                
                csv += `"${date}","${type}","${transaction.description}","${amount}","${user}","${status}"\n`;
            });
        }
        
        return csv;
    }
</script>
@endpush
@endsection

