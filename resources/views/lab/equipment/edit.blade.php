@extends('layouts.app')

@section('title', 'Edit Equipment - ' . $equipment->name)

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('lab.equipment.index') }}">Equipment</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('lab.equipment.show', $equipment) }}">{{ $equipment->name }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-1" style="color: #1e3a5f;">
                    <i class="bi bi-pencil"></i> Edit Equipment
                </h1>
                <p class="text-secondary mb-0">{{ $equipment->name }} - {{ $equipment->model }}</p>
            </div>
            <div>
                <a href="{{ route('lab.equipment.show', $equipment) }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Details
                </a>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Validation Errors</h5>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> Equipment Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('lab.equipment.update', $equipment) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Equipment Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $equipment->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" class="form-control @error('model') is-invalid @enderror" 
                                       id="model" name="model" value="{{ old('model', $equipment->model) }}">
                                @error('model')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="manufacturer" class="form-label">Manufacturer</label>
                                <input type="text" class="form-control @error('manufacturer') is-invalid @enderror" 
                                       id="manufacturer" name="manufacturer" value="{{ old('manufacturer', $equipment->manufacturer) }}">
                                @error('manufacturer')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="serial_number" class="form-label">Serial Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('serial_number') is-invalid @enderror" 
                                       id="serial_number" name="serial_number" value="{{ old('serial_number', $equipment->serial_number) }}" required>
                                @error('serial_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="equipment_type" class="form-label">Equipment Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('equipment_type') is-invalid @enderror" 
                                        id="equipment_type" name="equipment_type" required>
                                    <option value="">-- Select Equipment Type --</option>
                                    <option value="microscope" {{ old('equipment_type', $equipment->equipment_type) == 'microscope' ? 'selected' : '' }}>Microscope</option>
                                    <option value="centrifuge" {{ old('equipment_type', $equipment->equipment_type) == 'centrifuge' ? 'selected' : '' }}>Centrifuge</option>
                                    <option value="analyzer" {{ old('equipment_type', $equipment->equipment_type) == 'analyzer' ? 'selected' : '' }}>Analyzer</option>
                                    <option value="incubator" {{ old('equipment_type', $equipment->equipment_type) == 'incubator' ? 'selected' : '' }}>Incubator</option>
                                    <option value="autoclave" {{ old('equipment_type', $equipment->equipment_type) == 'autoclave' ? 'selected' : '' }}>Autoclave</option>
                                    <option value="refrigerator" {{ old('equipment_type', $equipment->equipment_type) == 'refrigerator' ? 'selected' : '' }}>Refrigerator</option>
                                    <option value="freezer" {{ old('equipment_type', $equipment->equipment_type) == 'freezer' ? 'selected' : '' }}>Freezer</option>
                                    <option value="balance" {{ old('equipment_type', $equipment->equipment_type) == 'balance' ? 'selected' : '' }}>Balance</option>
                                    <option value="ph_meter" {{ old('equipment_type', $equipment->equipment_type) == 'ph_meter' ? 'selected' : '' }}>pH Meter</option>
                                    <option value="spectrophotometer" {{ old('equipment_type', $equipment->equipment_type) == 'spectrophotometer' ? 'selected' : '' }}>Spectrophotometer</option>
                                    <option value="other" {{ old('equipment_type', $equipment->equipment_type) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('equipment_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                       id="location" name="location" value="{{ old('location', $equipment->location) }}" 
                                       placeholder="e.g., Lab Room 1, Storage Room A">
                                @error('location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="installation_date" class="form-label">Installation Date</label>
                                <input type="date" class="form-control @error('installation_date') is-invalid @enderror" 
                                       id="installation_date" name="installation_date" value="{{ old('installation_date', $equipment->installation_date ? $equipment->installation_date->format('Y-m-d') : '') }}">
                                @error('installation_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" name="status" required>
                                    <option value="">-- Select Status --</option>
                                    <option value="operational" {{ old('status', $equipment->status) == 'operational' ? 'selected' : '' }}>Operational</option>
                                    <option value="under_maintenance" {{ old('status', $equipment->status) == 'under_maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                                    <option value="out_of_service" {{ old('status', $equipment->status) == 'out_of_service' ? 'selected' : '' }}>Out of Service</option>
                                    <option value="retired" {{ old('status', $equipment->status) == 'retired' ? 'selected' : '' }}>Retired</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="supplier_id" class="form-label">Supplier</label>
                                <select class="form-select @error('supplier_id') is-invalid @enderror" 
                                        id="supplier_id" name="supplier_id">
                                    <option value="">-- Select Supplier --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id', $equipment->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="purchase_date" class="form-label">Purchase Date</label>
                                <input type="date" class="form-control @error('purchase_date') is-invalid @enderror" 
                                       id="purchase_date" name="purchase_date" value="{{ old('purchase_date', $equipment->purchase_date ? $equipment->purchase_date->format('Y-m-d') : '') }}">
                                @error('purchase_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="purchase_cost" class="form-label">Purchase Cost (GHS)</label>
                                <input type="number" step="0.01" class="form-control @error('purchase_cost') is-invalid @enderror" 
                                       id="purchase_cost" name="purchase_cost" value="{{ old('purchase_cost', $equipment->purchase_cost) }}" min="0">
                                @error('purchase_cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                                <input type="date" class="form-control @error('warranty_expiry') is-invalid @enderror" 
                                       id="warranty_expiry" name="warranty_expiry" value="{{ old('warranty_expiry', $equipment->warranty_expiry ? $equipment->warranty_expiry->format('Y-m-d') : '') }}">
                                @error('warranty_expiry')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('lab.equipment.show', $equipment) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Equipment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Equipment Details -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Current Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Current Status:</strong>
                        <span class="badge bg-{{ $equipment->status == 'operational' ? 'success' : ($equipment->status == 'under_maintenance' ? 'warning' : 'danger') }} ms-2">
                            {{ ucfirst(str_replace('_', ' ', $equipment->status)) }}
                        </span>
                    </div>
                    
                    @if($equipment->department)
                    <div class="mb-3">
                        <strong>Department:</strong>
                        <span class="text-muted">{{ $equipment->department }}</span>
                    </div>
                    @endif
                    
                    @if($equipment->last_maintenance_date)
                    <div class="mb-3">
                        <strong>Last Maintenance:</strong>
                        <span class="text-muted">{{ $equipment->last_maintenance_date->format('M d, Y') }}</span>
                    </div>
                    @endif
                    
                    @if($equipment->next_maintenance_date)
                    <div class="mb-3">
                        <strong>Next Maintenance:</strong>
                        <span class="text-muted">{{ $equipment->next_maintenance_date->format('M d, Y') }}</span>
                    </div>
                    @endif
                    
                    @if($equipment->specifications)
                    <div class="mb-3">
                        <strong>Specifications:</strong>
                        <div class="mt-2">
                            @if(is_array($equipment->specifications))
                                @foreach($equipment->specifications as $key => $value)
                                    <div class="mb-1">
                                        <small class="text-muted">
                                            <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                            @if(is_array($value))
                                                {{ implode(', ', $value) }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </small>
                                    </div>
                                @endforeach
                            @else
                                <small class="text-muted">{{ $equipment->specifications }}</small>
                            @endif
                        </div>
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <strong>Created:</strong>
                        <span class="text-muted">{{ $equipment->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Last Updated:</strong>
                        <span class="text-muted">{{ $equipment->updated_at->format('M d, Y H:i') }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('lab.equipment.show', $equipment) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye"></i> View Details
                        </a>
                        <a href="{{ route('lab.equipment.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-list"></i> All Equipment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
