@extends('layouts.app')

@section('title', 'Pre-Authorizations')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Pre-Authorizations</h1>
            <p class="text-secondary mb-0">Manage insurance pre-authorization requests</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPreAuthModal">
            <i class="bi bi-plus-circle"></i> New Pre-Authorization
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3>{{ $statistics['total'] }}</h3>
                    <small>Total Requests</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3>{{ $statistics['pending'] }}</h3>
                    <small>Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3>{{ $statistics['approved'] }}</h3>
                    <small>Approved</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3>{{ $statistics['rejected'] }}</h3>
                    <small>Rejected</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Urgency</label>
                    <select class="form-select" id="urgencyFilter">
                        <option value="">All Urgencies</option>
                        <option value="routine">Routine</option>
                        <option value="urgent">Urgent</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" id="dateFromFilter">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" id="dateToFilter">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by patient name, policy number, or service...">
                </div>
                <div class="col-md-6">
                    <button class="btn btn-primary" onclick="applyFilters()">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <button class="btn btn-outline-secondary" onclick="clearFilters()">
                        <i class="bi bi-x-circle"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pre-Authorizations Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Pre-Authorization Requests</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="preAuthTable">
                    <thead class="table-light">
                        <tr>
                            <th>Request #</th>
                            <th>Patient</th>
                            <th>Provider</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Urgency</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Expiry Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preAuthorizations as $preAuth)
                        <tr data-status="{{ $preAuth->status }}" data-urgency="{{ $preAuth->urgency }}">
                            <td><strong>#{{ $preAuth->id }}</strong></td>
                            <td>
                                <div>
                                    <strong>{{ $preAuth->patient->full_name }}</strong>
                                    <br><small class="text-muted">{{ $preAuth->patient->patient_number }}</small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $preAuth->policy->insuranceProvider->name }}</strong>
                                    <br><small class="text-muted">{{ $preAuth->policy->policy_number }}</small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $preAuth->service_type }}</strong>
                                    @if($preAuth->service_code)
                                        <br><small class="text-muted">{{ $preAuth->service_code }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>₵{{ number_format($preAuth->requested_amount, 2) }}</strong>
                                    @if($preAuth->approved_amount)
                                        <br><small class="text-success">Approved: ₵{{ number_format($preAuth->approved_amount, 2) }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $preAuth->urgency === 'emergency' ? 'danger' : ($preAuth->urgency === 'urgent' ? 'warning' : 'info') }}">
                                    {{ ucfirst($preAuth->urgency) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $isExpired = $preAuth->expiry_date && \Carbon\Carbon::parse($preAuth->expiry_date)->isPast();
                                    $statusClass = $preAuth->status === 'approved' ? 'success' : ($preAuth->status === 'rejected' ? 'danger' : ($isExpired ? 'secondary' : 'warning'));
                                @endphp
                                <span class="badge bg-{{ $statusClass }}">
                                    {{ $isExpired ? 'Expired' : ucfirst($preAuth->status) }}
                                </span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($preAuth->request_date)->format('M d, Y') }}</td>
                            <td>
                                @if($preAuth->expiry_date)
                                    {{ \Carbon\Carbon::parse($preAuth->expiry_date)->format('M d, Y') }}
                                    @if($isExpired)
                                        <br><small class="text-danger">Expired</small>
                                    @endif
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </td>
                            <td class="position-static">
                                <div class="dropdown position-static">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        @can('view_pre_authorizations')
                                        <li><a class="dropdown-item" href="#" onclick="viewPreAuth({{ $preAuth->id }})">
                                            <i class="bi bi-eye"></i> View Details
                                        </a></li>
                                        @endcan
                                        @if($preAuth->status === 'pending' && !$isExpired)
                                        @can('edit_pre_authorizations')
                                        <li><a class="dropdown-item text-success" href="#" onclick="approvePreAuth({{ $preAuth->id }})">
                                            <i class="bi bi-check-circle"></i> Approve
                                        </a></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="rejectPreAuth({{ $preAuth->id }})">
                                            <i class="bi bi-x-circle"></i> Reject
                                        </a></li>
                                        @endcan
                                        @endif
                                        @if($preAuth->status !== 'pending')
                                        @can('view_pre_authorizations')
                                        <li><a class="dropdown-item" href="#" onclick="viewApprovalDetails({{ $preAuth->id }})">
                                            <i class="bi bi-info-circle"></i> Approval Details
                                        </a></li>
                                        @endcan
                                        @endif
                                        @can('manage_insurance_reports')
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" onclick="exportPreAuth({{ $preAuth->id }})">
                                            <i class="bi bi-download"></i> Export PDF
                                        </a></li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $preAuthorizations->links() }}
        </div>
    </div>
</div>

<!-- Add Pre-Authorization Modal -->
<div class="modal fade" id="addPreAuthModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Pre-Authorization Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('insurance.pre-authorizations.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Patient <span class="text-danger">*</span></label>
                                <select class="form-select" name="patient_id" required id="patientSelect">
                                    <option value="">Select Patient</option>
                                    @foreach(\App\Models\Patient::latest()->get() as $patient)
                                        <option value="{{ $patient->id }}">{{ $patient->full_name }} ({{ $patient->patient_number }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Insurance Policy <span class="text-danger">*</span></label>
                                <select class="form-select" name="policy_id" required id="policySelect">
                                    <option value="">Select Policy</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Service Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="service_type" required>
                                    <option value="">Select Service Type</option>
                                    <option value="consultation">Consultation</option>
                                    <option value="laboratory">Laboratory Test</option>
                                    <option value="radiology">Radiology/Imaging</option>
                                    <option value="surgery">Surgery</option>
                                    <option value="emergency">Emergency Care</option>
                                    <option value="pharmacy">Pharmacy</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Service Code</label>
                                <input type="text" class="form-control" name="service_code" placeholder="e.g., CPT code">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Requested Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="requested_amount" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Urgency <span class="text-danger">*</span></label>
                                <select class="form-select" name="urgency" required>
                                    <option value="">Select Urgency</option>
                                    <option value="routine">Routine</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Service Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="description" rows="2" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Clinical Justification <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="clinical_justification" rows="3" required placeholder="Explain why this service is medically necessary..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalTitle">Approve Pre-Authorization</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approvalForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Approved Amount</label>
                        <input type="number" class="form-control" name="approved_amount" step="0.01" min="0" id="approvedAmount">
                        <small class="text-muted">Leave empty to approve full requested amount</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" class="form-control" name="expiry_date" id="expiryDate">
                        <small class="text-muted">Leave empty for default 30 days</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Approval Notes</label>
                        <textarea class="form-control" name="approval_notes" rows="3" id="approvalNotes"></textarea>
                    </div>
                    <input type="hidden" name="status" id="approvalStatus">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="approvalSubmitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Load patient policies when patient is selected
document.getElementById('patientSelect').addEventListener('change', function() {
    const patientId = this.value;
    const policySelect = document.getElementById('policySelect');
    
    policySelect.innerHTML = '<option value="">Loading...</option>';
    
    if (patientId) {
        fetch(`/insurance/patients/${patientId}/policies`)
            .then(response => response.json())
            .then(data => {
                policySelect.innerHTML = '<option value="">Select Policy</option>';
                data.policies.forEach(policy => {
                    const option = document.createElement('option');
                    option.value = policy.id;
                    option.textContent = `${policy.policy_number} - ${policy.insurance_provider.name}`;
                    policySelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                policySelect.innerHTML = '<option value="">Error loading policies</option>';
            });
    } else {
        policySelect.innerHTML = '<option value="">Select Policy</option>';
    }
});

function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const urgency = document.getElementById('urgencyFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    const dateTo = document.getElementById('dateToFilter').value;
    const search = document.getElementById('searchInput').value.toLowerCase();
    
    const rows = document.querySelectorAll('#preAuthTable tbody tr');
    
    rows.forEach(row => {
        let show = true;
        
        if (status && row.dataset.status !== status) show = false;
        if (urgency && row.dataset.urgency !== urgency) show = false;
        if (search && !row.textContent.toLowerCase().includes(search)) show = false;
        
        row.style.display = show ? '' : 'none';
    });
}

function clearFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('urgencyFilter').value = '';
    document.getElementById('dateFromFilter').value = '';
    document.getElementById('dateToFilter').value = '';
    document.getElementById('searchInput').value = '';
    applyFilters();
}

function viewPreAuth(id) {
    alert('View pre-authorization functionality coming soon!');
}

function approvePreAuth(id) {
    document.getElementById('approvalModalTitle').textContent = 'Approve Pre-Authorization';
    document.getElementById('approvalForm').action = `/insurance/pre-authorizations/${id}`;
    document.getElementById('approvalStatus').value = 'approved';
    document.getElementById('approvalSubmitBtn').className = 'btn btn-success';
    document.getElementById('approvalSubmitBtn').textContent = 'Approve';
    
    new bootstrap.Modal(document.getElementById('approvalModal')).show();
}

function rejectPreAuth(id) {
    document.getElementById('approvalModalTitle').textContent = 'Reject Pre-Authorization';
    document.getElementById('approvalForm').action = `/insurance/pre-authorizations/${id}`;
    document.getElementById('approvalStatus').value = 'rejected';
    document.getElementById('approvalSubmitBtn').className = 'btn btn-danger';
    document.getElementById('approvalSubmitBtn').textContent = 'Reject';
    
    // Hide amount field for rejection
    document.querySelector('input[name="approved_amount"]').closest('.mb-3').style.display = 'none';
    
    new bootstrap.Modal(document.getElementById('approvalModal')).show();
}

function viewApprovalDetails(id) {
    alert('View approval details functionality coming soon!');
}

function exportPreAuth(id) {
    window.open(`/insurance/pre-authorizations/${id}/export`, '_blank');
}

// Set default expiry date to 30 days from now
document.addEventListener('DOMContentLoaded', function() {
    const expiryDateInput = document.getElementById('expiryDate');
    if (expiryDateInput) {
        const defaultDate = new Date();
        defaultDate.setDate(defaultDate.getDate() + 30);
        expiryDateInput.value = defaultDate.toISOString().split('T')[0];
    }
});
</script>
@endpush
