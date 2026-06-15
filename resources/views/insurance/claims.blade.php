@extends('layouts.app')

@section('title', 'Insurance Claims')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Insurance Claims</h1>
            <p class="text-secondary mb-0">Process and manage insurance claims</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newClaimModal">
            <i class="bi bi-plus-circle"></i> New Claim
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3>{{ $statistics['total'] }}</h3>
                    <small>Total Claims</small>
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

    <!-- Financial Summary -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="text-success">₵{{ number_format($statistics['total_amount'], 2) }}</h4>
                    <small class="text-muted">Total Claim Amount</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="text-info">₵{{ number_format($statistics['covered_amount'], 2) }}</h4>
                    <small class="text-muted">Total Covered Amount</small>
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
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Provider</label>
                    <select class="form-select" id="providerFilter">
                        <option value="">All Providers</option>
                        @foreach(\App\Models\InsuranceProvider::where('is_active', true)->get() as $provider)
                            <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                        @endforeach
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
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by patient name, claim ID, or policy number...">
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

    <!-- Claims Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Insurance Claims</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="claimsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Claim ID</th>
                            <th>Patient</th>
                            <th>Provider</th>
                            <th>Total Amount</th>
                            <th>Covered</th>
                            <th>Co-Pay</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($claims as $claim)
                        <tr data-status="{{ $claim->status }}" data-provider="{{ $claim->policy->insuranceProvider->id ?? '' }}">
                            <td><strong>#{{ $claim->id }}</strong></td>
                            <td>
                                <div>
                                    <strong>{{ $claim->patient->full_name }}</strong>
                                    <br><small class="text-muted">{{ $claim->patient->patient_number }}</small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $claim->policy->insuranceProvider->name ?? 'N/A' }}</strong>
                                    <br><small class="text-muted">{{ $claim->policy->policy_number ?? 'N/A' }}</small>
                                </div>
                            </td>
                            <td><strong>₵{{ number_format($claim->total_amount, 2) }}</strong></td>
                            <td class="text-success">₵{{ number_format($claim->covered_amount, 2) }}</td>
                            <td class="text-warning">₵{{ number_format($claim->co_pay_amount, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $claim->status === 'approved' ? 'success' : ($claim->status === 'pending' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($claim->status) }}
                                </span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($claim->submitted_date)->format('M d, Y') }}</td>
                            <td class="position-static">
                                <div class="dropdown position-static">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li><a class="dropdown-item" href="#" onclick="viewClaim({{ $claim->id }})">
                                            <i class="bi bi-eye"></i> View Details
                                        </a></li>
                                        @if($claim->status === 'pending')
                                        <li><a class="dropdown-item text-success" href="#" onclick="approveClaim({{ $claim->id }})">
                                            <i class="bi bi-check-circle"></i> Approve
                                        </a></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="rejectClaim({{ $claim->id }})">
                                            <i class="bi bi-x-circle"></i> Reject
                                        </a></li>
                                        @endif
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" onclick="exportClaim({{ $claim->id }})">
                                            <i class="bi bi-download"></i> Export PDF
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="resubmitClaim({{ $claim->id }})">
                                            <i class="bi bi-arrow-repeat"></i> Resubmit
                                        </a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $claims->links() }}
        </div>
    </div>
</div>

<!-- New Claim Modal -->
<div class="modal fade" id="newClaimModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Insurance Claim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('insurance.claims.store') }}" method="POST" id="claimForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Patient <span class="text-danger">*</span></label>
                                <select class="form-select" name="patient_id" required id="patientSelect" onchange="loadPatientPolicies()">
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
                                <label class="form-label">Visit (Optional)</label>
                                <select class="form-select" name="visit_id" id="visitSelect">
                                    <option value="">Select Visit</option>
                                    @foreach(\App\Models\Visit::latest()->limit(50)->get() as $visit)
                                        <option value="{{ $visit->id }}">{{ $visit->visit_number }} - {{ $visit->patient->full_name ?? 'Unknown' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Total Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="total_amount" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Claim Items <span class="text-danger">*</span></label>
                        <div id="claimItems">
                            <div class="claim-item row mb-2">
                                <div class="col-md-4">
                                    <select class="form-select" name="claim_items[0][service_type]" required>
                                        <option value="">Service Type</option>
                                        <option value="consultation">Consultation</option>
                                        <option value="laboratory">Laboratory</option>
                                        <option value="radiology">Radiology</option>
                                        <option value="pharmacy">Pharmacy</option>
                                        <option value="surgery">Surgery</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="claim_items[0][description]" placeholder="Description" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" name="claim_items[0][amount]" step="0.01" min="0" placeholder="Amount" required>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeClaimItem(this)" disabled>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addClaimItem()">
                            <i class="bi bi-plus"></i> Add Item
                        </button>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        Coverage and co-pay amounts will be calculated automatically based on the selected policy.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Claim</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let claimItemIndex = 1;

function loadPatientPolicies() {
    const patientId = document.getElementById('patientSelect').value;
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
}

function addClaimItem() {
    const claimItems = document.getElementById('claimItems');
    const newItem = document.createElement('div');
    newItem.className = 'claim-item row mb-2';
    newItem.innerHTML = `
        <div class="col-md-4">
            <select class="form-select" name="claim_items[${claimItemIndex}][service_type]" required>
                <option value="">Service Type</option>
                <option value="consultation">Consultation</option>
                <option value="laboratory">Laboratory</option>
                <option value="radiology">Radiology</option>
                <option value="pharmacy">Pharmacy</option>
                <option value="surgery">Surgery</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" name="claim_items[${claimItemIndex}][description]" placeholder="Description" required>
        </div>
        <div class="col-md-3">
            <input type="number" class="form-control" name="claim_items[${claimItemIndex}][amount]" step="0.01" min="0" placeholder="Amount" required>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeClaimItem(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    
    claimItems.appendChild(newItem);
    claimItemIndex++;
    updateRemoveButtons();
}

function removeClaimItem(button) {
    button.closest('.claim-item').remove();
    updateRemoveButtons();
}

function updateRemoveButtons() {
    const removeButtons = document.querySelectorAll('.claim-item .btn-outline-danger');
    removeButtons.forEach(button => {
        button.disabled = removeButtons.length === 1;
    });
}

function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const provider = document.getElementById('providerFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    const dateTo = document.getElementById('dateToFilter').value;
    const search = document.getElementById('searchInput').value.toLowerCase();
    
    const rows = document.querySelectorAll('#claimsTable tbody tr');
    
    rows.forEach(row => {
        let show = true;
        
        if (status && row.dataset.status !== status) show = false;
        if (provider && row.dataset.provider !== provider) show = false;
        if (search && !row.textContent.toLowerCase().includes(search)) show = false;
        
        row.style.display = show ? '' : 'none';
    });
}

function clearFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('providerFilter').value = '';
    document.getElementById('dateFromFilter').value = '';
    document.getElementById('dateToFilter').value = '';
    document.getElementById('searchInput').value = '';
    applyFilters();
}

function viewClaim(id) {
    window.location.href = '{{ route('insurance.claims') }}?highlight=' + id;
}

function approveClaim(id) {
    if (confirm('Are you sure you want to approve this claim?')) {
        fetch('{{ url('/insurance/claims') }}/' + id + '/status', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            credentials: 'same-origin',
            body: JSON.stringify({ status: 'approved' })
        }).then(r => r.json()).then(() => window.location.reload());
    }
}

function rejectClaim(id) {
    if (confirm('Are you sure you want to reject this claim?')) {
        fetch('{{ url('/insurance/claims') }}/' + id + '/status', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            credentials: 'same-origin',
            body: JSON.stringify({ status: 'rejected' })
        }).then(r => r.json()).then(() => window.location.reload());
    }
}

function exportClaim(id) {
    window.open(`/insurance/claims/${id}/export`, '_blank');
}

function resubmitClaim(id) {
    if (confirm('Are you sure you want to resubmit this claim?')) {
        // Implement resubmit claim functionality
        alert('Resubmit claim functionality coming soon!');
    }
}

// Initialize remove buttons state
document.addEventListener('DOMContentLoaded', function() {
    updateRemoveButtons();
});
</script>
@endpush
