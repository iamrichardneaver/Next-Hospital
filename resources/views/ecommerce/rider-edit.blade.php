@extends('layouts.app')

@section('title', 'Edit Delivery Rider')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit me-2"></i>Edit Delivery Rider
        </h1>
        <a href="{{ route('ecommerce.riders') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Riders
        </a>
    </div>

    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Rider Information</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('ecommerce.riders.update', $rider->id) }}">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>User</label>
                            <input type="text" class="form-control" value="{{ $rider->user->name }}" disabled>
                            <small class="form-text text-muted">User account cannot be changed</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Branch <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required>
                                <option value="">Choose a branch...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $rider->branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                   value="{{ old('phone', $rider->phone) }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Emergency Contact</label>
                            <input type="text" name="emergency_contact" class="form-control @error('emergency_contact') is-invalid @enderror" 
                                   value="{{ old('emergency_contact', $rider->emergency_contact) }}">
                            @error('emergency_contact')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Vehicle Type</label>
                            <select name="vehicle_type" class="form-control @error('vehicle_type') is-invalid @enderror">
                                <option value="">Select type...</option>
                                <option value="bike" {{ old('vehicle_type', $rider->vehicle_type) == 'bike' ? 'selected' : '' }}>Bike</option>
                                <option value="motorcycle" {{ old('vehicle_type', $rider->vehicle_type) == 'motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                                <option value="car" {{ old('vehicle_type', $rider->vehicle_type) == 'car' ? 'selected' : '' }}>Car</option>
                                <option value="van" {{ old('vehicle_type', $rider->vehicle_type) == 'van' ? 'selected' : '' }}>Van</option>
                            </select>
                            @error('vehicle_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Vehicle Number</label>
                            <input type="text" name="vehicle_number" class="form-control @error('vehicle_number') is-invalid @enderror" 
                                   value="{{ old('vehicle_number', $rider->vehicle_number) }}">
                            @error('vehicle_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>License Number</label>
                            <input type="text" name="license_number" class="form-control @error('license_number') is-invalid @enderror" 
                                   value="{{ old('license_number', $rider->license_number) }}">
                            @error('license_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', $rider->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $rider->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="off_duty" {{ old('status', $rider->status) == 'off_duty' ? 'selected' : '' }}>Off Duty</option>
                                <option value="on_delivery" {{ old('status', $rider->status) == 'on_delivery' ? 'selected' : '' }}>On Delivery</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" 
                                      rows="3">{{ old('notes', $rider->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>

                <div class="form-group mb-0">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Rider
                    </button>
                    <a href="{{ route('ecommerce.riders') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
