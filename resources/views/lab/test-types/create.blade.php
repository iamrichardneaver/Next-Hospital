@extends('layouts.app')

@section('title', 'Create Test Type')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Create Test Type</h1>
            <p class="text-secondary mb-0">Add a new laboratory test type</p>
        </div>
        <div>
            <a href="{{ route('lab.test-types') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Test Types
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Test Type Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('lab.test-types.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="test_code" class="form-label">Test Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('test_code') is-invalid @enderror" 
                                       id="test_code" name="test_code" value="{{ old('test_code') }}" required>
                                @error('test_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="test_name" class="form-label">Test Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('test_name') is-invalid @enderror" 
                                       id="test_name" name="test_name" value="{{ old('test_name') }}" required>
                                @error('test_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select @error('category_id') is-invalid @enderror" 
                                        id="category_id" name="category_id" required>
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="subcategory" class="form-label">Subcategory</label>
                                <input type="text" class="form-control @error('subcategory') is-invalid @enderror" 
                                       id="subcategory" name="subcategory" value="{{ old('subcategory') }}">
                                @error('subcategory')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="template_id" class="form-label">Result template</label>
                            <select class="form-select @error('template_id') is-invalid @enderror" id="template_id" name="template_id">
                                <option value="">— Optional: select later in Edit —</option>
                                @foreach($templates as $t)
                                    <option value="{{ $t->id }}" {{ old('template_id') == $t->id ? 'selected' : '' }}>
                                        {{ $t->template_name }} ({{ $t->template_code }}) — {{ $t->parameters->count() }} parameters
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Required for entering lab results. Create under <a href="{{ route('lab.templates') }}">Templates</a>, add parameters, then assign here or in Edit.</small>
                            @error('template_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="specimen_type" class="form-label">Specimen Type <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('specimen_type') is-invalid @enderror" 
                                       id="specimen_type" name="specimen_type" value="{{ old('specimen_type') }}" required>
                                @error('specimen_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="collection_method" class="form-label">Collection Method</label>
                                <input type="text" class="form-control @error('collection_method') is-invalid @enderror" 
                                       id="collection_method" name="collection_method" value="{{ old('collection_method') }}">
                                @error('collection_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="routine_tat_hours" class="form-label">Routine TAT (Hours)</label>
                                <input type="number" class="form-control @error('routine_tat_hours') is-invalid @enderror" 
                                       id="routine_tat_hours" name="routine_tat_hours" value="{{ old('routine_tat_hours', 24) }}" min="1">
                                @error('routine_tat_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="urgent_tat_hours" class="form-label">Urgent TAT (Hours)</label>
                                <input type="number" class="form-control @error('urgent_tat_hours') is-invalid @enderror" 
                                       id="urgent_tat_hours" name="urgent_tat_hours" value="{{ old('urgent_tat_hours', 4) }}" min="1">
                                @error('urgent_tat_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="stat_tat_hours" class="form-label">STAT TAT (Hours)</label>
                                <input type="number" class="form-control @error('stat_tat_hours') is-invalid @enderror" 
                                       id="stat_tat_hours" name="stat_tat_hours" value="{{ old('stat_tat_hours', 1) }}" min="1">
                                @error('stat_tat_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cost" class="form-label">Cost (GHS)</label>
                                <input type="number" class="form-control @error('cost') is-invalid @enderror" 
                                       id="cost" name="cost" value="{{ old('cost') }}" step="0.01" min="0">
                                @error('cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="nhis_cost" class="form-label">NHIS Cost (GHS)</label>
                                <input type="number" class="form-control @error('nhis_cost') is-invalid @enderror" 
                                       id="nhis_cost" name="nhis_cost" value="{{ old('nhis_cost') }}" step="0.01" min="0">
                                @error('nhis_cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="methodology" class="form-label">Methodology</label>
                                <input type="text" class="form-control @error('methodology') is-invalid @enderror" 
                                       id="methodology" name="methodology" value="{{ old('methodology') }}">
                                @error('methodology')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="equipment_required" class="form-label">Equipment Required</label>
                                <input type="text" class="form-control @error('equipment_required') is-invalid @enderror" 
                                       id="equipment_required" name="equipment_required" value="{{ old('equipment_required') }}">
                                @error('equipment_required')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ghs_code" class="form-label">GHS Code</label>
                            <input type="text" class="form-control @error('ghs_code') is-invalid @enderror" 
                                   id="ghs_code" name="ghs_code" value="{{ old('ghs_code') }}">
                            @error('ghs_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="nhis_covered" name="nhis_covered" 
                                           {{ old('nhis_covered') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="nhis_covered">
                                        NHIS Covered
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="requires_doctor_approval" name="requires_doctor_approval" 
                                           {{ old('requires_doctor_approval') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_doctor_approval">
                                        Requires Doctor Approval
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="requires_consultant_review" name="requires_consultant_review" 
                                           {{ old('requires_consultant_review') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_consultant_review">
                                        Requires Consultant Review
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="requires_qc" name="requires_qc" 
                                           {{ old('requires_qc') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_qc">
                                        Requires Quality Control
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="requires_verification" name="requires_verification" 
                                           {{ old('requires_verification') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_verification">
                                        Requires Verification
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="ghs_mandatory" name="ghs_mandatory" 
                                           {{ old('ghs_mandatory') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ghs_mandatory">
                                        GHS Mandatory Reporting
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('lab.test-types') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Test Type
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Help & Guidelines</h5>
                </div>
                <div class="card-body">
                    <h6>Test Code Guidelines:</h6>
                    <ul class="small">
                        <li>Use uppercase letters and numbers</li>
                        <li>Keep it short and memorable (e.g., CBC, FBS, MP)</li>
                        <li>Avoid special characters</li>
                    </ul>
                    
                    <h6>Turnaround Times:</h6>
                    <ul class="small">
                        <li><strong>Routine:</strong> Standard processing time</li>
                        <li><strong>Urgent:</strong> Priority processing</li>
                        <li><strong>STAT:</strong> Emergency/immediate processing</li>
                    </ul>
                    
                    <h6>Specimen Types:</h6>
                    <ul class="small">
                        <li>Blood (Serum, Plasma, Whole Blood)</li>
                        <li>Urine (Random, 24-hour, Clean Catch)</li>
                        <li>Stool, Sputum, Swab, etc.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
