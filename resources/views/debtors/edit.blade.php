@extends('layouts.app')

@section('title', 'Edit Debtor')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Edit Debtor</h1>
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
            <a href="{{ route('debtors.show', $debtor) }}" class="btn btn-outline-primary">
                <i class="bi bi-eye me-2"></i>
                View Details
            </a>
        </div>
    </div>

    <!-- Debtor Form -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Debtor Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('debtors.update', $debtor) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                                <select class="form-select @error('patient_id') is-invalid @enderror" id="patient_id" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}" {{ old('patient_id', $debtor->patient_id) == $patient->id ? 'selected' : '' }}>
                                            {{ $patient->first_name }} {{ $patient->last_name }} ({{ $patient->patient_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('patient_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $debtor->branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="total_outstanding_amount" class="form-label">Outstanding Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₵</span>
                                    <input type="number" class="form-control @error('total_outstanding_amount') is-invalid @enderror" 
                                           id="total_outstanding_amount" name="total_outstanding_amount" 
                                           value="{{ old('total_outstanding_amount', $debtor->total_outstanding_amount) }}" step="0.01" min="0" required>
                                </div>
                                @error('total_outstanding_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="debt_status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('debt_status') is-invalid @enderror" id="debt_status" name="debt_status" required>
                                    <option value="">Select Status</option>
                                    <option value="current" {{ old('debt_status', $debtor->debt_status) == 'current' ? 'selected' : '' }}>Current</option>
                                    <option value="overdue" {{ old('debt_status', $debtor->debt_status) == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                    <option value="critical" {{ old('debt_status', $debtor->debt_status) == 'critical' ? 'selected' : '' }}>Critical</option>
                                    <option value="on_payment_plan" {{ old('debt_status', $debtor->debt_status) == 'on_payment_plan' ? 'selected' : '' }}>On Payment Plan</option>
                                    <option value="settled" {{ old('debt_status', $debtor->debt_status) == 'settled' ? 'selected' : '' }}>Settled</option>
                                    <option value="bad_debt" {{ old('debt_status', $debtor->debt_status) == 'bad_debt' ? 'selected' : '' }}>Bad Debt</option>
                                </select>
                                @error('debt_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="last_payment_date" class="form-label">Last Payment Date</label>
                                <input type="date" class="form-control @error('last_payment_date') is-invalid @enderror" 
                                       id="last_payment_date" name="last_payment_date" 
                                       value="{{ old('last_payment_date', $debtor->last_payment_date?->format('Y-m-d')) }}">
                                @error('last_payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="last_reminder_sent_at" class="form-label">Last Reminder Sent</label>
                                <input type="date" class="form-control @error('last_reminder_sent_at') is-invalid @enderror" 
                                       id="last_reminder_sent_at" name="last_reminder_sent_at" 
                                       value="{{ old('last_reminder_sent_at', $debtor->last_reminder_sent_at?->format('Y-m-d')) }}">
                                @error('last_reminder_sent_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="4" 
                                      placeholder="Additional notes about this debtor...">{{ old('notes', $debtor->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('debtors.show', $debtor) }}" class="btn btn-outline-secondary me-2">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>
                                Update Debtor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Current Debtor Info</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted">Patient:</td>
                            <td>
                                @if($debtor->patient)
                                    <strong>{{ $debtor->patient->first_name }} {{ $debtor->patient->last_name }}</strong>
                                @else
                                    <strong class="text-muted">Unknown Patient</strong>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Patient Number:</td>
                            <td>
                                @if($debtor->patient)
                                    {{ $debtor->patient->patient_number }}
                                @else
                                    <span class="text-muted">{{ $debtor->patient_number_display }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Branch:</td>
                            <td>{{ $debtor->branch->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Current Status:</td>
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
                        </tr>
                        <tr>
                            <td class="text-muted">Outstanding:</td>
                            <td><strong class="text-danger">₵{{ number_format($debtor->total_outstanding_amount, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Created:</td>
                            <td>{{ $debtor->created_at ? $debtor->created_at->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Last Updated:</td>
                            <td>{{ $debtor->updated_at ? $debtor->updated_at->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('debtors.payment-history', $debtor) }}" class="btn btn-outline-info">
                            <i class="bi bi-clock-history me-2"></i>
                            View Payment History
                        </a>
                        <a href="{{ route('debtors.outstanding-invoices', $debtor) }}" class="btn btn-outline-warning">
                            <i class="bi bi-file-text me-2"></i>
                            Outstanding Invoices
                        </a>
                        <button class="btn btn-outline-success" onclick="recordPayment()">
                            <i class="bi bi-plus-circle me-2"></i>
                            Record Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-populate last payment date if status is settled
    const statusSelect = document.getElementById('debt_status');
    const lastPaymentDate = document.getElementById('last_payment_date');
    
    statusSelect.addEventListener('change', function() {
        if (this.value === 'settled') {
            lastPaymentDate.value = new Date().toISOString().split('T')[0];
        }
    });
});

function recordPayment() {
    // You can implement a modal or redirect to payment recording page
    if (confirm('This will open the payment recording form. Continue?')) {
        // Implement payment recording functionality
        alert('Payment recording feature will be implemented here');
    }
}
</script>
@endpush