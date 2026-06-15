@extends('layouts.app')

@section('title', 'Register New Patient')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Register New Patient</h1>
            <p class="text-secondary mb-0">Enter patient information to create a new record</p>
        </div>
        <div>
            <a href="{{ route('patients.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Patients
            </a>
        </div>
    </div>
    
    <!-- Registration Form -->
    <div class="row">
        <div class="col-lg-10 col-xl-8 mx-auto">
            <form action="{{ route('patients.store') }}" method="POST" enctype="multipart/form-data" id="patient-create-form">
                @csrf
                <input type="hidden" name="confirmed_no_duplicate" id="confirmed_no_duplicate" value="0">
                
                <!-- Personal Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark">
                            <i class="bi bi-person-circle me-2"></i>Personal Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('first_name') is-invalid @enderror" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="{{ old('first_name') }}" 
                                       required
                                       autocomplete="off">
                                <div id="first_name_duplicate_check" class="mt-1" style="display: none;"></div>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="other_names" class="form-label">Other Names</label>
                                <input type="text" 
                                       class="form-control @error('other_names') is-invalid @enderror" 
                                       id="other_names" 
                                       name="other_names" 
                                       value="{{ old('other_names') }}">
                                @error('other_names')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('last_name') is-invalid @enderror" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="{{ old('last_name') }}" 
                                       required
                                       autocomplete="off">
                                <div id="last_name_duplicate_check" class="mt-1" style="display: none;"></div>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                <select class="form-select @error('gender') is-invalid @enderror" 
                                        id="gender" 
                                        name="gender" 
                                        required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="age" class="form-label">Age (Years)</label>
                                <input type="number" 
                                       class="form-control @error('age') is-invalid @enderror" 
                                       id="age" 
                                       name="age" 
                                       value="{{ old('age') }}" 
                                       min="0" 
                                       max="150"
                                       placeholder="Enter age in years">
                                <small class="text-muted">Or provide date of birth below</small>
                                @error('age')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" 
                                       class="form-control @error('date_of_birth') is-invalid @enderror" 
                                       id="date_of_birth" 
                                       name="date_of_birth" 
                                       value="{{ old('date_of_birth') }}" 
                                       max="{{ date('Y-m-d') }}">
                                <small class="text-muted">Optional - provide age or date of birth</small>
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                <select class="form-select @error('branch_id') is-invalid @enderror" 
                                        id="branch_id" 
                                        name="branch_id" 
                                        required>
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark">
                            <i class="bi bi-telephone me-2"></i>Contact Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" 
                                       name="phone" 
                                       value="{{ old('phone') }}" 
                                       placeholder="+233 XX XXX XXXX">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       placeholder="patient@example.com">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" 
                                      name="address" 
                                      rows="2" 
                                      placeholder="Enter residential address">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <!-- Insurance & Identification -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark">
                            <i class="bi bi-shield-check me-2"></i>Insurance & Identification
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nhis_number" class="form-label">NHIS Number</label>
                                <input type="text" 
                                       class="form-control @error('nhis_number') is-invalid @enderror" 
                                       id="nhis_number" 
                                       name="nhis_number" 
                                       value="{{ old('nhis_number') }}" 
                                       placeholder="Enter NHIS number">
                                @error('nhis_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="ghana_card_number" class="form-label">Ghana Card Number</label>
                                <input type="text" 
                                       class="form-control @error('ghana_card_number') is-invalid @enderror" 
                                       id="ghana_card_number" 
                                       name="ghana_card_number" 
                                       value="{{ old('ghana_card_number') }}" 
                                       placeholder="GHA-XXXXXXXXX-X">
                                @error('ghana_card_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Emergency Contact -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark">
                            <i class="bi bi-exclamation-triangle me-2"></i>Emergency Contact
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="emergency_contact_name" class="form-label">Contact Name</label>
                                <input type="text" 
                                       class="form-control @error('emergency_contact_name') is-invalid @enderror" 
                                       id="emergency_contact_name" 
                                       name="emergency_contact_name" 
                                       value="{{ old('emergency_contact_name') }}" 
                                       placeholder="Full name">
                                @error('emergency_contact_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="emergency_contact_phone" class="form-label">Contact Phone</label>
                                <input type="tel" 
                                       class="form-control @error('emergency_contact_phone') is-invalid @enderror" 
                                       id="emergency_contact_phone" 
                                       name="emergency_contact_phone" 
                                       value="{{ old('emergency_contact_phone') }}" 
                                       placeholder="+233 XX XXX XXXX">
                                @error('emergency_contact_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                                <input type="text" 
                                       class="form-control @error('emergency_contact_relationship') is-invalid @enderror" 
                                       id="emergency_contact_relationship" 
                                       name="emergency_contact_relationship" 
                                       value="{{ old('emergency_contact_relationship') }}" 
                                       placeholder="e.g., Father, Mother, Spouse">
                                @error('emergency_contact_relationship')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('patients.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                <i class="bi bi-check-circle"></i> Register Patient
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Duplicate Detection Modal -->
<div class="modal fade" id="duplicateModal" tabindex="-1" aria-labelledby="duplicateModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="duplicateModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Potential Duplicate Patient Found
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    <strong>⚠️ Warning:</strong> We found <span id="duplicate-count">0</span> patient(s) that may already exist.
                    <span id="duplicate-severity-text">Please review the records below before continuing.</span>
                </div>
                
                <div id="duplicates-list" class="list-group">
                    <!-- Duplicate records will be inserted here -->
                </div>
                
                <div class="mt-3 p-3 bg-light rounded">
                    <strong>What would you like to do?</strong>
                    <ul class="mb-0 mt-2">
                        <li>If this is an <strong>existing patient</strong>, click <strong>Use Existing Patient</strong> to check them in.</li>
                        <li>If this is a <strong>new patient</strong> (e.g. family member sharing a phone), click <strong>Create Anyway</strong>.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" id="use-existing-patient-btn" style="display: none;">
                    <i class="bi bi-person-check"></i> Use Existing Patient
                </button>
                <button type="button" class="btn btn-primary" id="confirm-new-patient-btn">
                    <i class="bi bi-check-circle"></i> Create Anyway
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    let duplicateCheckTimeout;
    let hasHighConfidenceDuplicate = false;
    let hasPotentialMatches = false;
    let duplicateData = [];
    let isSubmitting = false;
    
    const firstNameInput = document.getElementById('first_name');
    const lastNameInput = document.getElementById('last_name');
    const phoneInput = document.getElementById('phone');
    const emailInput = document.getElementById('email');
    const nhisInput = document.getElementById('nhis_number');
    const ghanaCardInput = document.getElementById('ghana_card_number');
    const dobInput = document.getElementById('date_of_birth');
    const branchInput = document.getElementById('branch_id');
    const form = document.getElementById('patient-create-form');
    const confirmedNoDuplicate = document.getElementById('confirmed_no_duplicate');
    const submitBtn = document.getElementById('submit-btn');
    const confirmNewPatientBtn = document.getElementById('confirm-new-patient-btn');
    const useExistingPatientBtn = document.getElementById('use-existing-patient-btn');
    let duplicateModal = null;
    
    const duplicateModalElement = document.getElementById('duplicateModal');
    if (duplicateModalElement && typeof bootstrap !== 'undefined') {
        duplicateModal = new bootstrap.Modal(duplicateModalElement);
    }
    
    if (!firstNameInput || !lastNameInput || !form) {
        console.warn('Patient duplicate detection: Required form elements not found');
        return;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function hasEnoughDataForCheck() {
        const firstName = firstNameInput.value.trim();
        const lastName = lastNameInput.value.trim();
        const phone = phoneInput ? phoneInput.value.trim() : '';
        const email = emailInput ? emailInput.value.trim() : '';
        const nhis = nhisInput ? nhisInput.value.trim() : '';
        const ghanaCard = ghanaCardInput ? ghanaCardInput.value.trim() : '';

        return phone.length >= 6
            || email.length >= 5
            || nhis.length >= 3
            || ghanaCard.length >= 5
            || (firstName.length >= 2 && lastName.length >= 2);
    }

    function buildDuplicateParams() {
        const params = new URLSearchParams({
            first_name: firstNameInput.value.trim(),
            last_name: lastNameInput.value.trim(),
        });

        if (phoneInput && phoneInput.value.trim()) params.set('phone', phoneInput.value.trim());
        if (emailInput && emailInput.value.trim()) params.set('email', emailInput.value.trim());
        if (nhisInput && nhisInput.value.trim()) params.set('nhis_number', nhisInput.value.trim());
        if (ghanaCardInput && ghanaCardInput.value.trim()) params.set('ghana_card_number', ghanaCardInput.value.trim());
        if (dobInput && dobInput.value) params.set('date_of_birth', dobInput.value);
        if (branchInput && branchInput.value) params.set('branch_id', branchInput.value);

        return params;
    }

    function checkForDuplicates() {
        if (!hasEnoughDataForCheck()) {
            hideDuplicateIndicators();
            hasHighConfidenceDuplicate = false;
            hasPotentialMatches = false;
            duplicateData = [];
            return;
        }

        showLoadingIndicator();

        if (duplicateCheckTimeout) {
            clearTimeout(duplicateCheckTimeout);
        }

        duplicateCheckTimeout = setTimeout(() => {
            fetch('{{ route("patients.check-duplicates") }}?' + buildDuplicateParams().toString(), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.has_potential_matches && data.duplicates.length > 0) {
                    hasHighConfidenceDuplicate = !!data.has_high_confidence_match;
                    hasPotentialMatches = true;
                    duplicateData = data.duplicates;
                    showDuplicateWarning(data.duplicates, data.has_high_confidence_match);
                } else {
                    hasHighConfidenceDuplicate = false;
                    hasPotentialMatches = false;
                    duplicateData = [];
                    hideDuplicateIndicators();
                }
            })
            .catch(error => {
                console.error('Error checking for duplicates:', error);
                hideDuplicateIndicators();
            });
        }, 800);
    }
    
    // Function to show loading indicator
    function showLoadingIndicator() {
        const firstNameCheck = document.getElementById('first_name_duplicate_check');
        const lastNameCheck = document.getElementById('last_name_duplicate_check');
        
        firstNameCheck.innerHTML = '<small class="text-info"><i class="bi bi-hourglass-split"></i> Checking for duplicates...</small>';
        firstNameCheck.style.display = 'block';
        lastNameCheck.innerHTML = '<small class="text-info"><i class="bi bi-hourglass-split"></i> Checking for duplicates...</small>';
        lastNameCheck.style.display = 'block';
    }
    
    function showDuplicateWarning(duplicates, isHighConfidence) {
        const firstNameCheck = document.getElementById('first_name_duplicate_check');
        const lastNameCheck = document.getElementById('last_name_duplicate_check');
        const topMatch = duplicates[0];
        const reasonText = topMatch && (topMatch.match_reason || (topMatch.match_reasons || []).join(', '))
            ? ` Matched on: ${topMatch.match_reason || (topMatch.match_reasons || []).join(', ')}.`
            : '';

        const alertClass = isHighConfidence ? 'text-danger' : 'text-warning';
        const icon = isHighConfidence ? 'bi-exclamation-octagon-fill' : 'bi-exclamation-triangle-fill';
        const label = isHighConfidence ? 'Possible duplicate' : 'Similar name found';

        const warningHtml = `<small class="${alertClass}">
            <i class="bi ${icon}"></i>
            ${label}: ${duplicates.length} record(s).${escapeHtml(reasonText)}
            <a href="#" class="fw-bold duplicate-review-link">Review</a>
        </small>`;

        firstNameCheck.innerHTML = warningHtml;
        firstNameCheck.style.display = 'block';
        lastNameCheck.innerHTML = warningHtml;
        lastNameCheck.style.display = 'block';
    }
    
    // Function to hide duplicate indicators
    function hideDuplicateIndicators() {
        const firstNameCheck = document.getElementById('first_name_duplicate_check');
        const lastNameCheck = document.getElementById('last_name_duplicate_check');
        firstNameCheck.style.display = 'none';
        lastNameCheck.style.display = 'none';
    }
    
    function showDuplicateModal() {
        const duplicatesList = document.getElementById('duplicates-list');
        const duplicateCount = document.getElementById('duplicate-count');
        const severityText = document.getElementById('duplicate-severity-text');

        duplicateCount.textContent = duplicateData.length;
        severityText.textContent = hasHighConfidenceDuplicate
            ? 'A strong match was found (phone, email, NHIS, Ghana Card, or exact name + date of birth). Confirm before creating a new record.'
            : 'Similar names were found at your branch. You may still register if this is a different person.';

        duplicatesList.innerHTML = '';

        duplicateData.forEach((duplicate) => {
            const duplicateCard = document.createElement('div');
            duplicateCard.className = 'list-group-item mb-2 border rounded';

            const fullName = escapeHtml(duplicate.full_name || 'N/A');
            const patientNumber = duplicate.patient_number ? escapeHtml(duplicate.patient_number) : '';
            const gender = escapeHtml(duplicate.gender || 'N/A');
            const dateOfBirth = duplicate.date_of_birth ? escapeHtml(duplicate.date_of_birth) : '';
            const age = duplicate.age ? escapeHtml(String(duplicate.age)) : 'N/A';
            const phone = duplicate.phone ? escapeHtml(duplicate.phone) : '';
            const email = duplicate.email ? escapeHtml(duplicate.email) : '';
            const nhisNumber = duplicate.nhis_number ? escapeHtml(duplicate.nhis_number) : '';
            const ghanaCard = duplicate.ghana_card_number ? escapeHtml(duplicate.ghana_card_number) : '';
            const branchName = escapeHtml(duplicate.branch_name || 'N/A');
            const createdAt = duplicate.created_at ? new Date(duplicate.created_at).toLocaleDateString() : 'N/A';
            const confidence = duplicate.confidence === 'high' ? 'High confidence' : 'Similar name';
            const confidenceBadge = duplicate.confidence === 'high' ? 'bg-danger' : 'bg-warning text-dark';
            const matchReasons = duplicate.match_reasons && Array.isArray(duplicate.match_reasons)
                ? duplicate.match_reasons.map(reason => `<span class="badge bg-secondary ms-1">${escapeHtml(reason)}</span>`).join('')
                : (duplicate.match_reason ? `<span class="badge bg-secondary ms-1">${escapeHtml(duplicate.match_reason)}</span>` : '');
            const viewUrl = escapeHtml(duplicate.view_url || '#');
            const checkInUrl = escapeHtml(duplicate.check_in_url || '#');

            duplicateCard.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            <strong>${fullName}</strong>
                            ${patientNumber ? `<span class="badge bg-secondary ms-2">${patientNumber}</span>` : ''}
                        </h6>
                        <div class="small text-muted mb-2">
                            <div><strong>Gender:</strong> ${gender}</div>
                            ${dateOfBirth ? `<div><strong>Date of Birth:</strong> ${dateOfBirth} (Age: ${age})</div>` : ''}
                            ${phone ? `<div><strong>Phone:</strong> ${phone}</div>` : ''}
                            ${email ? `<div><strong>Email:</strong> ${email}</div>` : ''}
                            ${nhisNumber ? `<div><strong>NHIS:</strong> ${nhisNumber}</div>` : ''}
                            ${ghanaCard ? `<div><strong>Ghana Card:</strong> ${ghanaCard}</div>` : ''}
                            <div><strong>Branch:</strong> ${branchName}</div>
                            <div><strong>Registered:</strong> ${createdAt}</div>
                            <div class="mt-1">
                                <span class="badge ${confidenceBadge}">${confidence}</span>
                                ${matchReasons}
                            </div>
                        </div>
                    </div>
                    <div class="ms-3 d-flex flex-column gap-2">
                        <a href="${checkInUrl}" class="btn btn-sm btn-success use-existing-link" data-patient-id="${duplicate.id}">
                            <i class="bi bi-person-check"></i> Check In
                        </a>
                        <a href="${viewUrl}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </div>
                </div>
            `;
            duplicatesList.appendChild(duplicateCard);
        });

        if (useExistingPatientBtn) {
            const primaryMatch = duplicateData[0];
            useExistingPatientBtn.style.display = primaryMatch && primaryMatch.check_in_url ? 'inline-block' : 'none';
            useExistingPatientBtn.dataset.checkInUrl = primaryMatch ? (primaryMatch.check_in_url || '') : '';
        }

        if (duplicateModal) {
            duplicateModal.show();
        }
    }
    
    [firstNameInput, lastNameInput, phoneInput, emailInput, nhisInput, ghanaCardInput, dobInput, branchInput]
        .filter(Boolean)
        .forEach(input => input.addEventListener('input', checkForDuplicates));
    if (dobInput) dobInput.addEventListener('change', checkForDuplicates);
    if (branchInput) branchInput.addEventListener('change', checkForDuplicates);
    
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('duplicate-review-link')) {
            e.preventDefault();
            showDuplicateModal();
        }
    });
    
    if (form) {
        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            if (hasHighConfidenceDuplicate && duplicateData.length > 0 && confirmedNoDuplicate && confirmedNoDuplicate.value === '0') {
                e.preventDefault();
                showDuplicateModal();
                return false;
            }

            isSubmitting = true;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Registering...';
            }
        });
    }
    
    if (confirmNewPatientBtn) {
        confirmNewPatientBtn.addEventListener('click', function() {
            confirmedNoDuplicate.value = '1';
            if (duplicateModal) {
                duplicateModal.hide();
            }
            isSubmitting = true;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Registering...';
            }
            form.submit();
        });
    }

    if (useExistingPatientBtn) {
        useExistingPatientBtn.addEventListener('click', function() {
            const url = useExistingPatientBtn.dataset.checkInUrl || (duplicateData[0] && duplicateData[0].check_in_url);
            if (url) {
                window.location.href = url;
            }
        });
    }

    @if(session('duplicate_patients'))
        duplicateData = @json(session('duplicate_patients'));
        hasPotentialMatches = duplicateData.length > 0;
        hasHighConfidenceDuplicate = duplicateData.some(d => d.confidence === 'high');
        if (hasPotentialMatches) {
            showDuplicateWarning(duplicateData, hasHighConfidenceDuplicate);
            setTimeout(showDuplicateModal, 300);
        }
    @elseif(old('first_name') || old('last_name'))
        setTimeout(checkForDuplicates, 500);
    @endif
})();
</script>
@endpush
@endsection
