@extends('layouts.app')

@section('title', 'Create New Ward')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Create New Ward</h1>
            <p class="text-secondary mb-0">Add a new ward to the hospital</p>
        </div>
        <div>
            <a href="{{ route('wards.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Wards
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-building-add"></i> Ward Information</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('wards.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Ward Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Ward Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" 
                                        id="type" name="type" required>
                                    <option value="">Select Ward Type</option>
                                    <option value="general" {{ old('type') == 'general' ? 'selected' : '' }}>General</option>
                                    <option value="male" {{ old('type') == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('type') == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="pediatric" {{ old('type') == 'pediatric' ? 'selected' : '' }}>Pediatric</option>
                                    <option value="maternity" {{ old('type') == 'maternity' ? 'selected' : '' }}>Maternity</option>
                                    <option value="icu" {{ old('type') == 'icu' ? 'selected' : '' }}>ICU</option>
                                    <option value="isolation" {{ old('type') == 'isolation' ? 'selected' : '' }}>Isolation</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="total_beds" class="form-label">Total Beds <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('total_beds') is-invalid @enderror" 
                                       id="total_beds" name="total_beds" value="{{ old('total_beds') }}" 
                                       min="1" max="100" required>
                                @error('total_beds')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Number of beds to create for this ward</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Optional description of the ward">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('wards.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Ward
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Ward Types</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>General:</strong> Mixed gender ward for general patients
                    </div>
                    <div class="mb-2">
                        <strong>Male/Female:</strong> Gender-specific wards
                    </div>
                    <div class="mb-2">
                        <strong>Pediatric:</strong> Specialized ward for children
                    </div>
                    <div class="mb-2">
                        <strong>Maternity:</strong> Ward for pregnant women and new mothers
                    </div>
                    <div class="mb-2">
                        <strong>ICU:</strong> Intensive Care Unit for critical patients
                    </div>
                    <div class="mb-0">
                        <strong>Isolation:</strong> For patients with infectious diseases
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection