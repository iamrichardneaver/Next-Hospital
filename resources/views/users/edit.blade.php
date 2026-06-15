@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Edit User</h1>
            <p class="text-secondary mb-0">Update user information and role assignments</p>
        </div>
        <div>
            <a href="{{ route('users.show', $user) }}" class="btn btn-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to User
            </a>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-list"></i> All Users
            </a>
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
    
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Validation Errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <!-- Edit Form -->
    <div class="row">
        <div class="col-lg-8 col-xl-6 mx-auto">
            <form action="{{ route('users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')
                
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark">
                            <i class="bi bi-person-circle me-2"></i>User Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $user->name) }}" 
                                       placeholder="Enter full name"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email', $user->email) }}" 
                                       placeholder="user@example.com"
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Leave blank to keep current password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Leave blank to keep current password</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       placeholder="Confirm new password">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Role Assignment -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark">
                            <i class="bi bi-shield-check me-2"></i>Role & Permissions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="role" class="form-label">User Role</label>
                            <select class="form-select @error('role') is-invalid @enderror" 
                                    id="role" 
                                    name="role"
                                    onchange="updateRoleInfo(this)">
                                <option value="">Select a role</option>
                                @foreach($roles as $role)
                                <option value="{{ $role->name }}" 
                                        data-permissions="{{ $role->permissions->count() }}"
                                        data-role-display="{{ ucwords(str_replace('_', ' ', $role->name)) }}"
                                        {{ old('role', $user->roles->first()?->name) === $role->name ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                </option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="alert alert-info" id="role-info-alert">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Current Role:</strong> 
                            @if($user->roles->isNotEmpty())
                                <strong>{{ ucwords(str_replace('_', ' ', $user->roles->first()->name)) }}</strong>
                                with <strong>{{ $user->roles->first()->permissions->count() }}</strong> permissions
                            @else
                                <strong>No role assigned</strong>
                            @endif
                        </div>
                        
                        <div class="alert alert-warning d-none" id="role-change-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Role Change Detected!</strong><br>
                            <span id="role-change-text"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Current Permissions Display -->
                @if($user->roles->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark">
                            <i class="bi bi-list-check me-2"></i>Current Permissions
                        </h5>
                    </div>
                    <div class="card-body">
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
                </div>
                @endif
                
                <!-- Form Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('users.show', $user) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update User
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store original role for comparison
const originalRole = '{{ $user->roles->first()?->name ?? "" }}';
const originalRoleDisplay = '{{ $user->roles->isNotEmpty() ? ucwords(str_replace("_", " ", $user->roles->first()->name)) : "" }}';
const originalPermissions = {{ $user->roles->first()?->permissions->count() ?? 0 }};

// Update role info when role selection changes
function updateRoleInfo(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const newRole = selectedOption.value;
    const newRoleDisplay = selectedOption.getAttribute('data-role-display');
    const newPermissions = selectedOption.getAttribute('data-permissions');
    
    const warningDiv = document.getElementById('role-change-warning');
    const warningText = document.getElementById('role-change-text');
    
    if (newRole && newRole !== originalRole) {
        // Show warning about role change
        warningDiv.classList.remove('d-none');
        
        const permissionDiff = parseInt(newPermissions) - originalPermissions;
        const diffText = permissionDiff > 0 
            ? `gaining <strong>${permissionDiff}</strong> additional permissions` 
            : `losing <strong>${Math.abs(permissionDiff)}</strong> permissions`;
        
        warningText.innerHTML = `Changing from <strong>${originalRoleDisplay}</strong> (${originalPermissions} permissions) to <strong>${newRoleDisplay}</strong> (${newPermissions} permissions) - ${diffText}.`;
    } else {
        // Hide warning if back to original role
        warningDiv.classList.add('d-none');
    }
}

// Password confirmation validation
document.getElementById('password_confirmation').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password && confirmPassword && password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('password_confirmation');
    if (confirmPassword.value) {
        if (this.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
});
</script>
@endsection
