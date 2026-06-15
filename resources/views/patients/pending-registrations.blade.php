@extends('layouts.app')

@section('title', 'Pending Patient Registrations')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2 text-white">
                <i class="bi bi-person-plus-fill me-2"></i>Pending Patient Registrations
            </h1>
            <p class="text-muted mb-0">Review and approve patient self-registrations</p>
        </div>
        <div>
            <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Patients
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-gradient-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Pending Approval</h6>
                            <h2 class="mb-0">{{ $pendingCount }}</h2>
                        </div>
                        <div>
                            <i class="bi bi-clock-history fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gradient-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Approved Today</h6>
                            <h2 class="mb-0">{{ $approvedTodayCount }}</h2>
                        </div>
                        <div>
                            <i class="bi bi-check-circle-fill fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gradient-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Rejected</h6>
                            <h2 class="mb-0">{{ $rejectedCount }}</h2>
                        </div>
                        <div>
                            <i class="bi bi-x-circle-fill fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gradient-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Total This Month</h6>
                            <h2 class="mb-0">{{ $monthlyCount }}</h2>
                        </div>
                        <div>
                            <i class="bi bi-calendar-month fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Registrations Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>Pending Registrations
            </h5>
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" 
                       class="form-control" 
                       id="searchInput" 
                       placeholder="Search registrations...">
            </div>
        </div>
        <div class="card-body">
            @if($pendingRegistrations->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h4 class="mt-3 text-muted">No Pending Registrations</h4>
                    <p class="text-muted">All patient registrations have been processed.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="registrationsTable">
                        <thead>
                            <tr>
                                <th>Patient ID</th>
                                <th>Full Name</th>
                                <th>Gender</th>
                                <th>Date of Birth</th>
                                <th>Contact</th>
                                <th>Registration Date</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingRegistrations as $patient)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">{{ $patient->patient_number }}</span>
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $patient->full_name }}</strong>
                                    </div>
                                </td>
                                <td>
                                    @if($patient->gender == 'Male')
                                        <i class="bi bi-gender-male text-info"></i> Male
                                    @else
                                        <i class="bi bi-gender-female text-danger"></i> Female
                                    @endif
                                </td>
                                <td>
                                    {{ $patient->date_of_birth ? $patient->date_of_birth->format('d M Y') : 'Not provided' }}
                                    @if($patient->age)
                                    <small class="text-muted d-block">({{ $patient->age }} years)</small>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        <i class="bi bi-telephone"></i> {{ $patient->phone }}
                                    </div>
                                    <div class="small text-muted">
                                        <i class="bi bi-envelope"></i> {{ $patient->email }}
                                    </div>
                                </td>
                                <td>
                                    {{ $patient->created_at->format('d M Y') }}
                                    <small class="text-muted d-block">{{ $patient->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    @if($patient->account_status == 'pending')
                                        <span class="badge bg-warning">
                                            <i class="bi bi-clock-history me-1"></i>Pending
                                        </span>
                                    @elseif($patient->account_status == 'active')
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    @elseif($patient->account_status == 'rejected')
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle me-1"></i>Rejected
                                        </span>
                                    @elseif($patient->account_status == 'suspended')
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-pause-circle me-1"></i>Suspended
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" 
                                            class="btn btn-sm btn-info me-1" 
                                            onclick="viewPatientDetails({{ $patient->id }})">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @if($patient->account_status == 'pending')
                                        <button type="button" 
                                                class="btn btn-sm btn-success me-1" 
                                                onclick="approveRegistration({{ $patient->id }}, '{{ $patient->full_name }}')">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger" 
                                                onclick="rejectRegistration({{ $patient->id }}, '{{ $patient->full_name }}')">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $pendingRegistrations->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- View Patient Details Modal -->
<div class="modal fade" id="viewPatientModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-badge me-2"></i>Patient Registration Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="patientDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Reason Modal -->
<div class="modal fade" id="rejectReasonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-x-circle me-2"></i>Reject Registration
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>You are about to reject the registration for <strong id="rejectPatientName"></strong>.</p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="rejection_reason" 
                                  name="rejection_reason" 
                                  rows="4" 
                                  required
                                  placeholder="Please provide a reason for rejecting this registration..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-2"></i>Reject Registration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Live search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#registrationsTable tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// View patient details
function viewPatientDetails(patientId) {
    const modal = new bootstrap.Modal(document.getElementById('viewPatientModal'));
    modal.show();
    
    // Fetch patient details via AJAX
    fetch(`/patients/${patientId}/details`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('patientDetailsContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Personal Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Patient Number:</th>
                                <td>${data.patient_number}</td>
                            </tr>
                            <tr>
                                <th>Full Name:</th>
                                <td>${data.full_name}</td>
                            </tr>
                            <tr>
                                <th>Gender:</th>
                                <td>${data.gender}</td>
                            </tr>
                            <tr>
                                <th>Date of Birth:</th>
                                <td>${data.date_of_birth} (${data.age} years)</td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td>${data.phone || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>${data.email || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Address:</th>
                                <td>${data.address || 'N/A'}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Additional Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">NHIS Number:</th>
                                <td>${data.nhis_number || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Ghana Card:</th>
                                <td>${data.ghana_card_number || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Emergency Contact:</th>
                                <td>${data.emergency_contact_name || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Emergency Phone:</th>
                                <td>${data.emergency_contact_phone || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Relationship:</th>
                                <td>${data.emergency_contact_relationship || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Registration Date:</th>
                                <td>${data.created_at}</td>
                            </tr>
                            <tr>
                                <th>Account Status:</th>
                                <td><span class="badge bg-${data.account_status == 'pending' ? 'warning' : data.account_status == 'active' ? 'success' : 'danger'}">${data.account_status}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            document.getElementById('patientDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Error loading patient details. Please try again.
                </div>
            `;
        });
}

// Approve registration
function approveRegistration(patientId, patientName) {
    if (confirm(`Are you sure you want to APPROVE the registration for ${patientName}?\n\nThe patient will receive an email and SMS notification.`)) {
        // Submit approval via AJAX
        fetch(`/patients/${patientId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showAlert('success', data.message);
                // Reload page after 1.5 seconds
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('danger', data.message || 'Failed to approve registration.');
            }
        })
        .catch(error => {
            showAlert('danger', 'An error occurred. Please try again.');
        });
    }
}

// Reject registration
function rejectRegistration(patientId, patientName) {
    document.getElementById('rejectPatientName').textContent = patientName;
    document.getElementById('rejectForm').action = `/patients/${patientId}/reject`;
    
    const modal = new bootstrap.Modal(document.getElementById('rejectReasonModal'));
    modal.show();
}

// Handle reject form submission
document.getElementById('rejectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const url = this.action;
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide modal
            bootstrap.Modal.getInstance(document.getElementById('rejectReasonModal')).hide();
            // Show success message
            showAlert('success', data.message);
            // Reload page after 1.5 seconds
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('danger', data.message || 'Failed to reject registration.');
        }
    })
    .catch(error => {
        showAlert('danger', 'An error occurred. Please try again.');
    });
});

// Helper function to show alerts
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}-fill me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}
</script>
@endpush
