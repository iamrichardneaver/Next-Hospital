@extends('layouts.app')

@section('title', 'Role Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ ucwords(str_replace('_', ' ', $role->name)) }}</h1>
            <p class="text-muted">Role details and permissions</p>
        </div>
        <div>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            @if($role->isProtected())
                <span class="badge bg-secondary ms-2">System Role</span>
            @endif
            @if(!$role->isProtected())
                @can('manage_roles')
                <a href="{{ route('roles.edit', $role) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit Role
                </a>
                @endcan
            @endif
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <i class="bi bi-people text-primary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Users with this Role</h6>
                            <h3 class="mb-0">{{ $statistics['users_count'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <i class="bi bi-shield-check text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Assigned Permissions</h6>
                            <h3 class="mb-0">{{ $statistics['permissions_count'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions List -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">Permissions</h5>
        </div>
        <div class="card-body">
            @if($role->name === 'super_admin')
                <div class="alert alert-info mb-0">
                    <i class="bi bi-shield-fill-check"></i> Super admin has access to all permissions in the system.
                </div>
            @elseif($groupedPermissions->isNotEmpty())
                <div class="row">
                    @foreach($groupedPermissions as $module => $permissions)
                    <div class="col-md-6 mb-3">
                        <div class="card border">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ $module ?: 'General' }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($permissions as $permission)
                                    <span class="badge bg-primary">
                                        {{ str_replace('_', ' ', $permission->name) }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i> This role has no permissions assigned.
                </div>
            @endif
        </div>
    </div>

    <!-- Users with this Role -->
    @if($role->users->count() > 0)
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">Users with this Role</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Last Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($role->users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

