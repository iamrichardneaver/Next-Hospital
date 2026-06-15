@extends('layouts.app')

@section('title', 'Branch Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-geo-alt"></i> Branch Details
            </h1>
            <p class="text-secondary mb-0">{{ $branch->name }}</p>
        </div>
        <div>
            @can('edit_branches')
            <a href="{{ route('branches.edit', $branch) }}" class="btn btn-primary me-2">
                <i class="bi bi-pencil me-2"></i>
                Edit Branch
            </a>
            @endcan
            <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Back to Branches
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Branch Information -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Branch Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Branch ID</th>
                            <td><strong>#{{ $branch->id }}</strong></td>
                        </tr>
                        <tr>
                            <th>Branch Name</th>
                            <td><strong class="text-primary">{{ $branch->name }}</strong></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td>{{ $branch->address ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td>{{ $branch->phone ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $branch->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($branch->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Created</th>
                            <td>{{ $branch->created_at ? $branch->created_at->format('M d, Y h:i A') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Last Updated</th>
                            <td>{{ $branch->updated_at ? $branch->updated_at->format('M d, Y h:i A') : 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Statistics -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Branch Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 border rounded">
                                <i class="bi bi-people" style="font-size: 2rem; color: #667eea;"></i>
                                <h4 class="mt-2 mb-0">{{ $branch->users()->count() }}</h4>
                                <small class="text-muted">Staff Members</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 border rounded">
                                <i class="bi bi-person-badge" style="font-size: 2rem; color: #4facfe;"></i>
                                <h4 class="mt-2 mb-0">{{ \App\Models\Patient::where('branch_id', $branch->id)->count() }}</h4>
                                <small class="text-muted">Patients</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 border rounded">
                                <i class="bi bi-calendar-check" style="font-size: 2rem; color: #f093fb;"></i>
                                <h4 class="mt-2 mb-0">{{ \App\Models\Appointment::where('branch_id', $branch->id)->count() }}</h4>
                                <small class="text-muted">Appointments</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @can('edit_branches')
                        <a href="{{ route('branches.edit', $branch) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-2"></i> Edit Branch
                        </a>
                        @endcan
                        
                        @can('view_users')
                        <a href="{{ route('users.index') }}?branch_id={{ $branch->id }}" class="btn btn-outline-info">
                            <i class="bi bi-people me-2"></i> View Staff
                        </a>
                        @endcan
                        
                        @can('view_patients')
                        <a href="{{ route('patients.index') }}?branch_id={{ $branch->id }}" class="btn btn-outline-success">
                            <i class="bi bi-person-badge me-2"></i> View Patients
                        </a>
                        @endcan
                        
                        @can('delete_branches')
                        <form action="{{ route('branches.destroy', $branch) }}" method="POST" 
                              onsubmit="return confirm('Are you sure you want to delete this branch? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-trash me-2"></i> Delete Branch
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Important Notes</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        <i class="bi bi-info-circle text-primary"></i>
                        Deactivating a branch will hide it from selection dropdowns but won't delete existing data.
                    </p>
                    <p class="small text-muted mb-0">
                        <i class="bi bi-shield-check text-success"></i>
                        All branch data is preserved even if the branch is marked inactive.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

