@extends('layouts.app')

@section('title', 'Manage User Permissions')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Manage Direct Permissions</h1>
            <p class="text-secondary mb-0">
                <strong>{{ $user->name }}</strong> 
                <span class="badge bg-primary ms-2">{{ implode(', ', $userRoles) }}</span>
            </p>
        </div>
        <div>
            <a href="{{ route('users.show', $user) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to User
            </a>
        </div>
    </div>
    
    <!-- Informational Alert -->
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <div class="d-flex">
            <div class="me-3">
                <i class="bi bi-info-circle-fill" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h5 class="alert-heading mb-2">How Permission Management Works</h5>
                <ul class="mb-0 small">
                    <li><strong>Role Permissions:</strong> These permissions come from the user's assigned role(s) and <span class="badge bg-secondary">cannot be changed here</span>. To modify role permissions, edit the role itself.</li>
                    <li><strong>Direct Permissions:</strong> These are <span class="badge bg-success">temporary additional permissions</span> you can grant directly to this user, on top of their role permissions.</li>
                    <li><strong>Use Case:</strong> Perfect for situations like when a lab technician needs to cover receptionist duties temporarily, or a nurse needs to handle billing while the regular staff is absent.</li>
                    <li><strong>Effect:</strong> Changes take effect immediately. The user will see relevant menu items and can access protected features as soon as permissions are saved.</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Current Permissions Summary -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary shadow-sm">
                <div class="card-body">
                    <h6 class="text-primary mb-2">
                        <i class="bi bi-shield-check"></i> Role Permissions
                    </h6>
                    <h3 class="mb-0">{{ count($rolePermissions) }}</h3>
                    <small class="text-muted">From assigned role(s)</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-success shadow-sm">
                <div class="card-body">
                    <h6 class="text-success mb-2">
                        <i class="bi bi-key"></i> Direct Permissions
                    </h6>
                    <h3 class="mb-0">{{ count($directPermissions) }}</h3>
                    <small class="text-muted">Temporary additional grants</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Permission Management Form -->
    <form action="{{ route('users.update-permissions', $user) }}" method="POST" id="permissionsForm">
        @csrf
        @method('PUT')
        
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-grid-3x3-gap"></i> Permission Modules
                    </h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary me-2" id="selectAllBtn">
                            <i class="bi bi-check-square"></i> Select All Direct
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAllBtn">
                            <i class="bi bi-x-square"></i> Clear All Direct
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                
                <!-- Search Box -->
                <div class="mb-4">
                    <input type="text" class="form-control" id="permissionSearch" 
                           placeholder="🔍 Search permissions by name or module...">
                </div>
                
                <!-- Permissions Grid -->
                <div class="row">
                    @foreach($groupedPermissions as $module => $permissions)
                    <div class="col-lg-6 mb-4 permission-module">
                        <div class="border rounded p-3 h-100" style="background-color: #f8f9fa;">
                            <h6 class="text-primary mb-3 fw-bold">
                                <i class="bi bi-folder2-open"></i> {{ $module }}
                            </h6>
                            
                            @foreach($permissions as $permission)
                                @php
                                    $hasViaRole = in_array($permission->id, $rolePermissions);
                                    $hasDirectly = in_array($permission->id, $directPermissions);
                                @endphp
                                
                                <div class="form-check mb-2 permission-item" data-permission-name="{{ strtolower($permission->name) }}" data-module="{{ strtolower($module) }}">
                                    <!-- Show role permission (read-only) -->
                                    @if($hasViaRole)
                                    <div class="d-flex align-items-center mb-1 ps-2" style="opacity: 0.7;">
                                        <i class="bi bi-shield-check text-primary me-2"></i>
                                        <span class="text-muted small">
                                            {{ $permission->name }}
                                            <span class="badge bg-secondary ms-2">Role</span>
                                        </span>
                                    </div>
                                    @endif
                                    
                                    <!-- Direct permission checkbox (always shown, but indicate if already via role) -->
                                    <div class="d-flex align-items-center">
                                        <input class="form-check-input direct-permission-checkbox" 
                                               type="checkbox" 
                                               name="direct_permissions[]" 
                                               value="{{ $permission->id }}" 
                                               id="permission_{{ $permission->id }}"
                                               {{ $hasDirectly ? 'checked' : '' }}>
                                        <label class="form-check-label ms-2" for="permission_{{ $permission->id }}">
                                            {{ $permission->name }}
                                            @if(!$hasViaRole)
                                                <i class="bi bi-plus-circle text-success small" title="Grant this permission directly"></i>
                                            @else
                                                <span class="badge bg-info text-white small">Also Direct</span>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <!-- No Results Message -->
                <div id="noResults" class="text-center text-muted py-4" style="display: none;">
                    <i class="bi bi-search" style="font-size: 2rem;"></i>
                    <p class="mt-2">No permissions found matching your search.</p>
                </div>
            </div>
            
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('users.show', $user) }}" class="btn btn-light">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save Direct Permissions
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('permissionSearch');
    const permissionItems = document.querySelectorAll('.permission-item');
    const permissionModules = document.querySelectorAll('.permission-module');
    const noResults = document.getElementById('noResults');
    const selectAllBtn = document.getElementById('selectAllBtn');
    const clearAllBtn = document.getElementById('clearAllBtn');
    const directCheckboxes = document.querySelectorAll('.direct-permission-checkbox');
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        let visibleCount = 0;
        
        if (searchTerm === '') {
            // Show all
            permissionModules.forEach(module => module.style.display = 'block');
            permissionItems.forEach(item => item.style.display = 'block');
            noResults.style.display = 'none';
            return;
        }
        
        permissionModules.forEach(module => {
            let hasVisibleItems = false;
            const items = module.querySelectorAll('.permission-item');
            
            items.forEach(item => {
                const permissionName = item.dataset.permissionName;
                const moduleName = item.dataset.module;
                
                if (permissionName.includes(searchTerm) || moduleName.includes(searchTerm)) {
                    item.style.display = 'block';
                    hasVisibleItems = true;
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            module.style.display = hasVisibleItems ? 'block' : 'none';
        });
        
        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    });
    
    // Select All Direct Permissions
    selectAllBtn.addEventListener('click', function() {
        directCheckboxes.forEach(checkbox => checkbox.checked = true);
    });
    
    // Clear All Direct Permissions
    clearAllBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to remove all direct permissions from this user? They will still have permissions from their role(s).')) {
            directCheckboxes.forEach(checkbox => checkbox.checked = false);
        }
    });
    
    // Form submission confirmation
    document.getElementById('permissionsForm').addEventListener('submit', function(e) {
        const checkedCount = Array.from(directCheckboxes).filter(cb => cb.checked).length;
        
        if (checkedCount === 0) {
            if (!confirm('No direct permissions selected. This will remove all temporary permission grants. Continue?')) {
                e.preventDefault();
            }
        }
    });
});
</script>
@endpush
@endsection
