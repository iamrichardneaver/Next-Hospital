@extends('layouts.app')

@section('title', 'User Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">User Details</h1>
            <p class="text-secondary mb-0">View user information and permissions</p>
        </div>
        <div>
            <a href="{{ route('users.index') }}" class="btn btn-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Users
            </a>
            @can('edit_users')
            <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit User
            </a>
            @endcan
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <div class="row">
        <!-- User Information -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-person-circle me-2"></i>User Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Full Name</label>
                            <p class="mb-0">{{ $user->name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email Address</label>
                            <p class="mb-0">{{ $user->email }}</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <p class="mb-0">
                                @if($user->is_active ?? true)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Member Since</label>
                            <p class="mb-0">{{ $user->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Roles & Permissions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-shield-check me-2"></i>Roles & Permissions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assigned Roles</label>
                        <div>
                            @forelse($user->roles as $role)
                                <span class="badge bg-primary me-2 mb-2">{{ ucfirst($role->name) }}</span>
                            @empty
                                <span class="text-muted">No roles assigned</span>
                            @endforelse
                        </div>
                    </div>
                    
                    @if($user->roles->isNotEmpty())
                    <div>
                        <label class="form-label fw-bold">Permissions</label>
                        <div class="row">
                            @foreach($user->roles as $role)
                                @if($role->permissions->isNotEmpty())
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted">{{ ucfirst($role->name) }} Permissions:</h6>
                                    <div class="permission-list">
                                        @foreach($role->permissions as $permission)
                                            <span class="badge bg-light text-dark me-1 mb-1">{{ $permission->name }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- User Stats & Actions -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-graph-up me-2"></i>User Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Last Login</span>
                            <span class="fw-bold">
                                {{ $user->last_login_at ? $user->last_login_at->format('M d, Y H:i') : 'Never' }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Account Created</span>
                            <span class="fw-bold">{{ $user->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                    
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Last Updated</span>
                            <span class="fw-bold">{{ $user->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-lightning me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @can('edit_users')
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit User
                        </a>
                        @endcan
                        
                        @can('manage_roles')
                        <a href="{{ route('users.manage-permissions', $user) }}" class="btn btn-outline-success">
                            <i class="bi bi-key"></i> Manage Direct Permissions
                        </a>
                        @endcan
                        
                        @can('delete_users')
                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline" 
                              onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-trash"></i> Delete User
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
