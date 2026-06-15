@extends('layouts.app')

@section('title', 'Branches Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-geo-alt"></i> Branch Management
            </h1>
            <p class="text-secondary mb-0">Manage hospital branches and locations</p>
        </div>
        <div>
            @can('create_branches')
            <a href="{{ route('branches.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>
                Add New Branch
            </a>
            @endcan
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-geo-alt"></i>
                </div>
                <div class="stat-label">Total Branches</div>
                <div class="stat-value">{{ number_format($statistics['total']) }}</div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Active Branches</div>
                <div class="stat-value">{{ number_format($statistics['active']) }}</div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-label">Inactive Branches</div>
                <div class="stat-value">{{ number_format($statistics['inactive']) }}</div>
            </div>
        </div>
    </div>

    <!-- Branches Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-list"></i> All Branches
            </h5>
        </div>
        <div class="card-body">
            @if($branches->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Branch Name</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branches as $branch)
                            <tr>
                                <td><strong>#{{ $branch->id }}</strong></td>
                                <td>
                                    <strong class="text-primary">{{ $branch->name }}</strong>
                                </td>
                                <td>{{ $branch->address ?? 'N/A' }}</td>
                                <td>{{ $branch->phone ?? 'N/A' }}</td>
                                <td>{{ $branch->email ?? 'N/A' }}</td>
                                <td>
                                    @if($branch->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $branch->created_at ? $branch->created_at->format('M d, Y') : 'N/A' }}</td>
                                <td>
                                    <div class="dropdown position-static">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow">
                                            @can('view_branches')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('branches.show', $branch) }}">
                                                    <i class="bi bi-eye"></i> View Details
                                                </a>
                                            </li>
                                            @endcan
                                            @can('edit_branches')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('branches.edit', $branch) }}">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                            </li>
                                            @endcan
                                            @can('delete_branches')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('branches.destroy', $branch) }}" method="POST" 
                                                      onsubmit="return confirm('Are you sure you want to delete this branch?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($branches->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $branches->links() }}
                </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="bi bi-geo-alt" style="font-size: 3rem; color: #ccc;"></i>
                    <h5 class="mt-3">No Branches Found</h5>
                    <p class="text-muted">Start by adding your first branch.</p>
                    @can('create_branches')
                    <a href="{{ route('branches.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Branch
                    </a>
                    @endcan
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

.stat-card.success {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card.danger {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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

