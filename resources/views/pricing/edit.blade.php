@extends('layouts.app')

@section('title', 'Edit Service Pricing')

@section('content')
<div class="container-fluid px-4">
    {{-- Page Header --}}
    <div class="mb-4">
        <h1 class="h3 mb-1" style="color: #1e3a5f;">
            <i class="bi bi-pencil me-2"></i>Edit Service Pricing
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pricing.index') }}">Pricing</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pricing.show', $pricing->id) }}">{{ $pricing->service_name }}</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <form action="{{ route('pricing.update', $pricing->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                {{-- Service Information Card --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark"><i class="bi bi-info-circle me-2"></i>Service Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="service_id" class="form-label">Service ID <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('service_id') is-invalid @enderror" 
                                       id="service_id" 
                                       name="service_id" 
                                       value="{{ old('service_id', $pricing->service_id) }}" 
                                       required>
                                <small class="text-muted">Unique identifier for this service</small>
                                @error('service_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="service_name" class="form-label">Service Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('service_name') is-invalid @enderror" 
                                       id="service_name" 
                                       name="service_name" 
                                       value="{{ old('service_name', $pricing->service_name) }}" 
                                       required>
                                @error('service_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="service_type" class="form-label">Service Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('service_type') is-invalid @enderror" 
                                        id="service_type" 
                                        name="service_type" 
                                        required>
                                    <option value="">Select Service Type</option>
                                    @foreach($serviceTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('service_type', $pricing->service_type) == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('service_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                <select class="form-select @error('branch_id') is-invalid @enderror" 
                                        id="branch_id" 
                                        name="branch_id" 
                                        required>
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $pricing->branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="base_price" class="form-label">Base Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control @error('base_price') is-invalid @enderror" 
                                           id="base_price" 
                                           name="base_price" 
                                           value="{{ old('base_price', $pricing->base_price) }}" 
                                           step="0.01" 
                                           min="0" 
                                           required>
                                    <span class="input-group-text">{{ $pricing->currency }}</span>
                                </div>
                                @error('base_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select @error('currency') is-invalid @enderror" 
                                        id="currency" 
                                        name="currency">
                                    <option value="GHS" {{ old('currency', $pricing->currency) == 'GHS' ? 'selected' : '' }}>GHS (Ghana Cedi)</option>
                                    <option value="USD" {{ old('currency', $pricing->currency) == 'USD' ? 'selected' : '' }}>USD (US Dollar)</option>
                                    <option value="EUR" {{ old('currency', $pricing->currency) == 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
                                    <option value="GBP" {{ old('currency', $pricing->currency) == 'GBP' ? 'selected' : '' }}>GBP (British Pound)</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @include('pricing._additive_fields', ['pricing' => $pricing])

                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" 
                                          name="description" 
                                          rows="3"
                                          placeholder="Enter service description...">{{ old('description', $pricing->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="is_active" 
                                           name="is_active" 
                                           value="1" 
                                           {{ old('is_active', $pricing->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active Service
                                    </label>
                                </div>
                                <small class="text-muted">Inactive services won't appear in price lists</small>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="requires_approval" 
                                           name="requires_approval" 
                                           value="1" 
                                           {{ old('requires_approval', $pricing->requires_approval) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_approval">
                                        Requires Manager Approval
                                    </label>
                                </div>
                                <small class="text-muted">Services requiring approval need manager authorization</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pricing Tiers Card (Optional) --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark"><i class="bi bi-layers me-2"></i>Pricing Tiers (Optional)</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Info:</strong> Define different pricing tiers for this service (e.g., Standard, Premium, VIP)
                        </div>
                        
                        <div class="row g-3" id="pricing-tiers">
                            @php
                                $tiers = old('pricing_tiers', $pricing->pricing_tiers ?? []);
                                if (empty($tiers)) {
                                    $tiers = ['standard' => ['price' => $pricing->base_price, 'description' => 'Standard pricing']];
                                }
                            @endphp
                            
                            @foreach($tiers as $tierKey => $tierData)
                            <div class="col-md-4 tier-row">
                                <div class="border rounded p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold mb-0">{{ ucfirst($tierKey) }} Tier</label>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-tier" style="display: none;">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="pricing_tiers[{{ $tierKey }}][key]" value="{{ $tierKey }}">
                                    <input type="number" 
                                           class="form-control mb-2" 
                                           name="pricing_tiers[{{ $tierKey }}][price]" 
                                           value="{{ $tierData['price'] ?? $tierData }}" 
                                           step="0.01" 
                                           min="0" 
                                           placeholder="Price">
                                    <input type="text" 
                                           class="form-control" 
                                           name="pricing_tiers[{{ $tierKey }}][description]" 
                                           value="{{ $tierData['description'] ?? '' }}" 
                                           placeholder="Description">
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-tier">
                                <i class="bi bi-plus-circle"></i> Add Tier
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Submit Buttons --}}
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('pricing.show', $pricing->id) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Service Pricing
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add tier functionality
    let tierCounter = {{ count($tiers) }};
    
    document.getElementById('add-tier').addEventListener('click', function() {
        const tierName = prompt('Enter tier name (e.g., premium, vip):');
        if (tierName && tierName.trim()) {
            const tierKey = tierName.toLowerCase().replace(/\s+/g, '_');
            const tierHtml = `
                <div class="col-md-4 tier-row">
                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0">${tierName.charAt(0).toUpperCase() + tierName.slice(1)} Tier</label>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-tier">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        <input type="hidden" name="pricing_tiers[${tierKey}][key]" value="${tierKey}">
                        <input type="number" class="form-control mb-2" 
                               name="pricing_tiers[${tierKey}][price]" 
                               step="0.01" min="0" placeholder="Price" required>
                        <input type="text" class="form-control" 
                               name="pricing_tiers[${tierKey}][description]" 
                               placeholder="Description">
                    </div>
                </div>
            `;
            document.getElementById('pricing-tiers').insertAdjacentHTML('beforeend', tierHtml);
            tierCounter++;
            updateTierButtons();
        }
    });
    
    // Remove tier functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-tier')) {
            e.target.closest('.tier-row').remove();
            updateTierButtons();
        }
    });
    
    // Show/hide remove buttons based on tier count
    function updateTierButtons() {
        const tierRows = document.querySelectorAll('.tier-row');
        const removeButtons = document.querySelectorAll('.remove-tier');
        
        removeButtons.forEach(button => {
            button.style.display = tierRows.length > 1 ? 'block' : 'none';
        });
    }
    
    updateTierButtons();
});
</script>
@endpush
@endsection
