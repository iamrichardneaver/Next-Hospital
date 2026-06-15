@extends('layouts.app')

@section('title', 'Add New Drug')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Add New Drug</h1>
            <p class="text-secondary mb-0">Add a new drug to inventory</p>
        </div>
        <a href="{{ route('pharmacy.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Cancel</a>
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
                    <form action="{{ route('pharmacy.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Drug Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Brand or commercial name</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="generic_name" class="form-label">Generic Name</label>
                                <input type="text" class="form-control @error('generic_name') is-invalid @enderror" id="generic_name" name="generic_name" value="{{ old('generic_name') }}">
                                @error('generic_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Scientific/generic name</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="drug_code" class="form-label">Drug Code</label>
                                <input type="text" class="form-control @error('drug_code') is-invalid @enderror" id="drug_code" name="drug_code" value="{{ old('drug_code') }}">
                                @error('drug_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Analgesic">Analgesic</option>
                                    <option value="Antibiotic">Antibiotic</option>
                                    <option value="Antimalarial">Antimalarial</option>
                                    <option value="Antiviral">Antiviral</option>
                                    <option value="Cardiovascular">Cardiovascular</option>
                                    <option value="Diabetes">Diabetes</option>
                                    <option value="Vitamins">Vitamins & Supplements</option>
                                    <option value="Gastrointestinal">Gastrointestinal</option>
                                    <option value="Respiratory">Respiratory</option>
                                    <option value="Other">Other</option>
                                </select>
                                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="dosage_form" class="form-label">Dosage Form <span class="text-danger">*</span></label>
                                <select class="form-select @error('dosage_form') is-invalid @enderror" id="dosage_form" name="dosage_form" required>
                                    <option value="">Select Form</option>
                                    <option value="Tablet">Tablet</option>
                                    <option value="Capsule">Capsule</option>
                                    <option value="Syrup">Syrup/Suspension</option>
                                    <option value="Injection">Injection</option>
                                    <option value="Cream">Cream</option>
                                    <option value="Ointment">Ointment</option>
                                    <option value="Drops">Drops</option>
                                    <option value="Inhaler">Inhaler</option>
                                </select>
                                @error('dosage_form')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="strength" class="form-label">Strength</label>
                                <input type="text" class="form-control @error('strength') is-invalid @enderror" id="strength" name="strength" value="{{ old('strength') }}" placeholder="e.g., 500mg, 5%">
                                @error('strength')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="unit" class="form-label">Unit</label>
                                <input type="text" class="form-control @error('unit') is-invalid @enderror" id="unit" name="unit" value="{{ old('unit') }}" placeholder="e.g., tablet, ml, g">
                                @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="manufacturer" class="form-label">Manufacturer</label>
                                <input type="text" class="form-control @error('manufacturer') is-invalid @enderror" id="manufacturer" name="manufacturer" value="{{ old('manufacturer') }}">
                                @error('manufacturer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="selling_price" class="form-label">Selling Price (₵) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" name="selling_price" value="{{ old('selling_price') }}" min="0" required>
                                @error('selling_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="cost_price" class="form-label">Cost Price (₵)</label>
                                <input type="number" step="0.01" class="form-control @error('cost_price') is-invalid @enderror" id="cost_price" name="cost_price" value="{{ old('cost_price') }}" min="0">
                                @error('cost_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nhis_price" class="form-label">NHIS Price (₵)</label>
                                <input type="number" step="0.01" class="form-control @error('nhis_price') is-invalid @enderror" id="nhis_price" name="nhis_price" value="{{ old('nhis_price') }}" min="0">
                                @error('nhis_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="requires_prescription" name="requires_prescription" {{ old('requires_prescription') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_prescription">
                                        Requires Prescription
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="nhis_covered" name="nhis_covered" {{ old('nhis_covered') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="nhis_covered">
                                        NHIS Covered
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Initial Stock Information -->
                        <h5 class="mb-3 text-dark"><i class="bi bi-box-seam"></i> Initial Stock Information</h5>
                        <p class="text-muted small mb-3">Add initial stock quantity for this drug (you can add more stock later)</p>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="current_stock" class="form-label">Initial Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('current_stock') is-invalid @enderror" id="current_stock" name="current_stock" value="{{ old('current_stock', 0) }}" min="0" required>
                                @error('current_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Starting stock quantity</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="reorder_level" class="form-label">Reorder Level <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('reorder_level') is-invalid @enderror" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', 10) }}" min="0" required>
                                @error('reorder_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Alert when stock reaches this level</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="minimum_stock" class="form-label">Minimum Stock</label>
                                <input type="number" class="form-control @error('minimum_stock') is-invalid @enderror" id="minimum_stock" name="minimum_stock" value="{{ old('minimum_stock', 5) }}" min="0">
                                @error('minimum_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Absolute minimum quantity</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="batch_number" class="form-label">Batch Number</label>
                                <input type="text" class="form-control @error('batch_number') is-invalid @enderror" id="batch_number" name="batch_number" value="{{ old('batch_number') }}">
                                @error('batch_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="date" class="form-control @error('expiry_date') is-invalid @enderror" id="expiry_date" name="expiry_date" value="{{ old('expiry_date') }}" min="{{ date('Y-m-d') }}">
                                @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="supplier" class="form-label">Supplier</label>
                                <input type="text" class="form-control @error('supplier') is-invalid @enderror" id="supplier" name="supplier" value="{{ old('supplier') }}">
                                @error('supplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('pharmacy.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Add Drug
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
