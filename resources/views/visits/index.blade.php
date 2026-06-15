@extends('layouts.app')

@section('title', 'Visits')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1" style="color: #1e3a5f;">Patient Visits</h1><p class="text-secondary mb-0">OPD/IPD Check-In & Management</p></div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('visits.export'),
                'permission' => 'view_visits',
            ])
            @can('create_visits')
            <a href="{{ route('visits.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Check-In Patient</a>
            @endcan
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div class="stat-label">Total Visits</div>
                <div class="stat-value">{{ $statistics['total'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-activity"></i>
                </div>
                <div class="stat-label">Active</div>
                <div class="stat-value">{{ $statistics['active'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Completed</div>
                <div class="stat-value">{{ $statistics['completed'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-label">Today</div>
                <div class="stat-value">{{ $statistics['today'] }}</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0 text-dark">All Visits</h5>
            <div class="d-flex align-items-center gap-2">
                <form method="GET" action="{{ route('visits.index') }}" class="d-flex align-items-center gap-2">
                    <label for="status-filter" class="form-label mb-0 text-nowrap small text-secondary">Status:</label>
                    <select class="form-select form-select-sm" id="status-filter" name="status" onchange="this.form.submit()" style="width: auto; min-width: 10rem;">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Ongoing</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="transferred" {{ request('status') === 'transferred' ? 'selected' : '' }}>Transferred</option>
                    </select>
                </form>
                @can('manage_visits')
                <button type="button" 
                        class="btn btn-outline-danger btn-sm" 
                        onclick="bulkDeleteVisits()" 
                        id="bulk-delete-visits-btn" 
                        disabled>
                    <i class="bi bi-trash"></i> Delete Selected
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            @can('manage_visits')
                            <th width="50">
                                <input type="checkbox" id="select-all-visits" class="form-check-input">
                            </th>
                            @endcan
                            <th>Visit Token</th>
                            <th>Patient</th>
                            <th>Type</th>
                            <th>Check-In</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($visits as $visit)
                        <tr>
                            @can('manage_visits')
                            <td>
                                <input type="checkbox" class="form-check-input visit-checkbox" value="{{ $visit->id }}">
                            </td>
                            @endcan
                            <td><strong>{{ $visit->visit_token }}</strong></td>
                            <td>
                                @if($visit->patient)
                                    {{ $visit->patient->full_name }}<br><small class="text-secondary">{{ $visit->patient->patient_number }}</small>
                                @else
                                    <span class="text-danger">Patient Not Found</span><br><small class="text-muted">ID: {{ $visit->patient_id }}</small>
                                @endif
                            </td>
                            <td>
                                @php
                                    $typeColors = [
                                        'OPD' => 'primary',
                                        'IPD' => 'success', 
                                        'Emergency' => 'danger',
                                        'LabOnly' => 'info',
                                        'PharmacyOnly' => 'warning'
                                    ];
                                    $color = $typeColors[$visit->visit_type] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">{{ strtoupper($visit->visit_type) }}</span>
                            </td>
                            <td>{{ $visit->check_in_time->format('M d, Y h:i A') }}</td>
                            <td>
                                @php
                                    $visitStatusConfig = [
                                        'active' => ['badge' => 'warning', 'label' => 'Ongoing'],
                                        'completed' => ['badge' => 'success', 'label' => 'Completed'],
                                        'cancelled' => ['badge' => 'danger', 'label' => 'Cancelled'],
                                        'transferred' => ['badge' => 'info', 'label' => 'Transferred'],
                                    ];
                                    $statusConfig = $visitStatusConfig[$visit->status] ?? ['badge' => 'secondary', 'label' => ucfirst($visit->status ?? 'Unknown')];
                                @endphp
                                <span class="badge bg-{{ $statusConfig['badge'] }}">{{ $statusConfig['label'] }}</span>
                            </td>
                            <td><a href="{{ route('visits.show', $visit) }}" class="btn btn-sm btn-info"><i class="bi bi-eye"></i> View</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $visits->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
// Bulk delete functionality for visits
function bulkDeleteVisits() {
    const selectedIds = [];
    document.querySelectorAll('.visit-checkbox:checked').forEach(checkbox => {
        selectedIds.push(checkbox.value);
    });
    
    if (selectedIds.length === 0) {
        alert('Please select at least one visit to delete.');
        return;
    }
    
    const confirmMessage = selectedIds.length === 1
        ? 'Are you sure you want to delete this visit? This action cannot be undone.'
        : `Are you sure you want to delete ${selectedIds.length} selected visit(s)? This action cannot be undone.`;
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Create a form for bulk delete
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("visits.bulk-delete") }}';
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);
    
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

// Initialize checkbox functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all-visits');
    const visitCheckboxes = document.querySelectorAll('.visit-checkbox');
    const bulkDeleteBtn = document.getElementById('bulk-delete-visits-btn');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            visitCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkDeleteButton();
        });
    }
    
    visitCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkDeleteButton();
            
            // Update select all checkbox
            if (selectAllCheckbox) {
                const checkedCount = document.querySelectorAll('.visit-checkbox:checked').length;
                selectAllCheckbox.checked = checkedCount === visitCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < visitCheckboxes.length;
            }
        });
    });
    
    function updateBulkDeleteButton() {
        if (!bulkDeleteBtn) return;
        
        const checkedCount = document.querySelectorAll('.visit-checkbox:checked').length;
        if (checkedCount > 0) {
            bulkDeleteBtn.disabled = false;
            bulkDeleteBtn.innerHTML = `<i class="bi bi-trash"></i> Delete Selected (${checkedCount})`;
        } else {
            bulkDeleteBtn.disabled = true;
            bulkDeleteBtn.innerHTML = '<i class="bi bi-trash"></i> Delete Selected';
        }
    }
    
    // Initial update
    updateBulkDeleteButton();
});
</script>
@endpush
@endsection
