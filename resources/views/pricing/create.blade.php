@extends('layouts.app')

@section('title', 'Add New Service Pricing')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="mb-4">
        <h1 class="h3 mb-1" style="color: #1e3a5f;">
            <i class="bi bi-plus-circle me-2"></i>Add New Service Pricing
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pricing.index') }}">Pricing</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Service Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('pricing.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="service_id" class="form-label">Service ID <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('service_id') is-invalid @enderror" 
                                       id="service_id" 
                                       name="service_id" 
                                       value="{{ old('service_id') }}"
                                       placeholder="e.g., consultation, lab_test_12, radiology_3"
                                       required>
                                <small class="text-muted">Module fees: <code>module_fee_lab</code>, <code>module_fee_radiology</code>. Consultation: <code>consultation</code> or module fee. Item overrides: <code>lab_test_{id}</code>, <code>drug_{id}</code>. Unique per branch.</small>
                                @error('service_id')
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
                                        <option value="{{ $key }}" {{ old('service_type') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('service_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="service_name" class="form-label">Service Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('service_name') is-invalid @enderror" 
                                       id="service_name" 
                                       name="service_name" 
                                       value="{{ old('service_name') }}"
                                       placeholder="Enter service name"
                                       required>
                                @error('service_name')
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
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
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
                                    <select class="form-select" 
                                            style="max-width: 100px;" 
                                            name="currency">
                                        <option value="GHS">GHS</option>
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                    </select>
                                    <input type="number" 
                                           class="form-control @error('base_price') is-invalid @enderror" 
                                           id="base_price" 
                                           name="base_price" 
                                           value="{{ old('base_price') }}"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00"
                                           required>
                                </div>
                                @error('base_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @include('pricing._additive_fields', ['pricing' => new \App\Models\ServicePricing()])

                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" 
                                          name="description" 
                                          rows="3"
                                          placeholder="Enter service description...">{{ old('description') }}</textarea>
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
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active Service
                                    </label>
                                    <br><small class="text-muted">Service is available for billing</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="requires_approval" 
                                           name="requires_approval" 
                                           value="1"
                                           {{ old('requires_approval') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_approval">
                                        Requires Approval
                                    </label>
                                    <br><small class="text-muted">Service billing needs authorization</small>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Service Pricing
                            </button>
                            <a href="{{ route('pricing.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

