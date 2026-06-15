@extends('layouts.app')

@section('title', 'Edit Ward - ' . $ward->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Edit Ward</h1>
            <p class="text-secondary mb-0">Update ward information</p>
        </div>
        <div>
            <a href="{{ route('wards.show', $ward) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Ward
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Ward Information</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('wards.update', $ward) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Ward Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $ward->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Ward Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" 
                                        id="type" name="type" required>
                                    <option value="">Select Ward Type</option>
                                    <option value="general" {{ old('type', $ward->type) == 'general' ? 'selected' : '' }}>General</option>
                                    <option value="male" {{ old('type', $ward->type) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('type', $ward->type) == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="pediatric" {{ old('type', $ward->type) == 'pediatric' ? 'selected' : '' }}>Pediatric</option>
                                    <option value="maternity" {{ old('type', $ward->type) == 'maternity' ? 'selected' : '' }}>Maternity</option>
                                    <option value="icu" {{ old('type', $ward->type) == 'icu' ? 'selected' : '' }}>ICU</option>
                                    <option value="isolation" {{ old('type', $ward->type) == 'isolation' ? 'selected' : '' }}>Isolation</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Optional description of the ward">{{ old('description', $ward->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('wards.show', $ward) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-check-circle"></i> Update Ward
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Current Ward Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Ward Code:</strong>
                        <span class="badge bg-secondary ms-2">{{ $ward->code }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Total Beds:</strong>
                        <span class="badge bg-primary ms-2">{{ $ward->total_beds }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Available Beds:</strong>
                        <span class="badge bg-success ms-2">{{ $ward->available_beds }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Status:</strong>
                        <span class="badge bg-{{ $ward->is_active ? 'success' : 'danger' }} ms-2">
                            {{ $ward->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="mb-0">
                        <strong>Created:</strong>
                        <div class="text-muted">{{ $ward->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Danger Zone</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Deleting a ward will remove all associated beds and assignments. This action cannot be undone.
                    </p>
                    <form action="{{ route('wards.destroy', $ward) }}" method="POST" 
                          onsubmit="return confirm('Are you sure you want to delete this ward? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash"></i> Delete Ward
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection