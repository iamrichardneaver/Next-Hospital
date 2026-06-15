@extends('layouts.app')

@section('title', 'Debtor Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Debtor Details</h1>
            <p class="text-secondary mb-0">
                @if($debtor->patient)
                    {{ $debtor->patient->first_name }} {{ $debtor->patient->last_name }} - {{ $debtor->branch->name }}
                @else
                    <span class="text-muted">Unknown Patient</span> - {{ $debtor->patient_number_display }} - {{ $debtor->branch->name }}
                @endif
            </p>
        </div>
        <div>
            <a href="{{ route('debtors.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Back to Debtors
            </a>
            <a href="{{ route('debtors.edit', $debtor) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-2"></i>
                Edit
            </a>
        </div>
    </div>

    <!-- Debtor Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-label">Outstanding</div>
                <div class="stat-value">₵{{ number_format($debtor->total_outstanding, 2) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Paid</div>
                <div class="stat-value">₵{{ number_format($debtor->total_paid, 2) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-label">Total Invoiced</div>
                <div class="stat-value">₵{{ number_format($debtor->total_invoiced, 2) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card {{ $debtor->debt_status == 'current' ? 'primary' : ($debtor->debt_status == 'overdue' ? 'warning' : 'danger') }}">
                <div class="stat-icon">
                    <i class="bi bi-flag"></i>
                </div>
                <div class="stat-label">Status</div>
                <div class="stat-value">{{ ucfirst($debtor->debt_status) }}</div>
            </div>
        </div>
    </div>

    <!-- Debtor Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Patient Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        @if($debtor->patient)
                        <tr>
                            <td class="text-muted">Patient Number:</td>
                            <td><strong>{{ $debtor->patient->patient_number }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Contact:</td>
                            <td>{{ $debtor->patient->contact ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email:</td>
                            <td>{{ $debtor->patient->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Address:</td>
                            <td>{{ $debtor->patient->address ?? 'N/A' }}</td>
                        </tr>
                        @else
                        <tr>
                            <td class="text-muted">Patient:</td>
                            <td><strong class="text-muted">Unknown Patient</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Patient Number:</td>
                            <td><span class="text-muted">{{ $debtor->patient_number_display }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Contact:</td>
                            <td><span class="text-muted">N/A</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email:</td>
                            <td><span class="text-muted">N/A</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Address:</td>
                            <td><span class="text-muted">N/A</span></td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Debt Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted">Outstanding Invoices:</td>
                            <td><span class="badge bg-info">{{ $debtor->outstanding_invoices_count }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Overdue Invoices:</td>
                            <td><span class="badge bg-warning">{{ $debtor->overdue_invoices_count }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Days Overdue:</td>
                            <td>
                                @if($debtor->days_overdue > 0)
                                    <span class="text-warning fw-bold">{{ $debtor->days_overdue }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Payment Percentage:</td>
                            <td>{{ $debtor->getPaymentPercentage() }}%</td>
                        </tr>
                        <tr>
                            <td class="text-muted">First Outstanding:</td>
                            <td>{{ $debtor->first_outstanding_date ? $debtor->first_outstanding_date->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Last Invoice:</td>
                            <td>{{ $debtor->last_invoice_date ? $debtor->last_invoice_date->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Debtor Information Tabs -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="debtorTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" 
                                    data-bs-target="#overview" type="button" role="tab">
                                <i class="bi bi-info-circle me-2"></i>Overview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="outstanding-tab" data-bs-toggle="tab" 
                                    data-bs-target="#outstanding" type="button" role="tab">
                                <i class="bi bi-file-text me-2"></i>Outstanding Invoices
                                <span class="badge bg-warning ms-2">{{ $outstandingInvoices->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="overdue-tab" data-bs-toggle="tab" 
                                    data-bs-target="#overdue" type="button" role="tab">
                                <i class="bi bi-exclamation-triangle me-2"></i>Overdue Invoices
                                <span class="badge bg-danger ms-2">{{ $overdueInvoices->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" 
                                    data-bs-target="#payments" type="button" role="tab">
                                <i class="bi bi-clock-history me-2"></i>Payment History
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body">
                    <div class="tab-content" id="debtorTabsContent">
                        <!-- Overview Tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            @if($debtor->notes)
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Notes</h6>
                                <p class="mb-0">{{ $debtor->notes }}</p>
                            </div>
                            @endif
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Payment Summary</h6>
                                    <p class="mb-2">
                                        <strong>Total Invoiced:</strong> ₵{{ number_format($debtor->total_invoiced, 2) }}
                                    </p>
                                    <p class="mb-2">
                                        <strong>Total Paid:</strong> ₵{{ number_format($debtor->total_paid, 2) }}
                                    </p>
                                    <p class="mb-2">
                                        <strong>Outstanding Balance:</strong> ₵{{ number_format($debtor->total_outstanding, 2) }}
                                    </p>
                                    <p class="mb-0">
                                        <strong>Collection Rate:</strong> {{ $debtor->getPaymentPercentage() }}%
                                    </p>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6 class="text-muted">Account Status</h6>
                                    <p class="mb-2">
                                        <strong>Status:</strong> 
                                        @if($debtor->debt_status == 'current')
                                            <span class="badge bg-success">{{ ucfirst($debtor->debt_status) }}</span>
                                        @elseif($debtor->debt_status == 'overdue')
                                            <span class="badge bg-warning">{{ ucfirst($debtor->debt_status) }}</span>
                                        @elseif($debtor->debt_status == 'critical')
                                            <span class="badge bg-danger">{{ ucfirst($debtor->debt_status) }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($debtor->debt_status) }}</span>
                                        @endif
                                    </p>
                                    <p class="mb-2">
                                        <strong>Days Overdue:</strong> 
                                        @if($debtor->days_overdue > 0)
                                            <span class="text-warning fw-bold">{{ $debtor->days_overdue }}</span>
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </p>
                                    <p class="mb-2">
                                        <strong>Last Payment:</strong> 
                                        {{ $debtor->last_payment_date ? $debtor->last_payment_date->format('M d, Y') : 'Never' }}
                                    </p>
                                    <p class="mb-0">
                                        <strong>Outstanding Invoices:</strong> {{ $debtor->outstanding_invoices_count }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Outstanding Invoices Tab -->
                        <div class="tab-pane fade" id="outstanding" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Date</th>
                                            <th>Due Date</th>
                                            <th>Amount</th>
                                            <th>Paid</th>
                                            <th>Balance</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($outstandingInvoices as $invoice)
                                        <tr>
                                            <td><strong class="text-primary">{{ $invoice->invoice_number }}</strong></td>
                                            <td>{{ $invoice->invoice_date ? $invoice->invoice_date->format('M d, Y') : 'N/A' }}</td>
                                            <td>{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'N/A' }}</td>
                                            <td>₵{{ number_format($invoice->total_amount, 2) }}</td>
                                            <td>₵{{ number_format($invoice->getTotalPaid(), 2) }}</td>
                                            <td>₵{{ number_format($invoice->getRemainingBalance(), 2) }}</td>
                                            <td>
                                                @if($invoice->status == 'paid')
                                                    <span class="badge bg-success">{{ ucfirst($invoice->status) }}</span>
                                                @elseif($invoice->status == 'partial')
                                                    <span class="badge bg-warning">{{ ucfirst($invoice->status) }}</span>
                                                @elseif($invoice->status == 'overdue')
                                                    <span class="badge bg-danger">{{ ucfirst($invoice->status) }}</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('billing.show', $invoice) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                                                    <p class="mt-2 mb-0">No outstanding invoices</p>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Overdue Invoices Tab -->
                        <div class="tab-pane fade" id="overdue" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Date</th>
                                            <th>Due Date</th>
                                            <th>Amount</th>
                                            <th>Paid</th>
                                            <th>Balance</th>
                                            <th>Days Overdue</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($overdueInvoices as $invoice)
                                        <tr>
                                            <td><strong class="text-primary">{{ $invoice->invoice_number }}</strong></td>
                                            <td>{{ $invoice->invoice_date ? $invoice->invoice_date->format('M d, Y') : 'N/A' }}</td>
                                            <td>{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'N/A' }}</td>
                                            <td>₵{{ number_format($invoice->total_amount, 2) }}</td>
                                            <td>₵{{ number_format($invoice->getTotalPaid(), 2) }}</td>
                                            <td>₵{{ number_format($invoice->getRemainingBalance(), 2) }}</td>
                                            <td>
                                                <span class="text-warning fw-bold">
                                                    {{ $invoice->due_date ? now()->diffInDays($invoice->due_date) : 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('billing.show', $invoice) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                                                    <p class="mt-2 mb-0">No overdue invoices</p>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Payment History Tab -->
                        <div class="tab-pane fade" id="payments" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Reference</th>
                                            <th>Remaining Balance</th>
                                            <th>Processed By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($paymentHistory as $payment)
                                        <tr>
                                            <td>{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : 'N/A' }}</td>
                                            <td><strong class="text-success">₵{{ number_format($payment->payment_amount, 2) }}</strong></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ ucfirst($payment->payment_method) }}
                                                </span>
                                            </td>
                                            <td>{{ $payment->reference_number ?? 'N/A' }}</td>
                                            <td>₵{{ number_format($payment->remaining_balance, 2) }}</td>
                                            <td>{{ $payment->processor->name ?? 'N/A' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-clock-history" style="font-size: 2rem;"></i>
                                                    <p class="mt-2 mb-0">No payment history</p>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            
                            @if($paymentHistory->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $paymentHistory->links() }}
                            </div>
                            @endif
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
function updateStatus() {
    if (confirm('Are you sure you want to update this debtor\'s status?')) {
        fetch(`/debtors/{{ $debtor->id }}/update-status`, {
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
</script>
@endpush