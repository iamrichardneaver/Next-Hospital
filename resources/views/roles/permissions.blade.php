@extends('layouts.app')

@section('title', 'Permissions')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Permissions</h1>
            <p class="text-muted">All web permissions available for role and user assignment</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Roles
            </a>
            @can('manage_roles')
            <a href="{{ route('roles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Role
            </a>
            @endcan
        </div>
    </div>

    <div class="alert alert-info border-0 shadow-sm mb-4">
        <div class="d-flex">
            <div class="me-3">
                <i class="bi bi-info-circle-fill" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h5 class="alert-heading mb-2">How permissions are managed</h5>
                <ul class="mb-0 small">
                    <li>Permissions listed here come from the <code>permissions</code> database table after auto-sync or <code>php artisan permissions:sync</code>.</li>
                    <li>Assign permissions to roles via <a href="{{ route('roles.index') }}">Roles</a>, or grant direct permissions on individual users.</li>
                    <li>Developers reference new permissions in code (routes, <code>@can</code>, policies). They appear here automatically; optional descriptions go in <code>config/permissions.php</code>.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div class="stat-label">Total Permissions</div>
                <div class="stat-value">{{ $statistics['total_permissions'] }}</div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="stat-label">Total Roles</div>
                <div class="stat-value">{{ $statistics['total_roles'] }}</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Permission Registry</h5>
                <input type="text" class="form-control form-control-sm w-auto" id="permissionSearch"
                       placeholder="Search permissions..." style="min-width: 240px;">
            </div>
        </div>
        <div class="card-body">
            @forelse($groupedPermissions as $module => $modulePermissions)
            <div class="card mb-3 border permission-module">
                <div class="card-header bg-light">
                    <strong>{{ $module }}</strong>
                    <span class="badge bg-secondary ms-2">{{ $modulePermissions->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($modulePermissions as $permission)
                        <div class="col-md-4 col-lg-3 mb-2 permission-item" data-name="{{ strtolower($permission->name) }}">
                            <div class="border rounded p-2 h-100 bg-white">
                                <code class="small d-block text-break">{{ $permission->name }}</code>
                                @if(!empty($descriptions[$permission->name] ?? null))
                                    <small class="text-muted d-block mt-1">{{ $descriptions[$permission->name] }}</small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-5 text-muted">
                <i class="bi bi-shield-x fs-1"></i>
                <p class="mt-2 mb-0">No permissions found. Run <code>php artisan permissions:sync</code>.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('permissionSearch')?.addEventListener('input', function () {
    const query = this.value.trim().toLowerCase();
    document.querySelectorAll('.permission-item').forEach(function (item) {
        const name = item.dataset.name || '';
        item.style.display = !query || name.includes(query) ? '' : 'none';
    });
    document.querySelectorAll('.permission-module').forEach(function (module) {
        const visible = module.querySelectorAll('.permission-item[style=""], .permission-item:not([style])').length;
        const anyVisible = Array.from(module.querySelectorAll('.permission-item')).some(function (item) {
            return item.style.display !== 'none';
        });
        module.style.display = anyVisible ? '' : 'none';
    });
});
</script>
@endpush
