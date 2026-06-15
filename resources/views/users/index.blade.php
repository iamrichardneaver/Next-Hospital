@extends('layouts.app')

@section('title', 'Users & Roles')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Users & Roles</h1>
            <p class="text-secondary mb-0">Manage system users and permissions</p>
        </div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('users.export'),
                'permission' => 'view_users',
            ])
            @can('create_users')
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Add User
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
    
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-label">Total Users</div>
                <div class="stat-value">{{ number_format($statistics['total']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-person-check"></i>
                </div>
                <div class="stat-label">Active</div>
                <div class="stat-value">{{ number_format($statistics['active']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <div class="stat-label">Doctors</div>
                <div class="stat-value">{{ number_format($statistics['doctors']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-hospital"></i>
                </div>
                <div class="stat-label">Nurses</div>
                <div class="stat-value">{{ number_format($statistics['nurses']) }}</div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 text-dark">System Users</h5>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <input type="text" class="form-control" placeholder="Search users...">
                        @canany(['delete_users', 'manage_users'])
                        <button type="button"
                                class="btn btn-outline-danger"
                                onclick="bulkDeleteUsers()"
                                id="bulk-delete-users-btn"
                                disabled>
                            <i class="bi bi-trash"></i> Delete Selected
                        </button>
                        @endcanany
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="users-table">
                    <thead class="table-light">
                        <tr>
                            @canany(['delete_users', 'manage_users'])
                            <th width="50">
                                <input type="checkbox" id="select-all-users" class="form-check-input">
                            </th>
                            @endcanany
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Permissions</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            @canany(['delete_users', 'manage_users'])
                            <td>
                                @if($user->id === auth()->id())
                                    <input type="checkbox"
                                           class="form-check-input"
                                           disabled
                                           title="You cannot delete your own account">
                                @elseif($user->hasRole('super_admin') && !auth()->user()->hasRole('super_admin'))
                                    <input type="checkbox"
                                           class="form-check-input"
                                           disabled
                                           title="Only super admins can delete super admin accounts">
                                @else
                                    <input type="checkbox"
                                           class="form-check-input user-checkbox"
                                           value="{{ $user->id }}">
                                @endif
                            </td>
                            @endcanany
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar bg-primary me-2" style="width: 35px; height: 35px; font-size: 14px;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <strong>{{ $user->name }}</strong>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @foreach($user->roles as $role)
                                    <span class="badge bg-primary">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                @php
                                    $directPermissionsCount = $user->getDirectPermissionsCount();
                                @endphp
                                @if($directPermissionsCount > 0)
                                    <span class="badge bg-warning text-dark" 
                                          title="{{ $directPermissionsCount }} temporary permission(s) granted">
                                        <i class="bi bi-key"></i> {{ $directPermissionsCount }} Direct
                                    </span>
                                @else
                                    <span class="text-muted small">Role Only</span>
                                @endif
                            </td>
                            <td>
                                @if($user->is_active ?? true)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->can('delete_users') || auth()->user()->can('manage_users') ? '7' : '6' }}" class="text-center py-5">
                                <p class="text-secondary">No users found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
        <div class="card-footer">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

@canany(['delete_users', 'manage_users'])
@push('scripts')
<script>
function bulkDeleteUsers() {
    const selectedIds = [];
    document.querySelectorAll('.user-checkbox:checked').forEach(checkbox => {
        selectedIds.push(checkbox.value);
    });

    if (selectedIds.length === 0) {
        alert('Please select at least one user to delete.');
        return;
    }

    const confirmMessage = selectedIds.length === 1
        ? 'Delete 1 user? This cannot be undone.'
        : `Delete ${selectedIds.length} users? This cannot be undone.`;

    if (!confirm(confirmMessage)) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("users.bulk-delete") }}';

    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);

    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'user_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

function initializeUserCheckboxes() {
    const selectAllCheckbox = document.getElementById('select-all-users');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const bulkDeleteBtn = document.getElementById('bulk-delete-users-btn');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkDeleteUsersButton();
        });
    }

    userCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkDeleteUsersButton();

            if (selectAllCheckbox) {
                const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
                selectAllCheckbox.checked = checkedCount === userCheckboxes.length && userCheckboxes.length > 0;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < userCheckboxes.length;
            }
        });
    });

    function updateBulkDeleteUsersButton() {
        if (!bulkDeleteBtn) {
            return;
        }

        const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
        if (checkedCount > 0) {
            bulkDeleteBtn.disabled = false;
            bulkDeleteBtn.innerHTML = `<i class="bi bi-trash"></i> Delete Selected (${checkedCount})`;
        } else {
            bulkDeleteBtn.disabled = true;
            bulkDeleteBtn.innerHTML = '<i class="bi bi-trash"></i> Delete Selected';
        }
    }

    updateBulkDeleteUsersButton();
}

document.addEventListener('DOMContentLoaded', initializeUserCheckboxes);
</script>
@endpush
@endcanany
@endsection
