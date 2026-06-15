@extends('layouts.app')

@section('title', 'Prescription Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-prescription"></i> Prescription Management</h1>
            <p class="text-secondary mb-0">Manage prescriptions and dispensing workflow</p>
        </div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('pharmacy.prescriptions.export'),
                'permission' => 'dispense_drugs',
                'params' => request()->only(['status', 'priority', 'search']),
            ])
            <a href="{{ route('pharmacy.dispensing') }}" class="btn btn-primary">
                <i class="bi bi-capsule-pill"></i> Dispensing Workflow
            </a>
            <a href="{{ route('pharmacy.stock') }}" class="btn btn-info">
                <i class="bi bi-box-seam"></i> Stock Management
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-prescription"></i>
                </div>
                <div class="stat-label">Total Prescriptions</div>
                <div class="stat-value">{{ number_format($statistics['total']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-label">Pending</div>
                <div class="stat-value">{{ number_format($statistics['pending']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Dispensed</div>
                <div class="stat-value">{{ number_format($statistics['dispensed']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-check2-all"></i>
                </div>
                <div class="stat-label">Completed</div>
                <div class="stat-value">{{ number_format($statistics['completed']) }}</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Patient name, prescription number...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="dispensed" {{ request('status') == 'dispensed' ? 'selected' : '' }}>Dispensed</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('pharmacy.prescriptions') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Prescriptions Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Prescriptions</h5>
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
                                <th>Status</th>
                                <th>Orders</th>
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
                                    {{ $prescription->prescription_date->format('M d, Y') }}
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($prescription->status) {
                                            'pending' => 'bg-warning text-dark',
                                            'dispensed' => 'bg-info text-white',
                                            'completed' => 'bg-success text-white',
                                            'cancelled' => 'bg-danger text-white',
                                            default => 'bg-secondary text-white'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ ucfirst($prescription->status) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $prescription->orders->count() }} items</span>
                                </td>
                                <td>
                                    <div class="dropdown position-static">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow">
                                            @can('view_prescriptions')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('pharmacy.prescriptions.show', $prescription) }}">
                                                    <i class="bi bi-eye"></i> View Details
                                                </a>
                                            </li>
                                            @endcan
                                            @if($prescription->status === 'pending')
                                            @can('edit_prescriptions')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('pharmacy.prescriptions.show', $prescription) }}?dispense=true">
                                                    <i class="bi bi-capsule-pill"></i> Dispense
                                                </a>
                                            </li>
                                            @endcan
                                            @endif
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
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No prescriptions found</h5>
                    <p class="text-muted">No prescriptions match your current filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
