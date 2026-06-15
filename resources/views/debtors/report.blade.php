@extends('layouts.app')

@section('title', 'Debtors Report')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Debtors Report</h1>
            <p class="text-secondary mb-0">Comprehensive analysis of debtor accounts and outstanding balances</p>
        </div>
        <div>
            <a href="{{ route('debtors.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Back to Debtors
            </a>
            <button class="btn btn-primary" onclick="printReport()">
                <i class="bi bi-printer me-2"></i>
                Print Report
            </button>
        </div>
    </div>

    <!-- Report Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-label">Total Debtors</div>
                <div class="stat-value">{{ number_format($reportData['total_debtors']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="stat-label">Overdue Accounts</div>
                <div class="stat-value">{{ number_format($reportData['overdue_debtors']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                </div>
                <div class="stat-label">Critical Accounts</div>
                <div class="stat-value">{{ number_format($reportData['critical_debtors']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-currency-exchange"></i>
                </div>
                <div class="stat-label">Total Outstanding</div>
                <div class="stat-value">₵{{ number_format($reportData['total_outstanding'], 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Report Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('debtors.report') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="current" {{ request('status') == 'current' ? 'selected' : '' }}>Current</option>
                                <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                <option value="critical" {{ request('status') == 'critical' ? 'selected' : '' }}>Critical</option>
                                <option value="settled" {{ request('status') == 'settled' ? 'selected' : '' }}>Settled</option>
                                <option value="bad_debt" {{ request('status') == 'bad_debt' ? 'selected' : '' }}>Bad Debt</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="branch_id" class="form-label">Branch</label>
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-1"></i>
                                    Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Report -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Detailed Debtors Report</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Patient</th>
                                    <th>Branch</th>
                                    <th>Outstanding</th>
                                    <th>Paid</th>
                                    <th>Total Invoiced</th>
                                    <th>Status</th>
                                    <th>Days Overdue</th>
                                    <th>Last Payment</th>
                                    <th>Collection Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($debtors as $debtor)
                                <tr>
                                    <td><strong class="text-primary">{{ $debtor->id }}</strong></td>
                                    <td>
                                        <div>
                                            @if($debtor->patient)
                                                <div class="fw-bold">{{ $debtor->patient->first_name }} {{ $debtor->patient->last_name }}</div>
                                                <small class="text-muted">{{ $debtor->patient->patient_number }}</small>
                                            @else
                                                <strong class="text-muted">Unknown Patient</strong>
                                                <br>
                                                <small class="text-muted">{{ $debtor->patient_number_display }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $debtor->branch->name }}</td>
                                    <td>
                                        <span class="fw-bold {{ $debtor->total_outstanding > 0 ? 'text-danger' : 'text-success' }}">
                                            ₵{{ number_format($debtor->total_outstanding, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">
                                            ₵{{ number_format($debtor->total_paid, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-info">
                                            ₵{{ number_format($debtor->total_invoiced, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($debtor->debt_status == 'current')
                                            <span class="badge bg-success">{{ ucfirst($debtor->debt_status) }}</span>
                                        @elseif($debtor->debt_status == 'overdue')
                                            <span class="badge bg-warning">{{ ucfirst($debtor->debt_status) }}</span>
                                        @elseif($debtor->debt_status == 'critical')
                                            <span class="badge bg-danger">{{ ucfirst($debtor->debt_status) }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($debtor->debt_status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($debtor->days_overdue > 0)
                                            <span class="text-warning fw-bold">{{ $debtor->days_overdue }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $debtor->last_payment_date ? $debtor->last_payment_date->format('M d, Y') : 'Never' }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $debtor->getPaymentPercentage() >= 80 ? 'bg-success' : ($debtor->getPaymentPercentage() >= 50 ? 'bg-warning' : 'bg-danger') }}">
                                            {{ $debtor->getPaymentPercentage() }}%
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                            <p class="mt-3 mb-0">No debtors found for the selected criteria</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                @if($debtors->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-center">
                        {{ $debtors->appends(request()->query())->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Collection Performance</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border-end">
                                <h4 class="text-success">{{ $reportData['collection_rate'] }}%</h4>
                                <small class="text-muted">Collection Rate</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-end">
                                <h4 class="text-info">{{ $reportData['average_outstanding'] }}</h4>
                                <small class="text-muted">Avg Outstanding</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <h4 class="text-warning">{{ $reportData['average_days_overdue'] }}</h4>
                            <small class="text-muted">Avg Days Overdue</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Status Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-3">
                            <h6 class="text-success">{{ $reportData['status_distribution']['current'] ?? 0 }}</h6>
                            <small class="text-muted">Current</small>
                        </div>
                        <div class="col-3">
                            <h6 class="text-warning">{{ $reportData['status_distribution']['overdue'] ?? 0 }}</h6>
                            <small class="text-muted">Overdue</small>
                        </div>
                        <div class="col-3">
                            <h6 class="text-danger">{{ $reportData['status_distribution']['critical'] ?? 0 }}</h6>
                            <small class="text-muted">Critical</small>
                        </div>
                        <div class="col-3">
                            <h6 class="text-secondary">{{ $reportData['status_distribution']['settled'] ?? 0 }}</h6>
                            <small class="text-muted">Settled</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function printReport() {
    window.print();
}

// Set default date range to last 30 days if not specified
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    if (!startDate.value) {
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        startDate.value = thirtyDaysAgo.toISOString().split('T')[0];
    }
    
    if (!endDate.value) {
        endDate.value = new Date().toISOString().split('T')[0];
    }
});
</script>
@endpush