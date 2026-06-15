@extends('layouts.app')

@section('title', 'Ward Details - ' . $ward->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">{{ $ward->name }}</h1>
            <p class="text-secondary mb-0">{{ $ward->description ?? 'Ward details and bed management' }}</p>
        </div>
        <div>
            <a href="{{ route('wards.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Wards
            </a>
            @can('edit_wards')
            <a href="{{ route('wards.edit', $ward) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit Ward
            </a>
            @endcan
        </div>
    </div>

    <!-- Ward Statistics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center mb-2">
                        <i class="bi bi-building text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="mb-1 text-dark">{{ $ward->name }}</h4>
                    <p class="text-muted mb-0">{{ ucfirst($ward->type) }} Ward</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center mb-2">
                        <i class="bi bi-hospital-bed text-info" style="font-size: 2rem;"></i>
                    </div>
                    <h3 class="mb-1 text-dark">{{ $ward->total_beds }}</h3>
                    <p class="text-muted mb-0">Total Beds</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center mb-2">
                        <i class="bi bi-person-fill text-danger" style="font-size: 2rem;"></i>
                    </div>
                    <h3 class="mb-1 text-dark">{{ $ward->beds->where('status', 'occupied')->count() }}</h3>
                    <p class="text-muted mb-0">Occupied</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center mb-2">
                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                    </div>
                    <h3 class="mb-1 text-dark">{{ $ward->beds->where('status', 'vacant')->count() }}</h3>
                    <p class="text-muted mb-0">Available</p>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-success mb-4">
        <i class="bi bi-cash-coin"></i>
        <strong>IPD billing policy:</strong> Inpatients may pay partially or in full, before or after service.
        Use <a href="{{ route('cashier.index') }}">Cashier</a> to collect payments without blocking admission or discharge.
    </div>

    <!-- Ward Information Card -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Ward Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Ward Code:</strong>
                        <span class="badge bg-secondary ms-2">{{ $ward->code }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Type:</strong>
                        <span class="badge bg-info ms-2">{{ ucfirst($ward->type) }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Status:</strong>
                        <span class="badge bg-{{ $ward->is_active ? 'success' : 'danger' }} ms-2">
                            {{ $ward->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    @if($ward->description)
                    <div class="mb-0">
                        <strong>Description:</strong>
                        <p class="text-muted mt-1">{{ $ward->description }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bed Layout -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-grid-3x3"></i> Bed Layout</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($ward->beds as $bed)
                        <div class="col-md-3 col-sm-4 col-6 mb-3">
                            <div class="card h-100 {{ $bed->status === 'occupied' ? 'border-danger shadow-sm' : ($bed->status === 'reserved' ? 'border-warning shadow-sm' : 'border-success') }}">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <i class="bi bi-hospital-bed {{ $bed->status === 'occupied' ? 'text-danger' : ($bed->status === 'reserved' ? 'text-warning' : 'text-success') }}" style="font-size: 1.5rem;"></i>
                                    </div>
                                    <h6 class="mb-2">Bed {{ $bed->bed_number }}</h6>
                                    
                                    <!-- Status Badge -->
                                    <div class="mb-2">
                                        @if($bed->status === 'occupied')
                                            <span class="badge bg-danger">Occupied</span>
                                        @elseif($bed->status === 'reserved')
                                            <span class="badge bg-warning text-dark">Reserved</span>
                                        @elseif($bed->status === 'maintenance')
                                            <span class="badge bg-secondary">Maintenance</span>
                                        @else
                                            <span class="badge bg-success">Available</span>
                                        @endif
                                    </div>

                                    <!-- Patient Name Badge for Occupied Beds -->
                                    @if($bed->status === 'occupied' && $bed->currentAssignment && $bed->currentAssignment->patient)
                                    <div class="mt-2">
                                        <div class="patient-badge">
                                            <i class="bi bi-person-fill me-1"></i>
                                            {{ $bed->currentAssignment->patient->first_name }} {{ $bed->currentAssignment->patient->last_name }}
                                        </div>
                                        @if($bed->currentAssignment->admission_date)
                                        <div class="admission-date">
                                            Since {{ \Carbon\Carbon::parse($bed->currentAssignment->admission_date)->format('M d, Y') }}
                                        </div>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Bed Type -->
                                    <div class="small text-muted mt-2">
                                        <i class="bi bi-tag"></i> {{ ucfirst($bed->bed_type) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    @if($ward->beds->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-hospital-bed text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">No beds assigned to this ward yet.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Bed Status Legend</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <span class="badge bg-success me-2">Available</span>
                            <small class="text-muted">Bed is ready for new patients</small>
                        </div>
                        <div class="col-md-3 mb-2">
                            <span class="badge bg-danger me-2">Occupied</span>
                            <small class="text-muted">Bed is currently assigned to a patient</small>
                        </div>
                        <div class="col-md-3 mb-2">
                            <span class="badge bg-warning text-dark me-2">Reserved</span>
                            <small class="text-muted">Bed is reserved for upcoming admission</small>
                        </div>
                        <div class="col-md-3 mb-2">
                            <span class="badge bg-secondary me-2">Maintenance</span>
                            <small class="text-muted">Bed is under maintenance</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge {
    font-weight: 500;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
}

.border-danger {
    border-width: 2px !important;
}

.border-warning {
    border-width: 2px !important;
}

.border-success {
    border-width: 2px !important;
}

.patient-badge {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
    border: 2px solid rgba(255, 255, 255, 0.2);
    text-align: center;
    margin-bottom: 4px;
    transition: all 0.3s ease;
}

.patient-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

.admission-date {
    font-size: 0.65rem;
    color: #6c757d;
    text-align: center;
    font-style: italic;
    margin-top: 2px;
}

/* Enhanced bed card styling for occupied beds */
.card.border-danger .card-body {
    background: linear-gradient(135deg, #fff5f5, #ffffff);
}

.card.border-warning .card-body {
    background: linear-gradient(135deg, #fffbf0, #ffffff);
}

.card.border-success .card-body {
    background: linear-gradient(135deg, #f0fff4, #ffffff);
}

/* Status badge enhancements */
.badge.bg-danger {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
}

.badge.bg-success {
    background: linear-gradient(135deg, #198754, #157347) !important;
    box-shadow: 0 2px 4px rgba(25, 135, 84, 0.3);
}

.badge.bg-warning {
    background: linear-gradient(135deg, #ffc107, #e0a800) !important;
    box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
}
</style>
@endsection