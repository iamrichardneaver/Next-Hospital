@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Roles & Permissions</h1>
            <p class="text-muted">Manage user roles and access control</p>
        </div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('roles.export'),
                'permission' => 'manage_roles',
            ])
            @can('manage_permissions')
            <a href="{{ route('permissions.index') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-shield-lock"></i> Manage Permissions
            </a>
            @endcan
            @can('manage_roles')
            <a href="{{ route('roles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Role
            </a>
            @endcan
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="stat-label">Total Roles</div>
                <div class="stat-value">{{ $statistics['total_roles'] }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div class="stat-label">Total Permissions</div>
                <div class="stat-value">{{ $statistics['total_permissions'] }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-label">Users with Roles</div>
                <div class="stat-value">{{ $statistics['total_users_with_roles'] }}</div>
            </div>
        </div>
    </div>

    <!-- Roles Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">All Roles</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Role Name</th>
                            <th>Users</th>
                            <th>Permissions</th>
                            <th>Created At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($role->name === 'super_admin')
                                        <span class="badge bg-danger me-2">
                                            <i class="bi bi-shield-fill-check"></i>
                                        </span>
                                    @endif
                                    <strong>{{ ucwords(str_replace('_', ' ', $role->name)) }}</strong>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $role->users_count }} users</span>
                            </td>
                            <td>
                                <span class="badge bg-success">{{ $role->getValidPermissions()->count() }} permissions</span>
                            </td>
                            <td>{{ $role->created_at->format('M d, Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('roles.show', $role) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                @if(!$role->isProtected())
                                    @can('manage_roles')
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this role?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                    @endcan
                                @else
                                    <span class="badge bg-secondary">Protected</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="bi bi-inbox fs-1 text-muted"></i>
                                <p class="text-muted mt-2">No roles found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

