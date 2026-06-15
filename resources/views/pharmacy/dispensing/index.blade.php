@extends('layouts.app')

@section('title', 'Dispensing Workflow')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-capsule-pill"></i> Dispensing Workflow</h1>
            <p class="text-secondary mb-0">Process prescription dispensing efficiently</p>
        </div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('pharmacy.dispensing.export'),
                'permission' => 'dispense_drugs',
                'params' => request()->only(['search']),
            ])
            <a href="{{ route('pharmacy.prescriptions') }}" class="btn btn-info">
                <i class="bi bi-prescription"></i> All Prescriptions
            </a>
            <a href="{{ route('pharmacy.stock') }}" class="btn btn-warning">
                <i class="bi bi-box-seam"></i> Stock Management
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-label">Pending Prescriptions</div>
                <div class="stat-value">{{ number_format($statistics['pending_prescriptions']) }}</div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Dispensed Today</div>
                <div class="stat-value">{{ number_format($statistics['dispensed_today']) }}</div>
            </div>
        </div>
    </div>

    <!-- Search Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <label for="search" class="form-label">Search Prescriptions</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Patient name, prescription number...">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('pharmacy.dispensing') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Pending Prescriptions -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Pending Prescriptions</h5>
        </div>
        <div class="card-body">
            @if($prescriptions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Prescription #</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prescriptions as $prescription)
                            <tr>
                                <td>
                                    <strong>{{ $prescription->prescription_number }}</strong>
                                </td>
                                <td>
                                    <div>
                                        @if($prescription->patient)
                                            <strong>{{ $prescription->patient->first_name }} {{ $prescription->patient->last_name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $prescription->patient->patient_number }}</small>
                                        @else
                                            <strong class="text-muted">Unknown Patient</strong>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($prescription->doctor)
                                        {{ $prescription->doctor->first_name }} {{ $prescription->doctor->last_name }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $prescription->prescription_date ? $prescription->prescription_date->format('M d, Y') : 'N/A' }}
                                    <br>
                                    <small class="text-muted">{{ $prescription->prescription_date ? $prescription->prescription_date->diffForHumans() : 'N/A' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $prescription->orders->count() }} items</span>
                                    <br>
                                    <small class="text-muted">
                                        {{ $prescription->orders->where('status', 'pending')->count() }} pending
                                    </small>
                                </td>
                                <td>
                                    @php
                                        $hoursSincePrescription = $prescription->prescription_date ? $prescription->prescription_date->diffInHours(now()) : 0;
                                        $priorityClass = $hoursSincePrescription > 24 ? 'bg-danger' : ($hoursSincePrescription > 12 ? 'bg-warning' : 'bg-success');
                                        $priorityText = $hoursSincePrescription > 24 ? 'High' : ($hoursSincePrescription > 12 ? 'Medium' : 'Low');
                                    @endphp
                                    <span class="badge {{ $priorityClass }}">{{ $priorityText }}</span>
                                </td>
                                <td>
                                    <div class="dropdown position-static">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('pharmacy.prescriptions.show', $prescription) }}">
                                                    <i class="bi bi-eye"></i> View Details
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('pharmacy.prescriptions.show', $prescription) }}?dispense=true">
                                                    <i class="bi bi-capsule-pill"></i> Start Dispensing
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $prescriptions->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-check-circle" style="font-size: 3rem; color: #28a745;"></i>
                    <h5 class="mt-3 text-success">All Caught Up!</h5>
                    <p class="text-muted">No pending prescriptions to dispense.</p>
                    <a href="{{ route('pharmacy.prescriptions') }}" class="btn btn-primary">
                        <i class="bi bi-prescription"></i> View All Prescriptions
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    padding: 1.5rem;
    color: white;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.stat-card.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card.success {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card.info {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-card.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    opacity: 0.8;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
}
</style>
@endsection
