@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Edit Role: {{ ucwords(str_replace('_', ' ', $role->name)) }}</h1>
            <p class="text-muted">Update role permissions and settings</p>
        </div>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Roles
        </a>
    </div>

    <!-- Edit Form -->
    <div class="row">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Role Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $role->name) }}" 
                                   placeholder="e.g., content_manager, billing_clerk"
                                   {{ $role->name === 'super_admin' ? 'readonly' : 'required' }}>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($role->name === 'super_admin')
                                <small class="text-muted">Super admin role name cannot be changed</small>
                            @else
                                <small class="text-muted">Use lowercase with underscores (e.g., content_manager)</small>
                            @endif
                        </div>

                        @if($role->name !== 'super_admin')
                        <div class="mb-4">
                            <h5 class="mb-3">Assign Permissions</h5>
                            <p class="text-muted">Select the permissions this role should have</p>
                            
                            <!-- Select All Checkbox -->
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                                <label class="form-check-label fw-bold" for="selectAll">
                                    Select All Permissions
                                </label>
                            </div>

                            <hr>

                            <!-- Grouped Permissions -->
                            @foreach($groupedPermissions as $module => $perms)
                            <div class="card mb-3 border">
                                <div class="card-header bg-light">
                                    <div class="form-check">
                                        <input class="form-check-input module-checkbox" 
                                               type="checkbox" 
                                               id="module_{{ Str::slug($module) }}"
                                               data-module="{{ Str::slug($module) }}">
                                        <label class="form-check-label fw-bold" for="module_{{ Str::slug($module) }}">
                                            {{ $module }} ({{ $perms->count() }})
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($perms as $permission)
                                        <div class="col-md-3 col-sm-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input permission-checkbox module-{{ Str::slug($module) }}" 
                                                       type="checkbox" 
                                                       name="permissions[]" 
                                                       value="{{ $permission->id }}" 
                                                       id="permission_{{ $permission->id }}"
                                                       {{ in_array($permission->id, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                    {{ str_replace('_', ' ', $permission->name) }}
                                                </label>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('roles.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Role
                            </button>
                        </div>
                        @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Super admin role has all permissions by default and cannot be modified.
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@if($role->name !== 'super_admin')
@push('scripts')
<script>
    // Select All functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.permission-checkbox');
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        
        const moduleCheckboxes = document.querySelectorAll('.module-checkbox');
        moduleCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
    });

    // Module checkbox functionality
    document.querySelectorAll('.module-checkbox').forEach(moduleCheckbox => {
        moduleCheckbox.addEventListener('change', function() {
            const module = this.dataset.module;
            const permissionCheckboxes = document.querySelectorAll(`.module-${module}`);
            permissionCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
    });

    // Update module checkbox when individual permissions change
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const classList = Array.from(this.classList);
            const moduleClass = classList.find(c => c.startsWith('module-'));
            if (moduleClass) {
                const module = moduleClass.replace('module-', '');
                const moduleCheckbox = document.querySelector(`[data-module="${module}"]`);
                const modulePermissions = document.querySelectorAll(`.module-${module}`);
                const checkedPermissions = Array.from(modulePermissions).filter(cb => cb.checked);
                
                moduleCheckbox.checked = checkedPermissions.length === modulePermissions.length;
            }
        });
    });
    
    // Initialize module checkboxes state
    document.querySelectorAll('.module-checkbox').forEach(moduleCheckbox => {
        const module = moduleCheckbox.dataset.module;
        const modulePermissions = document.querySelectorAll(`.module-${module}`);
        const checkedPermissions = Array.from(modulePermissions).filter(cb => cb.checked);
        moduleCheckbox.checked = checkedPermissions.length === modulePermissions.length;
    });
</script>
@endpush
@endif
@endsection

