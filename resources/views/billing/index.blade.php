@extends('layouts.app')

@section('title', 'Billing & Invoices')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Billing & Invoices</h1>
            <p class="text-secondary mb-0">Manage invoices and payments</p>
        </div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('billing.export'),
                'permission' => 'view_invoices',
            ])
            @can('create_invoices')
            <a href="{{ route('billing.create') }}" class="btn btn-primary">
                <i class="bi bi-receipt-cutoff"></i> New Invoice
            </a>
            @endcan
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-label">Total Invoices</div>
                <div class="stat-value">{{ number_format($statistics['total']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-label">Pending Payment</div>
                <div class="stat-value">{{ number_format($statistics['pending']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Paid Invoices</div>
                <div class="stat-value">{{ number_format($statistics['paid']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">GH₵{{ number_format($statistics['total_revenue'], 2) }}</div>
            </div>
        </div>
    </div>
    
    <!-- Invoices List -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 text-dark">All Invoices</h5>
                </div>
                <div class="col-md-6">
                    <input type="text" 
                           class="form-control" 
                           placeholder="Search invoices...">
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                        <tr>
                            <td><strong class="text-primary">{{ $invoice->invoice_number }}</strong></td>
                            <td>
                                <div>
                                    @if($invoice->patient)
                                        <div class="fw-bold">{{ $invoice->patient->first_name }} {{ $invoice->patient->last_name }}</div>
                                        <small class="text-muted">{{ $invoice->patient->patient_number ?? 'N/A' }}</small>
                                    @else
                                        <div class="fw-bold text-danger">Patient Not Found</div>
                                        <small class="text-muted">Patient ID: {{ $invoice->patient_id ?? 'N/A' }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('M d, Y') }}</td>
                            <td><strong>GH₵{{ number_format($invoice->total_amount, 2) }}</strong></td>
                            <td>
                                @if($invoice->payment_method)
                                    <span class="badge bg-secondary">{{ strtoupper($invoice->payment_method) }}</span>
                                @else
                                    <span class="badge bg-light text-dark">Not Paid</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'pending' => 'warning',
                                        'paid' => 'success',
                                        'cancelled' => 'danger',
                                        'refunded' => 'info'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$invoice->status] ?? 'secondary' }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('billing.show', $invoice) }}" 
                                       class="btn btn-sm btn-info" 
                                       title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    @can('edit_invoices')
                                    <a href="{{ route('billing.edit', $invoice) }}" 
                                       class="btn btn-sm btn-warning" 
                                       title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    
                                    <a href="{{ route('billing.download', $invoice) }}" 
                                       class="btn btn-sm btn-success" 
                                       target="_blank"
                                       title="Download PDF">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-receipt text-secondary" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="text-secondary mt-2 mb-0">No invoices found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($invoices->hasPages())
        <div class="card-footer">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
