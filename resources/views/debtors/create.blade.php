@extends('layouts.app')

@section('title', 'Add New Debtor')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Add New Debtor</h1>
            <p class="text-secondary mb-0">Create a new debtor record for tracking outstanding balances</p>
        </div>
        <div>
            <a href="{{ route('debtors.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Back to Debtors
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
                    <form method="POST" action="{{ route('debtors.store') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                                <select class="form-select @error('patient_id') is-invalid @enderror" id="patient_id" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
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
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
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
                                           value="{{ old('total_outstanding_amount') }}" step="0.01" min="0" required>
                                </div>
                                @error('total_outstanding_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="debt_status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('debt_status') is-invalid @enderror" id="debt_status" name="debt_status" required>
                                    <option value="">Select Status</option>
                                    <option value="current" {{ old('debt_status') == 'current' ? 'selected' : '' }}>Current</option>
                                    <option value="overdue" {{ old('debt_status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                    <option value="critical" {{ old('debt_status') == 'critical' ? 'selected' : '' }}>Critical</option>
                                    <option value="on_payment_plan" {{ old('debt_status') == 'on_payment_plan' ? 'selected' : '' }}>On Payment Plan</option>
                                    <option value="settled" {{ old('debt_status') == 'settled' ? 'selected' : '' }}>Settled</option>
                                    <option value="bad_debt" {{ old('debt_status') == 'bad_debt' ? 'selected' : '' }}>Bad Debt</option>
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
                                       value="{{ old('last_payment_date') }}">
                                @error('last_payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="last_reminder_sent_at" class="form-label">Last Reminder Sent</label>
                                <input type="date" class="form-control @error('last_reminder_sent_at') is-invalid @enderror" 
                                       id="last_reminder_sent_at" name="last_reminder_sent_at" 
                                       value="{{ old('last_reminder_sent_at') }}">
                                @error('last_reminder_sent_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="4" 
                                      placeholder="Additional notes about this debtor...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('debtors.index') }}" class="btn btn-outline-secondary me-2">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>
                                Create Debtor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Help & Guidelines</h5>
                </div>
                <div class="card-body">
                    <h6 class="text-muted">Creating a Debtor Record</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Select the patient with outstanding balance
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Choose the appropriate branch
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Enter the exact outstanding amount
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Set the appropriate debt status
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            Add any relevant notes
                        </li>
                    </ul>
                    
                    <hr>
                    
                    <h6 class="text-muted">Status Definitions</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-1"><strong>Current:</strong> Recently overdue</li>
                        <li class="mb-1"><strong>Overdue:</strong> Past due date</li>
                        <li class="mb-1"><strong>Critical:</strong> Long overdue</li>
                        <li class="mb-1"><strong>On Payment Plan:</strong> Making regular payments</li>
                        <li class="mb-1"><strong>Settled:</strong> Fully paid</li>
                        <li class="mb-1"><strong>Bad Debt:</strong> Uncollectible</li>
                    </ul>
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
    
    // Calculate outstanding amount based on patient selection
    const patientSelect = document.getElementById('patient_id');
    const outstandingAmount = document.getElementById('total_outstanding_amount');
    
    patientSelect.addEventListener('change', function() {
        if (this.value) {
            // You can add AJAX call here to fetch patient's outstanding amount
            // For now, we'll leave it as manual entry
        }
    });
});
</script>
@endpush