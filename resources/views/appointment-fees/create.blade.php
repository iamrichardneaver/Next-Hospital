@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-plus-circle me-2"></i>Create Appointment Fee</h2>
            <p class="text-muted mb-0">Set up pricing for appointments</p>
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

                    <form method="POST" action="{{ route('appointment-fees.store') }}" id="feeForm">
                        @csrf

                        <!-- Branch & Doctor Selection -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Branch <span class="text-danger">*</span></label>
                                <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                    <option value="">-- Select Branch --</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">The branch where this fee applies</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Doctor (Optional)</label>
                                <select name="doctor_id" id="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror">
                                    <option value="">-- Branch-Level Fee (All Doctors) --</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                            Dr. {{ $doctor->name }} - {{ $doctor->staffProfile->specialization ?? 'General' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('doctor_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Leave empty for branch-level pricing, or select a doctor for custom pricing</small>
                            </div>
                        </div>

                        <!-- Appointment Type & Category -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Appointment Type <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="appointment_type" id="type_in_person" value="in-person" {{ old('appointment_type', 'in-person') == 'in-person' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="type_in_person">
                                            <i class="fas fa-hospital me-1"></i>In-Person
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="appointment_type" id="type_teleconsultation" value="teleconsultation" {{ old('appointment_type') == 'teleconsultation' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="type_teleconsultation">
                                            <i class="fas fa-video me-1"></i>Teleconsultation
                                        </label>
                                    </div>
                                </div>
                                @error('appointment_type')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Fee Category <span class="text-danger">*</span></label>
                                <select name="fee_category" id="fee_category" class="form-select @error('fee_category') is-invalid @enderror" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="general" {{ old('fee_category') == 'general' ? 'selected' : '' }}>General Consultation</option>
                                    <option value="specialist" {{ old('fee_category') == 'specialist' ? 'selected' : '' }}>Specialist Consultation</option>
                                    <option value="emergency" {{ old('fee_category') == 'emergency' ? 'selected' : '' }}>Emergency Consultation</option>
                                    <option value="follow-up" {{ old('fee_category') == 'follow-up' ? 'selected' : '' }}>Follow-up Visit</option>
                                    <option value="pediatric" {{ old('fee_category') == 'pediatric' ? 'selected' : '' }}>Pediatric Consultation</option>
                                    <option value="antenatal" {{ old('fee_category') == 'antenatal' ? 'selected' : '' }}>Antenatal Care</option>
                                </select>
                                @error('fee_category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Pricing Details -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Base Fee <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="base_fee" id="base_fee" class="form-control @error('base_fee') is-invalid @enderror" 
                                           value="{{ old('base_fee') }}" step="0.01" min="0" required>
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
                                           value="{{ old('platform_fee', 0) }}" step="0.01" min="0">
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
                                           value="{{ old('tax_rate', 0) }}" step="0.01" min="0" max="100">
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
                                    <option value="GHS" {{ old('currency', 'GHS') == 'GHS' ? 'selected' : '' }}>GHS - Ghana Cedis</option>
                                    <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD - US Dollars</option>
                                    <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR - Euros</option>
                                    <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP - British Pounds</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Total Fee (Calculated)</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="total_currency">GHS</span>
                                    <input type="text" id="total_fee" class="form-control bg-light" readonly value="0.00">
                                </div>
                                <small class="form-text text-muted">Auto-calculated: Base + Platform + Tax</small>
                            </div>
                        </div>

                        <!-- Effective Period -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Effective From</label>
                                <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" 
                                       value="{{ old('effective_from', date('Y-m-d')) }}">
                                @error('effective_from')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Leave empty for immediate effect</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Effective Until</label>
                                <input type="date" name="effective_until" class="form-control @error('effective_until') is-invalid @enderror" 
                                       value="{{ old('effective_until') }}">
                                @error('effective_until')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Leave empty for no expiry</small>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Optional notes about this fee structure</small>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active (Fee is currently in use)
                                </label>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Appointment Fee
                            </button>
                            <a href="{{ route('appointment-fees.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Sidebar -->
        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle me-2"></i>Pricing Guide
                    </h5>

                    <h6 class="mt-3">Appointment Types:</h6>
                    <ul class="small">
                        <li><strong>In-Person:</strong> Physical visit to hospital/clinic</li>
                        <li><strong>Teleconsultation:</strong> Video call consultation (usually cheaper)</li>
                    </ul>

                    <h6 class="mt-3">Fee Levels:</h6>
                    <ul class="small">
                        <li><strong>Branch-Level:</strong> Leave doctor empty - applies to all doctors in the branch</li>
                        <li><strong>Doctor-Specific:</strong> Select a doctor - custom pricing for that doctor only</li>
                    </ul>

                    <h6 class="mt-3">Fee Categories:</h6>
                    <ul class="small">
                        <li><strong>General:</strong> Standard consultation</li>
                        <li><strong>Specialist:</strong> Specialist doctors (usually higher)</li>
                        <li><strong>Emergency:</strong> Emergency consultations</li>
                        <li><strong>Follow-up:</strong> Follow-up visits (usually lower)</li>
                    </ul>

                    <div class="alert alert-info mt-3">
                        <strong><i class="fas fa-lightbulb me-1"></i>Tip:</strong>
                        Set teleconsultation fees lower than in-person to encourage virtual visits!
                    </div>

                    <div class="alert alert-warning mt-3">
                        <strong><i class="fas fa-exclamation-triangle me-1"></i>Priority:</strong>
                        Doctor-specific fees override branch-level fees.
                    </div>
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

