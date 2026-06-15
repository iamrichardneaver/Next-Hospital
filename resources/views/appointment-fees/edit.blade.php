@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-edit me-2"></i>Edit Appointment Fee</h2>
            <p class="text-muted mb-0">Update pricing for appointments</p>
        </div>
        <a href="{{ route('appointment-fees.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>Validation Errors
                            </h6>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('appointment-fees.update', $appointmentFee) }}" id="feeForm">
                        @csrf
                        @method('PUT')

                        <!-- Fee Info (Read-only) -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Fee Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Branch:</strong> {{ $appointmentFee->branch->name }}<br>
                                    <strong>Doctor:</strong> {{ $appointmentFee->doctor ? 'Dr. ' . $appointmentFee->doctor->name : 'Branch-Level (All Doctors)' }}<br>
                                </div>
                                <div class="col-md-6">
                                    <strong>Type:</strong> {{ ucfirst($appointmentFee->appointment_type) }}<br>
                                    <strong>Category:</strong> {{ ucfirst($appointmentFee->fee_category) }}
                                </div>
                            </div>
                            <small class="text-muted">Note: Branch, Doctor, Type, and Category cannot be changed. Create a new fee if needed.</small>
                        </div>

                        <!-- Pricing Details -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Base Fee <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="base_fee" id="base_fee" class="form-control @error('base_fee') is-invalid @enderror" 
                                           value="{{ old('base_fee', $appointmentFee->base_fee) }}" step="0.01" min="0" required>
                                    @error('base_fee')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Main consultation fee</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Platform Fee</label>
                                <div class="input-group">
                                    <input type="number" name="platform_fee" id="platform_fee" class="form-control @error('platform_fee') is-invalid @enderror" 
                                           value="{{ old('platform_fee', $appointmentFee->platform_fee) }}" step="0.01" min="0">
                                    @error('platform_fee')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Optional platform/service fee</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Tax Rate (%)</label>
                                <div class="input-group">
                                    <input type="number" name="tax_rate" id="tax_rate" class="form-control @error('tax_rate') is-invalid @enderror" 
                                           value="{{ old('tax_rate', $appointmentFee->tax_rate) }}" step="0.01" min="0" max="100">
                                    <span class="input-group-text">%</span>
                                    @error('tax_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">VAT/Tax percentage</small>
                            </div>
                        </div>

                        <!-- Currency -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Currency <span class="text-danger">*</span></label>
                                <select name="currency" id="currency" class="form-select @error('currency') is-invalid @enderror" required>
                                    <option value="GHS" {{ old('currency', $appointmentFee->currency) == 'GHS' ? 'selected' : '' }}>GHS - Ghana Cedis</option>
                                    <option value="USD" {{ old('currency', $appointmentFee->currency) == 'USD' ? 'selected' : '' }}>USD - US Dollars</option>
                                    <option value="EUR" {{ old('currency', $appointmentFee->currency) == 'EUR' ? 'selected' : '' }}>EUR - Euros</option>
                                    <option value="GBP" {{ old('currency', $appointmentFee->currency) == 'GBP' ? 'selected' : '' }}>GBP - British Pounds</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Total Fee (Calculated)</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="total_currency">{{ $appointmentFee->currency }}</span>
                                    <input type="text" id="total_fee" class="form-control bg-light" readonly value="{{ number_format($appointmentFee->calculateTotalFee(), 2) }}">
                                </div>
                                <small class="form-text text-muted">Auto-calculated: Base + Platform + Tax</small>
                            </div>
                        </div>

                        <!-- Effective Period -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Effective From</label>
                                <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" 
                                       value="{{ old('effective_from', $appointmentFee->effective_from?->format('Y-m-d')) }}">
                                @error('effective_from')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Effective Until</label>
                                <input type="date" name="effective_until" class="form-control @error('effective_until') is-invalid @enderror" 
                                       value="{{ old('effective_until', $appointmentFee->effective_until?->format('Y-m-d')) }}">
                                @error('effective_until')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $appointmentFee->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $appointmentFee->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active (Fee is currently in use)
                                </label>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Appointment Fee
                            </button>
                            <a href="{{ route('appointment-fees.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-history me-2"></i>Change History
                    </h5>

                    <div class="small">
                        <p class="mb-1">
                            <strong>Created:</strong><br>
                            {{ $appointmentFee->created_at->format('M d, Y H:i') }}<br>
                            by {{ $appointmentFee->creator->name ?? 'System' }}
                        </p>

                        @if($appointmentFee->updated_at != $appointmentFee->created_at)
                            <p class="mb-1">
                                <strong>Last Updated:</strong><br>
                                {{ $appointmentFee->updated_at->format('M d, Y H:i') }}<br>
                                by {{ $appointmentFee->updater->name ?? 'System' }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card bg-light mt-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-calculator me-2"></i>Current Breakdown
                    </h5>

                    @php
                        $breakdown = $appointmentFee->getFeeBreakdown();
                    @endphp

                    <table class="table table-sm small">
                        <tr>
                            <td>Base Fee:</td>
                            <td class="text-end">{{ $breakdown['currency'] }} {{ number_format($breakdown['base_fee'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Platform Fee:</td>
                            <td class="text-end">{{ $breakdown['currency'] }} {{ number_format($breakdown['platform_fee'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-end">{{ $breakdown['currency'] }} {{ number_format($breakdown['subtotal'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Tax ({{ $breakdown['tax_rate'] }}%):</td>
                            <td class="text-end">{{ $breakdown['currency'] }} {{ number_format($breakdown['tax_amount'], 2) }}</td>
                        </tr>
                        <tr class="fw-bold">
                            <td>Total Fee:</td>
                            <td class="text-end">{{ $breakdown['currency'] }} {{ number_format($breakdown['total'], 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseFeeInput = document.getElementById('base_fee');
    const platformFeeInput = document.getElementById('platform_fee');
    const taxRateInput = document.getElementById('tax_rate');
    const totalFeeInput = document.getElementById('total_fee');
    const currencySelect = document.getElementById('currency');
    const totalCurrencySpan = document.getElementById('total_currency');

    function calculateTotal() {
        const baseFee = parseFloat(baseFeeInput.value) || 0;
        const platformFee = parseFloat(platformFeeInput.value) || 0;
        const taxRate = parseFloat(taxRateInput.value) || 0;

        const subtotal = baseFee + platformFee;
        const taxAmount = subtotal * (taxRate / 100);
        const total = subtotal + taxAmount;

        totalFeeInput.value = total.toFixed(2);
        totalCurrencySpan.textContent = currencySelect.value;
    }

    baseFeeInput.addEventListener('input', calculateTotal);
    platformFeeInput.addEventListener('input', calculateTotal);
    taxRateInput.addEventListener('input', calculateTotal);
    currencySelect.addEventListener('change', calculateTotal);

    // Calculate on page load
    calculateTotal();
});
</script>
@endpush
@endsection

