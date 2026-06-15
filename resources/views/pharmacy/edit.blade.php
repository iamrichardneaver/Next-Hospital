@extends('layouts.app')

@section('title', 'Edit Drug')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Edit Drug</h1>
            <p class="text-secondary mb-0">Update drug information</p>
        </div>
        <a href="{{ route('pharmacy.show', $pharmacy) }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Cancel</a>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Drug Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('pharmacy.update', $pharmacy) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Drug Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $pharmacy->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="generic_name" class="form-label">Generic Name</label>
                                <input type="text" class="form-control @error('generic_name') is-invalid @enderror" id="generic_name" name="generic_name" value="{{ old('generic_name', $pharmacy->generic_name) }}">
                                @error('generic_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="drug_code" class="form-label">Drug Code</label>
                                <input type="text" class="form-control @error('drug_code') is-invalid @enderror" id="drug_code" name="drug_code" value="{{ old('drug_code', $pharmacy->drug_code) }}">
                                @error('drug_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Analgesic" {{ old('category', $pharmacy->category) == 'Analgesic' ? 'selected' : '' }}>Analgesic</option>
                                    <option value="Antibiotic" {{ old('category', $pharmacy->category) == 'Antibiotic' ? 'selected' : '' }}>Antibiotic</option>
                                    <option value="Antimalarial" {{ old('category', $pharmacy->category) == 'Antimalarial' ? 'selected' : '' }}>Antimalarial</option>
                                    <option value="Antiviral" {{ old('category', $pharmacy->category) == 'Antiviral' ? 'selected' : '' }}>Antiviral</option>
                                    <option value="Cardiovascular" {{ old('category', $pharmacy->category) == 'Cardiovascular' ? 'selected' : '' }}>Cardiovascular</option>
                                    <option value="Diabetes" {{ old('category', $pharmacy->category) == 'Diabetes' ? 'selected' : '' }}>Diabetes</option>
                                    <option value="Vitamins" {{ old('category', $pharmacy->category) == 'Vitamins' ? 'selected' : '' }}>Vitamins & Supplements</option>
                                    <option value="Gastrointestinal" {{ old('category', $pharmacy->category) == 'Gastrointestinal' ? 'selected' : '' }}>Gastrointestinal</option>
                                    <option value="Respiratory" {{ old('category', $pharmacy->category) == 'Respiratory' ? 'selected' : '' }}>Respiratory</option>
                                    <option value="Other" {{ old('category', $pharmacy->category) == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="dosage_form" class="form-label">Dosage Form <span class="text-danger">*</span></label>
                                <select class="form-select @error('dosage_form') is-invalid @enderror" id="dosage_form" name="dosage_form" required>
                                    <option value="">Select Form</option>
                                    <option value="Tablet" {{ old('dosage_form', $pharmacy->dosage_form) == 'Tablet' ? 'selected' : '' }}>Tablet</option>
                                    <option value="Capsule" {{ old('dosage_form', $pharmacy->dosage_form) == 'Capsule' ? 'selected' : '' }}>Capsule</option>
                                    <option value="Syrup" {{ old('dosage_form', $pharmacy->dosage_form) == 'Syrup' ? 'selected' : '' }}>Syrup/Suspension</option>
                                    <option value="Injection" {{ old('dosage_form', $pharmacy->dosage_form) == 'Injection' ? 'selected' : '' }}>Injection</option>
                                    <option value="Cream" {{ old('dosage_form', $pharmacy->dosage_form) == 'Cream' ? 'selected' : '' }}>Cream</option>
                                    <option value="Ointment" {{ old('dosage_form', $pharmacy->dosage_form) == 'Ointment' ? 'selected' : '' }}>Ointment</option>
                                    <option value="Drops" {{ old('dosage_form', $pharmacy->dosage_form) == 'Drops' ? 'selected' : '' }}>Drops</option>
                                    <option value="Inhaler" {{ old('dosage_form', $pharmacy->dosage_form) == 'Inhaler' ? 'selected' : '' }}>Inhaler</option>
                                </select>
                                @error('dosage_form')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="strength" class="form-label">Strength</label>
                                <input type="text" class="form-control @error('strength') is-invalid @enderror" id="strength" name="strength" value="{{ old('strength', $pharmacy->strength) }}">
                                @error('strength')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="unit" class="form-label">Unit</label>
                                <input type="text" class="form-control @error('unit') is-invalid @enderror" id="unit" name="unit" value="{{ old('unit', $pharmacy->unit) }}">
                                @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="manufacturer" class="form-label">Manufacturer</label>
                                <input type="text" class="form-control @error('manufacturer') is-invalid @enderror" id="manufacturer" name="manufacturer" value="{{ old('manufacturer', $pharmacy->manufacturer) }}">
                                @error('manufacturer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description', $pharmacy->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="selling_price" class="form-label">Selling Price (₵) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" name="selling_price" value="{{ old('selling_price', $pharmacy->selling_price) }}" min="0" required>
                                @error('selling_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="cost_price" class="form-label">Cost Price (₵)</label>
                                <input type="number" step="0.01" class="form-control @error('cost_price') is-invalid @enderror" id="cost_price" name="cost_price" value="{{ old('cost_price', $pharmacy->cost_price) }}" min="0">
                                @error('cost_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nhis_price" class="form-label">NHIS Price (₵)</label>
                                <input type="number" step="0.01" class="form-control @error('nhis_price') is-invalid @enderror" id="nhis_price" name="nhis_price" value="{{ old('nhis_price', $pharmacy->nhis_price) }}" min="0">
                                @error('nhis_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="requires_prescription" name="requires_prescription" {{ old('requires_prescription', $pharmacy->requires_prescription) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_prescription">
                                        Requires Prescription
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="nhis_covered" name="nhis_covered" {{ old('nhis_covered', $pharmacy->nhis_covered) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="nhis_covered">
                                        NHIS Covered
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Stock Management Section -->
                        <h5 class="mb-3 text-dark"><i class="bi bi-box-seam"></i> Stock Management (Current Branch)</h5>
                        <p class="text-muted small mb-3">Manage stock quantity and settings for your branch</p>
                        
                        @if($currentStock)
                        <div class="alert alert-info mb-3">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <i class="bi bi-info-circle"></i> <strong>Current Stock:</strong> <span class="fs-5 text-primary">{{ $currentStock->current_stock }} units</span>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <small class="text-muted">
                                        @if($currentStock->current_stock <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif($currentStock->current_stock <= $currentStock->reorder_level)
                                            <span class="badge bg-warning">Low Stock</span>
                                        @else
                                            <span class="badge bg-success">In Stock</span>
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Add Stock Section -->
                        <div class="card mb-3 border-success">
                            <div class="card-header bg-success text-white">
                                <i class="bi bi-plus-circle"></i> Add More Stock
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="add_quantity" class="form-label">Quantity to Add</label>
                                        <input type="number" class="form-control form-control-lg @error('add_quantity') is-invalid @enderror" id="add_quantity" name="add_quantity" value="{{ old('add_quantity', 0) }}" min="0">
                                        @error('add_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <small class="text-muted">Enter number of units to add to current stock</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">New Total After Adding</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">{{ $currentStock->current_stock }}</span>
                                            <span class="input-group-text">+</span>
                                            <input type="text" class="form-control" id="new_total_display" readonly value="0">
                                            <span class="input-group-text bg-success text-white" id="equals_total">= {{ $currentStock->current_stock }}</span>
                                        </div>
                                        <small class="text-muted">Calculated automatically</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle"></i> <strong>No stock record found.</strong> Enter initial stock quantity below to create stock record for your branch.
                        </div>

                        <!-- Create Initial Stock -->
                        <div class="card mb-3 border-primary">
                            <div class="card-header bg-primary text-white">
                                <i class="bi bi-plus-circle"></i> Create Initial Stock
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="add_quantity" class="form-label">Initial Stock Quantity <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-lg @error('add_quantity') is-invalid @enderror" id="add_quantity" name="add_quantity" value="{{ old('add_quantity', 0) }}" min="0" required>
                                        @error('add_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <small class="text-muted">Enter starting stock quantity for this drug</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Stock Settings -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="reorder_level" class="form-label">Reorder Level <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('reorder_level') is-invalid @enderror" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', $currentStock->reorder_level ?? 10) }}" min="0" required>
                                @error('reorder_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Alert when stock reaches this level</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="minimum_stock" class="form-label">Minimum Stock</label>
                                <input type="number" class="form-control @error('minimum_stock') is-invalid @enderror" id="minimum_stock" name="minimum_stock" value="{{ old('minimum_stock', $currentStock->minimum_stock ?? 5) }}" min="0">
                                @error('minimum_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Absolute minimum quantity</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="batch_number" class="form-label">Batch Number</label>
                                <input type="text" class="form-control @error('batch_number') is-invalid @enderror" id="batch_number" name="batch_number" value="{{ old('batch_number', $currentStock->batch_number ?? '') }}">
                                @error('batch_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="date" class="form-control @error('expiry_date') is-invalid @enderror" id="expiry_date" name="expiry_date" value="{{ old('expiry_date', $currentStock && $currentStock->expiry_date ? $currentStock->expiry_date->format('Y-m-d') : '') }}" min="{{ date('Y-m-d') }}">
                                @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="supplier" class="form-label">Supplier</label>
                                <input type="text" class="form-control @error('supplier') is-invalid @enderror" id="supplier" name="supplier" value="{{ old('supplier', $currentStock->supplier ?? '') }}">
                                @error('supplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <input type="hidden" name="manage_stock" value="1">
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('pharmacy.show', $pharmacy) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Drug
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addQuantityInput = document.getElementById('add_quantity');
    const newTotalDisplay = document.getElementById('new_total_display');
    const equalsTotal = document.getElementById('equals_total');
    
    @if($currentStock)
    const currentStock = {{ $currentStock->current_stock }};
    
    if (addQuantityInput && newTotalDisplay && equalsTotal) {
        addQuantityInput.addEventListener('input', function() {
            const addQty = parseInt(this.value) || 0;
            const newTotal = currentStock + addQty;
            
            newTotalDisplay.value = addQty;
            equalsTotal.textContent = '= ' + newTotal;
            
            // Change color based on stock level
            if (newTotal > {{ $currentStock->reorder_level }}) {
                equalsTotal.className = 'input-group-text bg-success text-white';
            } else if (newTotal > 0) {
                equalsTotal.className = 'input-group-text bg-warning text-dark';
            } else {
                equalsTotal.className = 'input-group-text bg-danger text-white';
            }
        });
    }
    @endif
});
</script>
@endpush

@endsection
