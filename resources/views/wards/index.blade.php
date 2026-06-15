@extends('layouts.app')

@section('title', 'Wards & Beds')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Wards & Bed Management</h1>
            <p class="text-secondary mb-0">Manage wards, beds, and patient admissions</p>
        </div>
        <div>
            @can('create_wards')
            <a href="{{ route('wards.create') }}" class="btn btn-primary">
                <i class="bi bi-building-add"></i> Add Ward
            </a>
            @endcan
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-building"></i>
                </div>
                <div class="stat-label">Total Wards</div>
                <div class="stat-value">{{ number_format($statistics['total_wards']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-hospital-bed"></i>
                </div>
                <div class="stat-label">Total Beds</div>
                <div class="stat-value">{{ number_format($statistics['total_beds']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="stat-label">Occupied</div>
                <div class="stat-value">{{ number_format($statistics['occupied_beds']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Available</div>
                <div class="stat-value">{{ number_format($statistics['available_beds']) }}</div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 text-dark">Wards</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ward Name</th>
                            <th>Type</th>
                            <th>Code</th>
                            <th>Total Beds</th>
                            <th>Occupied</th>
                            <th>Available</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($wards as $ward)
                        <tr>
                            <td><strong>{{ $ward->name }}</strong></td>
                            <td><span class="badge bg-primary">{{ ucfirst($ward->type) }}</span></td>
                            <td>{{ $ward->code ?? 'N/A' }}</td>
                            <td>{{ $ward->total_beds }}</td>
                            <td>{{ $ward->beds->where('status', 'occupied')->count() }}</td>
                            <td>{{ $ward->beds->where('status', 'vacant')->count() }}</td>
                            <td class="position-static">
                                <div class="dropdown position-static">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('wards.show', $ward) }}">
                                                <i class="bi bi-eye text-info me-2"></i>View Details
                                            </a>
                                        </li>
                                        @can('edit_wards')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('wards.edit', $ward) }}">
                                                <i class="bi bi-pencil text-warning me-2"></i>Edit Ward
                                            </a>
                                        </li>
                                        @endcan
                                        @can('delete_wards')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('wards.destroy', $ward) }}" method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to delete this ward? This action cannot be undone.')"
                                                  style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i>Delete Ward
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <p class="text-secondary">No wards found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($wards->hasPages())
        <div class="card-footer">
            {{ $wards->links() }}
        </div>
        @endif
    </div>
</div>

<style>
.dropdown-toggle::after {
    display: none;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
}

.dropdown-item.text-danger:hover {
    background-color: #f8d7da;
    color: #721c24 !important;
}

.btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
}

.table-responsive {
    border-radius: 0.375rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}
</style>
@endsection
