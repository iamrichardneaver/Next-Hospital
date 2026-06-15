@extends('layouts.app')

@section('title', 'Debtors Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Debtors Management</h1>
            <p class="text-secondary mb-0">Track and manage outstanding patient balances</p>
        </div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('debtors.export'),
                'permission' => 'view_debtors',
                'params' => request()->only(['status', 'branch_id', 'min_amount', 'max_amount', 'min_days_overdue', 'max_days_overdue', 'search']),
            ])
            @can('create_debtors')
            <a href="{{ route('debtors.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>
                Add Debtor
            </a>
            @endcan
            <a href="{{ route('debtors.report') }}" class="btn btn-outline-primary ms-2">
                <i class="bi bi-file-earmark-text me-2"></i>
                Generate Report
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-label">Total Debtors</div>
                <div class="stat-value">{{ number_format($statistics['total_debtors']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="stat-label">Overdue</div>
                <div class="stat-value">{{ number_format($statistics['overdue_debtors']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                </div>
                <div class="stat-label">Critical</div>
                <div class="stat-value">{{ number_format($statistics['critical_debtors']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-currency-exchange"></i>
                </div>
                <div class="stat-label">Total Outstanding</div>
                <div class="stat-value">₵{{ number_format($statistics['total_outstanding'], 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Debtors List Card -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 text-dark">All Debtors</h5>
                </div>
                <div class="col-md-6">
                    <form method="GET" action="{{ route('debtors.index') }}" class="row g-2">
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ $filters['search'] ?? '' }}" placeholder="Search debtors...">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="current" {{ ($filters['status'] ?? '') == 'current' ? 'selected' : '' }}>Current</option>
                                <option value="overdue" {{ ($filters['status'] ?? '') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                <option value="critical" {{ ($filters['status'] ?? '') == 'critical' ? 'selected' : '' }}>Critical</option>
                                <option value="resolved" {{ ($filters['status'] ?? '') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ ($filters['branch_id'] ?? '') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control" id="min_amount" name="min_amount" 
                                   value="{{ $filters['min_amount'] ?? '' }}" placeholder="Min Amount" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control" id="max_amount" name="max_amount" 
                                   value="{{ $filters['max_amount'] ?? '' }}" placeholder="Max Amount" step="0.01">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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
                            <th>Status</th>
                            <th>Days Overdue</th>
                            <th>Invoices</th>
                            <th>Actions</th>
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
                                <span class="badge bg-info">{{ $debtor->outstanding_invoices_count }}</span>
                            </td>
                            <td>
                                <div class="dropdown position-static">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        @can('view_debtors')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('debtors.show', $debtor) }}">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('debtors.payment-history', $debtor) }}">
                                                <i class="bi bi-clock-history me-2"></i>Payment History
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('debtors.outstanding-invoices', $debtor) }}">
                                                <i class="bi bi-file-text me-2"></i>Outstanding Invoices
                                            </a>
                                        </li>
                                        @endcan
                                        @can('edit_debtors')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item" onclick="updateStatus({{ $debtor->id }})">
                                                <i class="bi bi-arrow-clockwise me-2"></i>Update Status
                                            </button>
                                        </li>
                                        @endcan
                                        @can('edit_debtors')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('debtors.edit', $debtor) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        @endcan
                                        @can('delete_debtors')
                                        <li>
                                            <form method="POST" action="{{ route('debtors.destroy', $debtor) }}" 
                                                  onsubmit="return confirm('Are you sure you want to delete this debtor record?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mt-3 mb-0">No debtors found</p>
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
@endsection

@push('scripts')
<script>
function updateStatus(debtorId) {
    if (confirm('Are you sure you want to update this debtor\'s status?')) {
        fetch(`/debtors/${debtorId}/update-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to update debtor status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating debtor status');
        });
    }
}

function bulkUpdate() {
    if (confirm('Are you sure you want to refresh all debtor records? This may take a moment.')) {
        fetch('/debtors/bulk-update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Failed to update debtors: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating debtors');
        });
    }
}

function sendReminders() {
    if (confirm('Are you sure you want to send payment reminders to all overdue debtors?')) {
        fetch('/debtors/send-reminders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Payment reminders sent successfully to ' + data.reminders.length + ' debtors');
            } else {
                alert('Failed to send reminders: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while sending reminders');
        });
    }
}
</script>
@endpush
