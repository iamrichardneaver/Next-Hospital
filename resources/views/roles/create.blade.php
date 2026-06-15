@extends('layouts.app')

@section('title', 'Create Role')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Create New Role</h1>
            <p class="text-muted">Define a new role with specific permissions</p>
        </div>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Roles
        </a>
    </div>

    <!-- Create Form -->
    <div class="row">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Role Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('roles.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   placeholder="e.g., content_manager, billing_clerk"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Use lowercase with underscores (e.g., content_manager)</small>
                        </div>

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
                                                       {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
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
                                <i class="bi bi-check-circle"></i> Create Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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
</script>
@endpush
@endsection

