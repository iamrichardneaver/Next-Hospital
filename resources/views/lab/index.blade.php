@extends('layouts.app')

@section('title', 'Laboratory')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Laboratory</h1>
            <p class="text-secondary mb-0">Manage lab requests and test results</p>
        </div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('lab.export'),
                'permission' => 'view_lab_requests',
            ])
            @can('create_lab_requests')
            <a href="{{ route('lab.create') }}" class="btn btn-primary">
                <i class="bi bi-clipboard-plus"></i> New Lab Request
            </a>
            @endcan
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-clipboard-data"></i>
                </div>
                <div class="stat-label">Total Requests</div>
                <div class="stat-value">{{ number_format($statistics['total']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-hourglass"></i>
                </div>
                <div class="stat-label">Pending</div>
                <div class="stat-value">{{ number_format($statistics['pending']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Completed</div>
                <div class="stat-value">{{ number_format($statistics['completed']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-label">Today</div>
                <div class="stat-value">{{ number_format($statistics['today']) }}</div>
            </div>
        </div>
    </div>

    <!-- Payment Tracking Section -->
    @can('process_payments')
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Lab Payment Tracking</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">Pending Payments</h6>
                    <div id="pending-lab-payments">
                        <p class="text-muted">Loading pending lab payments...</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-success">Recent Payments</h6>
                    <div id="recent-lab-payments">
                        <p class="text-muted">Loading recent lab payments...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 text-dark">Lab Requests</h5>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control" placeholder="Search lab requests...">
                        @can('delete_lab_requests')
                        <button type="button" class="btn btn-outline-danger" onclick="bulkDelete()" id="bulk-delete-btn" disabled>
                            <i class="bi bi-trash"></i> Delete Selected
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            @can('delete_lab_requests')
                            <th width="50">
                                <input type="checkbox" id="select-all" class="form-check-input">
                            </th>
                            @endcan
                            <th>Request #</th>
                            <th>Patient</th>
                            <th>Test Type</th>
                            <th>Individual Test</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($labRequests as $request)
                        <tr>
                            @can('delete_lab_requests')
                            <td>
                                <input type="checkbox" class="form-check-input row-checkbox" value="{{ $request->id }}">
                            </td>
                            @endcan
                            <td><strong class="text-primary">{{ $request->request_number ?? 'N/A' }}</strong></td>
                            <td>
                                @if($request->patient)
                                    {{ $request->patient->first_name }} {{ $request->patient->last_name }}
                                @else
                                    <span class="text-danger">Patient Not Found</span>
                                @endif
                            </td>
                            <td>{{ $request->test_type }}</td>
                            <td>
                                @if($request->templates && $request->templates->count() > 0)
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($request->templates->take(3) as $template)
                                            <span class="badge bg-info">{{ $template->template_name }}</span>
                                        @endforeach
                                        @if($request->templates->count() > 3)
                                            <small class="text-muted">+{{ $request->templates->count() - 3 }} more</small>
                                        @endif
                                    </div>
                                @elseif($request->template)
                                    <span class="badge bg-info">{{ $request->template->template_name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $request->created_at->format('M d, Y') }}</td>
                            <td><span class="badge bg-{{ $request->status === 'completed' ? 'success' : 'warning' }}">{{ ucfirst($request->status) }}</span></td>
                            <td class="position-static">
                                <div class="d-flex gap-1">
                                    @can('view_lab_requests')
                                    <a href="{{ route('lab.show', $request) }}" class="btn btn-sm btn-info" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @endcan
                                    @can('edit_lab_requests')
                                    <a href="{{ route('lab.edit', $request) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    @can('delete_lab_requests')
                                    <div class="dropdown position-static">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow">
                                            <li>
                                                <button class="dropdown-item text-danger" type="button" onclick="confirmDelete('{{ route('lab.destroy', $request) }}', '{{ $request->request_number ?? $request->id }}')">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->can('delete_lab_requests') ? '8' : '7' }}" class="text-center py-5">
                                <p class="text-secondary">No lab requests found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($labRequests->hasPages())
        <div class="card-footer">
            {{ $labRequests->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Delete Form -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
function confirmDelete(deleteUrl, requestNumber) {
    if (confirm(`Are you sure you want to delete lab request ${requestNumber}? This action cannot be undone.`)) {
        const form = document.getElementById('delete-form');
        form.action = deleteUrl;
        form.submit();
    }
}

function bulkDelete() {
    const selectedIds = [];
    document.querySelectorAll('.row-checkbox:checked').forEach(checkbox => {
        selectedIds.push(checkbox.value);
    });
    
    if (selectedIds.length === 0) {
        alert('Please select at least one lab request to delete.');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${selectedIds.length} selected lab request(s)? This action cannot be undone.`)) {
        // Create a form for bulk delete
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("lab.bulk-delete") }}';
        
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
}

// Handle select all checkbox
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkDeleteButton();
        });
    }
    
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkDeleteButton();
            
            // Update select all checkbox
            if (selectAllCheckbox) {
                const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
                selectAllCheckbox.checked = checkedCount === rowCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
            }
        });
    });
    
    function updateBulkDeleteButton() {
        const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
        if (bulkDeleteBtn) {
            bulkDeleteBtn.disabled = checkedCount === 0;
            if (checkedCount > 0) {
                bulkDeleteBtn.textContent = `Delete Selected (${checkedCount})`;
            } else {
                bulkDeleteBtn.innerHTML = '<i class="bi bi-trash"></i> Delete Selected';
            }
        }
    }

    // Load payment tracking data
    @can('process_payments')
    loadLabPaymentData();
    @endcan
});

@can('process_payments')
async function loadLabPaymentData() {
    try {
        // Load pending payments - use the correct endpoint
        const pendingResponse = await fetch('{{ route("cashier.pending-payments") }}');
        const pendingResult = await pendingResponse.json();
        
        const pendingContainer = document.getElementById('pending-lab-payments');
        if (!pendingResult.success || !pendingResult.patients_with_charges || pendingResult.patients_with_charges.length === 0) {
            pendingContainer.innerHTML = '<p class="text-success">No pending lab payments</p>';
        } else {
            // Filter for lab-related charges
            const labCharges = [];
            pendingResult.patients_with_charges.forEach(patient => {
                patient.charges.forEach(charge => {
                    if (charge.module === 'lab' || charge.description?.toLowerCase().includes('lab')) {
                        labCharges.push({
                            description: charge.description || 'Lab Service',
                            amount: charge.amount,
                            patient_name: patient.patient.name,
                            date: charge.created_at || charge.date
                        });
                    }
                });
            });
            
            if (labCharges.length === 0) {
                pendingContainer.innerHTML = '<p class="text-success">No pending lab payments</p>';
            } else {
                let html = '<ul class="list-group list-group-flush">';
                labCharges.slice(0, 5).forEach(payment => {
                    html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${payment.description}</strong><br>
                                <small class="text-muted">${payment.patient_name}</small>
                            </div>
                            <span class="badge bg-warning">GH₵ ${parseFloat(payment.amount).toFixed(2)}</span>
                        </li>
                    `;
                });
                html += '</ul>';
                pendingContainer.innerHTML = html;
            }
        }

        // Load recent payments - show link to cashier dashboard for full payment history
        const recentContainer = document.getElementById('recent-lab-payments');
        recentContainer.innerHTML = `
            <p class="text-muted mb-2">
                <a href="{{ route('cashier.index') }}" class="text-primary text-decoration-none">
                    <i class="bi bi-arrow-right-circle"></i> View Recent Payments
                </a>
            </p>
            <small class="text-muted">All payment history is available in the Cashier Dashboard.</small>
        `;
    } catch (error) {
        console.error('Error loading lab payment data:', error);
        // Show user-friendly message instead of error
        document.getElementById('pending-lab-payments').innerHTML = '<p class="text-muted">No pending lab payments</p>';
        document.getElementById('recent-lab-payments').innerHTML = '<p class="text-muted">No recent lab payments</p>';
    }
}
@endcan
</script>
@endpush
