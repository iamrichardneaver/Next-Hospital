@extends('layouts.app')

@section('title', 'Appointment Slots')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Appointment Slots Management</h2>
                <div>
                    @if(!$showAll)
                        <a href="{{ route('appointments.slots.index', array_merge(request()->all(), ['show_all' => '1'])) }}" class="btn btn-info me-2" title="Show all records without pagination">
                            <i class="fas fa-list"></i> Show All ({{ $slots->total() ?? 0 }})
                        </a>
                    @else
                        <a href="{{ route('appointments.slots.index', request()->except('show_all')) }}" class="btn btn-secondary me-2" title="Show paginated view">
                            <i class="fas fa-table"></i> Show Paginated
                        </a>
                    @endif
                    <button type="button" id="deleteAllRecordsBtn" class="btn btn-danger me-2" title="Delete all matching records (not just visible)">
                        <i class="fas fa-trash-alt"></i> Delete All Matching Records
                    </button>
                    <button type="button" id="deleteAllBtn" class="btn btn-danger me-2" style="display: none;" title="Delete all visible slots without bookings">
                        <i class="fas fa-trash-alt"></i> Delete All Visible
                    </button>
                    <button type="button" id="bulkDeleteBtn" class="btn btn-danger me-2" style="display: none;">
                        <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                <a href="{{ route('appointments.slots.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Slots
                </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('appointments.slots.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" class="form-select">
                        <option value="">All Doctors</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                {{ $doctor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="booked" {{ request('status') == 'booked' ? 'selected' : '' }}>Booked</option>
                        <option value="blocked" {{ request('status') == 'blocked' ? 'selected' : '' }}>Blocked</option>
                        <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Slots Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" title="Select All" style="display: none;">
                            </th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Branch</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Capacity</th>
                            <th>Fee</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($slots as $slot)
                            <tr>
                                <td>
                                    @if($slot->booked_appointments == 0)
                                        <input type="checkbox" class="slot-checkbox" value="{{ $slot->id }}" data-slot-id="{{ $slot->id }}">
                                    @else
                                        <span class="text-muted" title="Cannot delete slot with bookings">—</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($slot->slot_date)->format('M d, Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}</td>
                                <td>{{ $slot->doctor->name ?? 'N/A' }}</td>
                                <td>{{ $slot->branch->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ ucfirst($slot->appointment_type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($slot->status == 'available')
                                        <span class="badge bg-success">Available</span>
                                    @elseif($slot->status == 'booked')
                                        <span class="badge bg-warning">Booked</span>
                                    @elseif($slot->status == 'blocked')
                                        <span class="badge bg-danger">Blocked</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($slot->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $slot->booked_appointments }} / {{ $slot->max_appointments }}
                                    @if($slot->getRemainingCapacity() > 0)
                                        <small class="text-success">({{ $slot->getRemainingCapacity() }} left)</small>
                                    @else
                                        <small class="text-danger">(Full)</small>
                                    @endif
                                </td>
                                <td>
                                    @if($slot->fee)
                                        {{ $slot->currency }} {{ number_format($slot->fee, 2) }}
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('appointments.slots.edit', $slot->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if($slot->status == 'available')
                                            <form action="{{ route('appointments.slots.block', $slot->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Block Slot">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @elseif($slot->status == 'blocked')
                                            <form action="{{ route('appointments.slots.unblock', $slot->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Unblock Slot">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($slot->booked_appointments == 0)
                                            <form action="{{ route('appointments.slots.destroy', $slot->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this slot?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Slot">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <p class="mb-0">No appointment slots found.</p>
                                    <a href="{{ route('appointments.slots.create') }}" class="btn btn-sm btn-primary mt-2">
                                        Create Your First Slot
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if(!$showAll && $slots->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $slots->links() }}
            </div>
            @elseif($showAll)
                <div class="d-flex justify-content-center mt-4">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> Showing all {{ $slots->total() }} records. 
                        <a href="{{ route('appointments.slots.index', request()->except('show_all')) }}" class="alert-link">Switch to paginated view</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const slotCheckboxes = document.querySelectorAll('.slot-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const deleteAllBtn = document.getElementById('deleteAllBtn');
    const deleteAllRecordsBtn = document.getElementById('deleteAllRecordsBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    
    // Exit early if no checkboxes exist
    if (!slotCheckboxes || slotCheckboxes.length === 0) {
        if (selectAllCheckbox) selectAllCheckbox.style.display = 'none';
        if (deleteAllBtn) deleteAllBtn.style.display = 'none';
        return;
    }
    
    // Show/hide Select All checkbox based on available slots
    if (selectAllCheckbox) {
        selectAllCheckbox.style.display = 'inline-block';
    }
    
    // Show Delete All button if there are deletable slots
    if (deleteAllBtn) {
        deleteAllBtn.style.display = 'inline-block';
    }
    
    // Update Select All checkbox state
    function updateSelectAllCheckbox() {
        if (!selectAllCheckbox || slotCheckboxes.length === 0) {
            return;
        }
        
        const checkedCount = document.querySelectorAll('.slot-checkbox:checked').length;
        if (checkedCount === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCount === slotCheckboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }
    
    // Update bulk delete button visibility and count
    function updateBulkDeleteButton() {
        if (!bulkDeleteBtn || !selectedCountSpan) {
            return;
        }
        
        const checkedCheckboxes = document.querySelectorAll('.slot-checkbox:checked');
        const selectedCount = checkedCheckboxes.length;
        
        if (selectedCount > 0) {
            bulkDeleteBtn.style.display = 'inline-block';
            selectedCountSpan.textContent = selectedCount;
        } else {
            bulkDeleteBtn.style.display = 'none';
            selectedCountSpan.textContent = '0';
        }
    }
    
    // Select All functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            slotCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkDeleteButton();
        });
    }
    
    // Individual checkbox change - use event delegation for better performance
    slotCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllCheckbox();
            updateBulkDeleteButton();
        });
    });
    
    // Delete All Visible functionality
    if (deleteAllBtn) {
        deleteAllBtn.addEventListener('click', function() {
            const allSlotIds = Array.from(slotCheckboxes).map(cb => cb.value).filter(id => id);
            
            if (allSlotIds.length === 0) {
                alert('No slots available to delete.');
                return;
            }
            
            const confirmMessage = `Are you sure you want to delete ALL ${allSlotIds.length} visible slot(s) without bookings? This action cannot be undone.`;
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("appointments.slots.bulk-delete") }}';
            
            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);
            
            // Add all slot IDs
            allSlotIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'slot_ids[]';
                input.value = id;
                form.appendChild(input);
            });
            
            // Preserve filter parameters from the filter form
            const filterForm = document.querySelector('form[method="GET"]');
            if (filterForm) {
                const filterInputs = filterForm.querySelectorAll('input, select');
                filterInputs.forEach(input => {
                    // Only include fields with names and non-empty values
                    if (input.name && input.value && input.value.trim() !== '') {
                        const filterInput = document.createElement('input');
                        filterInput.type = 'hidden';
                        filterInput.name = input.name;
                        filterInput.value = input.value.trim();
                        form.appendChild(filterInput);
                    }
                });
            }
            
            // Show loading state
            deleteAllBtn.disabled = true;
            deleteAllBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting All...';
            
            // Disable bulk delete button too
            if (bulkDeleteBtn) {
                bulkDeleteBtn.disabled = true;
            }
            if (deleteAllRecordsBtn) {
                deleteAllRecordsBtn.disabled = true;
            }
            
            // Submit form
            document.body.appendChild(form);
            form.submit();
        });
    }
    
    // Delete All Matching Records functionality (applies to all filtered records, not just visible)
    if (deleteAllRecordsBtn) {
        deleteAllRecordsBtn.addEventListener('click', function() {
            const confirmMessage = 'WARNING: This will delete ALL appointment slots matching your current filters (not just the visible ones). Slots with existing bookings will be skipped. This action cannot be undone. Are you sure you want to continue?';
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Double confirmation for safety
            const doubleConfirmMessage = 'Are you absolutely certain? This will permanently delete all matching slots from the database.';
            
            if (!confirm(doubleConfirmMessage)) {
                return;
            }
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("appointments.slots.bulk-delete") }}';
            
            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);
            
            // Add delete_all flag
            const deleteAllInput = document.createElement('input');
            deleteAllInput.type = 'hidden';
            deleteAllInput.name = 'delete_all';
            deleteAllInput.value = '1';
            form.appendChild(deleteAllInput);
            
            // Preserve filter parameters from the filter form
            const filterForm = document.querySelector('form[method="GET"]');
            if (filterForm) {
                const filterInputs = filterForm.querySelectorAll('input, select');
                filterInputs.forEach(input => {
                    // Only include fields with names and non-empty values
                    if (input.name && input.value && input.value.trim() !== '') {
                        const filterInput = document.createElement('input');
                        filterInput.type = 'hidden';
                        filterInput.name = input.name;
                        filterInput.value = input.value.trim();
                        form.appendChild(filterInput);
                    }
                });
            }
            
            // Preserve show_all parameter if it exists
            @if($showAll)
                const showAllInput = document.createElement('input');
                showAllInput.type = 'hidden';
                showAllInput.name = 'show_all';
                showAllInput.value = '1';
                form.appendChild(showAllInput);
            @endif
            
            // Show loading state
            deleteAllRecordsBtn.disabled = true;
            deleteAllRecordsBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting All Records...';
            
            // Disable other buttons too
            if (bulkDeleteBtn) {
                bulkDeleteBtn.disabled = true;
            }
            if (deleteAllBtn) {
                deleteAllBtn.disabled = true;
            }
            
            // Submit form
            document.body.appendChild(form);
            form.submit();
        });
    }
    
    // Bulk delete functionality
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            const checkedCheckboxes = document.querySelectorAll('.slot-checkbox:checked');
            const selectedIds = Array.from(checkedCheckboxes).map(cb => cb.value).filter(id => id);
            
            if (selectedIds.length === 0) {
                alert('Please select at least one slot to delete.');
                return;
            }
            
            // Confirm deletion
            const confirmMessage = selectedIds.length === 1 
                ? 'Are you sure you want to delete this slot?' 
                : `Are you sure you want to delete ${selectedIds.length} slots?`;
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("appointments.slots.bulk-delete") }}';
            
            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);
            
            // Add selected slot IDs
            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'slot_ids[]';
                input.value = id;
                form.appendChild(input);
            });
            
            // Show loading state
            bulkDeleteBtn.disabled = true;
            bulkDeleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
            
            // Disable delete all button too
            if (deleteAllBtn) {
                deleteAllBtn.disabled = true;
            }
            
            // Submit form
            document.body.appendChild(form);
            form.submit();
        });
    }
    
    // Initialize
    updateSelectAllCheckbox();
    updateBulkDeleteButton();
});
</script>
@endpush
@endsection

