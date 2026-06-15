@extends('layouts.app')

@section('title', 'Patient Check-In')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1" style="color: #1e3a5f;">Patient Check-In</h1><p class="text-secondary mb-0">Register new visit</p></div>
        <a href="{{ route('visits.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Cancel</a>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('visits.store') }}" method="POST" id="visitCheckInForm">
                        @csrf
                        <div class="mb-3">
                            <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                            <select class="form-select patient-search-select @error('patient_id') is-invalid @enderror" id="patient_id" name="patient_id" required
                                data-placeholder="Type to search by name or number...">
                                <option value=""></option>
                                @foreach($patients as $patient)
                                <option value="{{ $patient->id }}"
                                    @if(old('patient_id', $selectedPatientId ?? null) == $patient->id) selected @endif>
                                    {{ $patient->patient_number }} - {{ $patient->full_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('patient_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <small class="form-text text-muted"><i class="bi bi-search"></i> Type at least 2 characters to search patients by name or number.</small>
                            @if(isset($selectedPatient))
                            <div class="form-text mt-1">
                                <i class="bi bi-info-circle text-info"></i>
                                Patient <strong>{{ $selectedPatient->full_name }}</strong> has been pre-selected from registration.
                            </div>
                            @endif
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="visit_type" class="form-label">Visit Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('visit_type') is-invalid @enderror" id="visit_type" name="visit_type" required>
                                    <option value="">Select Type</option>
                                    @foreach([
                                        'OPD' => 'OPD (Outpatient)',
                                        'IPD' => 'IPD (Inpatient)',
                                        'Emergency' => 'Emergency',
                                        'LabOnly' => 'Lab Only',
                                        'PharmacyOnly' => 'Pharmacy Only',
                                        'RadiologyOnly' => 'Radiology Only',
                                    ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('visit_type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('visit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div id="visitPolicyHint" class="form-text mt-2" style="display: none;"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="routine" @selected(old('priority', 'routine') === 'routine')>Routine</option>
                                    <option value="urgent" @selected(old('priority') === 'urgent')>Urgent</option>
                                    <option value="critical" @selected(old('priority') === 'critical')>Critical</option>
                                </select>
                            </div>
                        </div>
                        <div class="row" id="consultationStaffFields">
                            <div class="col-md-6 mb-3">
                                <label for="assigned_doctor_id" class="form-label">Assign Doctor</label>
                                @if(auth()->user()->hasRole('doctor'))
                                    <input type="text" class="form-control" value="Dr. {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}" disabled>
                                    <input type="hidden" name="assigned_doctor_id" value="{{ auth()->id() }}">
                                    <small class="form-text text-muted">You can only assign visits to yourself</small>
                                @else
                                    <select class="form-select" id="assigned_doctor_id" name="assigned_doctor_id">
                                        <option value="">Select Doctor (optional — auto-assigned if blank)</option>
                                        @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" @selected(old('assigned_doctor_id') == $doctor->id)>
                                            Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="assigned_nurse_id" class="form-label">Assign Nurse</label>
                                <select class="form-select" id="assigned_nurse_id" name="assigned_nurse_id">
                                    <option value="">Select Nurse</option>
                                    @foreach($nurses as $nurse)
                                    <option value="{{ $nurse->id }}" @selected(old('assigned_nurse_id') == $nurse->id)>
                                        {{ $nurse->first_name }} {{ $nurse->last_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="chief_complaint" class="form-label">Chief Complaint</label>
                            <textarea class="form-control" id="chief_complaint" name="chief_complaint" rows="3">{{ old('chief_complaint') }}</textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('visits.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Check-In Patient</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const consultationTypes = ['OPD', 'IPD', 'Emergency'];
    const directServiceTypes = ['LabOnly', 'PharmacyOnly', 'RadiologyOnly'];

    $('#patient_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: $('#patient_id').data('placeholder') || 'Type to search by name or number...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '{{ route("patients.search") }}',
            dataType: 'json',
            delay: 300,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(response) {
                const patients = response.data || [];
                return {
                    results: patients.map(function(patient) {
                        const name = [patient.first_name, patient.last_name].filter(Boolean).join(' ');
                        return {
                            id: patient.id,
                            text: (patient.patient_number || '') + ' - ' + name
                        };
                    })
                };
            },
            cache: true
        },
        language: {
            noResults: function() { return 'No patient found.'; },
            searching: function() { return 'Searching...'; },
            inputTooShort: function() { return 'Type at least 2 characters...'; }
        }
    });

    const policyHints = {
        OPD: '<i class="bi bi-cash-coin text-warning"></i> <strong>OPD:</strong> Full payment required before queue serve and consultation. No partial payments.',
        IPD: '<i class="bi bi-hospital text-success"></i> <strong>IPD:</strong> Partial or full payment allowed before or after service. Workflow is not blocked on unpaid balance.',
        Emergency: '<i class="bi bi-cash-coin text-danger"></i> <strong>Emergency:</strong> Full payment required before service (outpatient rules). Patient is queued as critical priority.',
        LabOnly: '<i class="bi bi-info-circle"></i> <strong>Lab Only:</strong> Patient goes directly to lab queue — no doctor consultation.',
        PharmacyOnly: '<i class="bi bi-info-circle"></i> <strong>Pharmacy Only:</strong> Patient goes directly to pharmacy queue — no doctor consultation.',
        RadiologyOnly: '<i class="bi bi-info-circle"></i> <strong>Radiology Only:</strong> Patient goes directly to radiology queue — no doctor consultation.'
    };

    function updateVisitTypeUi() {
        const type = document.getElementById('visit_type').value;
        const hint = document.getElementById('visitPolicyHint');
        const staffFields = document.getElementById('consultationStaffFields');
        const prioritySelect = document.getElementById('priority');

        if (type && policyHints[type]) {
            hint.style.display = 'block';
            hint.innerHTML = policyHints[type];
        } else {
            hint.style.display = 'none';
        }

        if (directServiceTypes.includes(type)) {
            staffFields.style.display = 'none';
            document.getElementById('assigned_doctor_id')?.removeAttribute('name');
            document.getElementById('assigned_nurse_id')?.removeAttribute('name');
        } else {
            staffFields.style.display = '';
            const doctorField = document.getElementById('assigned_doctor_id');
            const nurseField = document.getElementById('assigned_nurse_id');
            if (doctorField && !doctorField.disabled) {
                doctorField.setAttribute('name', 'assigned_doctor_id');
            }
            if (nurseField) {
                nurseField.setAttribute('name', 'assigned_nurse_id');
            }
        }

        if (type === 'Emergency' && prioritySelect.value === 'routine') {
            prioritySelect.value = 'critical';
        }
    }

    document.getElementById('visit_type').addEventListener('change', updateVisitTypeUi);
    updateVisitTypeUi();
});
</script>
@endpush
