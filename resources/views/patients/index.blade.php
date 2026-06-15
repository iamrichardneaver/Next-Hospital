@extends('layouts.app')

@section('title', 'Patients')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Patients</h1>
            <p class="text-secondary mb-0">Manage patient records and information</p>
        </div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('patients.export'),
                'permission' => 'view_patients',
            ])
            @can('create_patients')
            <a href="{{ route('patients.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Register New Patient
            </a>
            @endcan
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-label">Total Patients</div>
                <div class="stat-value">{{ number_format($statistics['total']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-gender-male"></i>
                </div>
                <div class="stat-label">Male Patients</div>
                <div class="stat-value">{{ number_format($statistics['male']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-gender-female"></i>
                </div>
                <div class="stat-label">Female Patients</div>
                <div class="stat-value">{{ number_format($statistics['female']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div class="stat-label">With NHIS</div>
                <div class="stat-value">{{ number_format($statistics['with_nhis']) }}</div>
            </div>
        </div>
    </div>
    
    <!-- Patients List Card -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 text-dark">All Patients</h5>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-2">
                        <!-- Live Search (AJAX) - Quick way to find returning patients for check-in -->
                        <div class="input-group flex-grow-1">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   id="patient-search" 
                                   placeholder="Search by name, phone, patient ID, NHIS... (then click Check in)">
                        </div>
                        @can('view_visits')
                        <a href="{{ route('visits.create') }}" class="btn btn-success">
                            <i class="bi bi-box-arrow-in-right"></i> Check in patient
                        </a>
                        @endcan
                        @can('delete_patients')
                        <button type="button" 
                                class="btn btn-outline-danger" 
                                onclick="bulkDeletePatients()" 
                                id="bulk-delete-btn" 
                                disabled>
                            <i class="bi bi-trash"></i> Delete Selected
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="patients-table">
                    <thead class="table-light">
                        <tr>
                            @can('delete_patients')
                            <th width="50">
                                <input type="checkbox" id="select-all-patients" class="form-check-input">
                            </th>
                            @endcan
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Phone</th>
                            <th>NHIS Number</th>
                            <th>Branch</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($patients as $patient)
                        <tr>
                            @can('delete_patients')
                            <td>
                                <input type="checkbox" class="form-check-input patient-checkbox" value="{{ $patient->id }}">
                            </td>
                            @endcan
                            <td><strong class="text-primary">{{ $patient->patient_number }}</strong></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar bg-primary me-2" style="width: 35px; height: 35px; font-size: 14px;">
                                        {{ strtoupper(substr($patient->first_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $patient->first_name }} {{ $patient->last_name }}</div>
                                        @if($patient->other_names)
                                        <small class="text-muted">{{ $patient->other_names }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <i class="bi bi-gender-{{ strtolower($patient->gender) }} me-1"></i>
                                {{ $patient->gender }}
                            </td>
                            <td>{{ \Carbon\Carbon::parse($patient->date_of_birth)->age }} years</td>
                            <td>{{ $patient->phone ?? '-' }}</td>
                            <td>
                                @if($patient->nhis_number)
                                    <span class="badge bg-success">{{ $patient->nhis_number }}</span>
                                @else
                                    <span class="badge bg-secondary">No NHIS</span>
                                @endif
                            </td>
                            <td>{{ $patient->branch->name ?? '-' }}</td>
                            <td>
                                @if($patient->registration_source)
                                <span class="badge bg-{{ $patient->registration_source === 'mobile_app' ? 'info' : 'secondary' }} mb-1" title="Registration Source">
                                    <i class="bi bi-{{ $patient->registration_source === 'mobile_app' ? 'phone' : 'globe' }}"></i>
                                    {{ $patient->registration_source === 'mobile_app' ? 'App' : 'Web' }}
                                </span>
                                <br>
                                @endif
                                <div class="btn-group btn-group-sm">
                                    @can('view_visits')
                                    <a href="{{ route('visits.create', ['patient_id' => $patient->id]) }}" 
                                       class="btn btn-sm btn-success" 
                                       title="Check in / Start visit">
                                        <i class="bi bi-box-arrow-in-right"></i> Check in
                                    </a>
                                    @endcan
                                    <a href="{{ route('patients.show', $patient) }}" 
                                       class="btn btn-sm btn-info" 
                                       title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    @can('edit_patients')
                                    <a href="{{ route('patients.edit', $patient) }}" 
                                       class="btn btn-sm btn-warning" 
                                       title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    
                                    @can('delete_patients')
                                    <form action="{{ route('patients.destroy', $patient) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this patient?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-sm btn-danger" 
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->can('delete_patients') ? '9' : '8' }}" class="text-center py-5">
                                <i class="bi bi-inbox text-secondary" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="text-secondary mt-2 mb-0">No patients found</p>
                                @can('create_patients')
                                <a href="{{ route('patients.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-person-plus"></i> Register First Patient
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($patients->hasPages())
        <div class="card-footer">
            {{ $patients->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// AJAX Live Search
const patientSearch = document.getElementById('patient-search');
const patientsTable = document.getElementById('patients-table').querySelector('tbody');

patientSearch.addEventListener('input', nexthospital.debounce(function(e) {
    const query = e.target.value;
    
    if (query.length < 2) {
        // If search is cleared, reload page to show all patients
        if (query.length === 0) {
            window.location.reload();
        }
        return;
    }
    
    // Show loader
    nexthospital.showLoader();
    
    // Call web search endpoint
    axios.get('/patients/search', {
        params: { q: query }
    })
    .then(response => {
        nexthospital.hideLoader();
        updatePatientsTable(response.data.data);
    })
    .catch(error => {
        nexthospital.hideLoader();
        console.error('Search error:', error);
        nexthospital.showToast('Search failed. Please try again.', 'danger');
    });
}, 300));

// Update table with search results
function updatePatientsTable(patients) {
    const canDelete = {{ auth()->user()->can('delete_patients') ? 'true' : 'false' }};
    const colSpan = canDelete ? 9 : 8;
    
    if (patients.length === 0) {
        patientsTable.innerHTML = `
            <tr>
                <td colspan="${colSpan}" class="text-center py-5">
                    <i class="bi bi-search text-secondary" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="text-secondary mt-2 mb-0">No patients found matching your search</p>
                </td>
            </tr>
        `;
        return;
    }
    
    patientsTable.innerHTML = patients.map(patient => {
        const age = calculateAge(patient.date_of_birth);
        const nhisBadge = patient.nhis_number 
            ? `<span class="badge bg-success">${patient.nhis_number}</span>`
            : `<span class="badge bg-secondary">No NHIS</span>`;
        
        const checkboxCell = canDelete 
            ? `<td><input type="checkbox" class="form-check-input patient-checkbox" value="${patient.id}"></td>`
            : '';
        
        return `
            <tr>
                ${checkboxCell}
                <td><strong class="text-primary">${patient.patient_number}</strong></td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="user-avatar bg-primary me-2" style="width: 35px; height: 35px; font-size: 14px;">
                            ${patient.first_name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div class="fw-bold">${patient.first_name} ${patient.last_name}</div>
                            ${patient.other_names ? `<small class="text-muted">${patient.other_names}</small>` : ''}
                        </div>
                    </div>
                </td>
                <td><i class="bi bi-gender-${patient.gender.toLowerCase()} me-1"></i> ${patient.gender}</td>
                <td>${age} years</td>
                <td>${patient.phone || '-'}</td>
                <td>${nhisBadge}</td>
                <td>${patient.branch?.name || '-'}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="/patients/${patient.id}" class="btn btn-sm btn-info" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="/patients/${patient.id}/edit" class="btn btn-sm btn-warning" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    // Re-initialize checkbox event listeners after table update
    initializeCheckboxes();
}

// Calculate age from date of birth
function calculateAge(dob) {
    const birthDate = new Date(dob);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
}

// Bulk delete functionality
function bulkDeletePatients() {
    const selectedIds = [];
    document.querySelectorAll('.patient-checkbox:checked').forEach(checkbox => {
        selectedIds.push(checkbox.value);
    });
    
    if (selectedIds.length === 0) {
        alert('Please select at least one patient to delete.');
        return;
    }
    
    const confirmMessage = selectedIds.length === 1
        ? 'Are you sure you want to delete this patient? This action cannot be undone.'
        : `Are you sure you want to delete ${selectedIds.length} selected patient(s)? This action cannot be undone.`;
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Create a form for bulk delete
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("patients.bulk-delete") }}';
    
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
function initializeCheckboxes() {
    const selectAllCheckbox = document.getElementById('select-all-patients');
    const patientCheckboxes = document.querySelectorAll('.patient-checkbox');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            patientCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkDeleteButton();
        });
    }
    
    patientCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkDeleteButton();
            
            // Update select all checkbox
            if (selectAllCheckbox) {
                const checkedCount = document.querySelectorAll('.patient-checkbox:checked').length;
                selectAllCheckbox.checked = checkedCount === patientCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < patientCheckboxes.length;
            }
        });
    });
    
    function updateBulkDeleteButton() {
        if (!bulkDeleteBtn) return;
        
        const checkedCount = document.querySelectorAll('.patient-checkbox:checked').length;
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
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeCheckboxes();
});
</script>
@endpush
@endsection
