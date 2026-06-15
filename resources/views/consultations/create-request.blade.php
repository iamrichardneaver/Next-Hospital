@extends('layouts.app')

@section('title', 'Create Consultation Request')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Create Consultation Request</h1>
                <p class="page-subtitle">Create a consultation request for doctor review</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('consultations.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Consultations
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('consultations.store-request') }}" method="POST" id="consultationRequestForm">
        @csrf

        <!-- Patient Selection Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person-check"></i> Patient Selection</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="visit_id" class="form-label">Select from Active Visits (Optional)</label>
                        <select class="form-select @error('visit_id') is-invalid @enderror" id="visit_id" name="visit_id">
                            <option value="">-- Or select patient below --</option>
                            @foreach($visits as $visit)
                                <option value="{{ $visit->id }}" data-patient-id="{{ $visit->patient_id }}" {{ old('visit_id') == $visit->id ? 'selected' : '' }}>
                                    {{ $visit->visit_token }} - {{ $visit->patient->first_name }} {{ $visit->patient->last_name }} ({{ $visit->patient->patient_number }})
                                </option>
                            @endforeach
                        </select>
                        @error('visit_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                        <select class="form-select @error('patient_id') is-invalid @enderror" id="patient_id" name="patient_id" required>
                            <option value="">Select Patient</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                                    {{ $patient->first_name }} {{ $patient->last_name }} ({{ $patient->patient_number }})
                                </option>
                            @endforeach
                        </select>
                        @error('patient_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Basic Information Section -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-data"></i> Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="doctor_id" class="form-label">Assigned Doctor <span class="text-danger">*</span></label>
                        @if(auth()->user()->hasRole('doctor'))
                            {{-- Doctors can only create requests for themselves --}}
                            <input type="text" class="form-control" value="Dr. {{ auth()->user()->first_name }} {{ auth()->user()->last_name }} - {{ auth()->user()->staffProfile->specialization ?? 'General Practice' }}" disabled>
                            <input type="hidden" name="doctor_id" value="{{ auth()->id() }}">
                            <small class="form-text text-muted">You can only create consultation requests for yourself</small>
                        @else
                            <select class="form-select @error('doctor_id') is-invalid @enderror" id="doctor_id" name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                        {{ $doctor->first_name }} {{ $doctor->last_name }} - {{ $doctor->staffProfile->specialization ?? 'General Practice' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('doctor_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="consultation_date" class="form-label">Consultation Date <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control @error('consultation_date') is-invalid @enderror" 
                               id="consultation_date" name="consultation_date" 
                               value="{{ old('consultation_date', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('consultation_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="consultation_type" class="form-label">Consultation Type <span class="text-danger">*</span></label>
                        <select class="form-select @error('consultation_type') is-invalid @enderror" id="consultation_type" name="consultation_type" required>
                            <option value="in-person" {{ old('consultation_type') == 'in-person' ? 'selected' : '' }}>In-Person</option>
                            <option value="teleconsultation" {{ old('consultation_type') == 'teleconsultation' ? 'selected' : '' }}>Teleconsultation</option>
                        </select>
                        @error('consultation_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="urgency" class="form-label">Priority</label>
                        <select class="form-select @error('urgency') is-invalid @enderror" id="urgency" name="urgency">
                            <option value="routine" {{ old('urgency') == 'routine' ? 'selected' : '' }}>Routine</option>
                            <option value="urgent" {{ old('urgency') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                            <option value="critical" {{ old('urgency') == 'critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                        @error('urgency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Chief Complaint Section -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-chat-text"></i> Chief Complaint</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="chief_complaint" class="form-label">Chief Complaint <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('chief_complaint') is-invalid @enderror" 
                              id="chief_complaint" name="chief_complaint" rows="3" 
                              placeholder="Brief description of the patient's main complaint..." required>{{ old('chief_complaint') }}</textarea>
                    @error('chief_complaint')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="history_of_present_illness" class="form-label">History of Present Illness (Optional)</label>
                    <textarea class="form-control @error('history_of_present_illness') is-invalid @enderror" 
                              id="history_of_present_illness" name="history_of_present_illness" rows="3" 
                              placeholder="Additional details about the current illness...">{{ old('history_of_present_illness') }}</textarea>
                    @error('history_of_present_illness')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Vitals Recording Section -->
        @can('record_vitals')
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-pulse"></i> Vital Signs Recording</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <label for="blood_pressure_systolic" class="form-label">BP Systolic (mmHg)</label>
                        <input type="number" class="form-control @error('blood_pressure_systolic') is-invalid @enderror" 
                               id="blood_pressure_systolic" name="blood_pressure_systolic" 
                               value="{{ old('blood_pressure_systolic') }}" placeholder="120" step="1" min="50" max="300">
                        @error('blood_pressure_systolic')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="blood_pressure_diastolic" class="form-label">BP Diastolic (mmHg)</label>
                        <input type="number" class="form-control @error('blood_pressure_diastolic') is-invalid @enderror" 
                               id="blood_pressure_diastolic" name="blood_pressure_diastolic" 
                               value="{{ old('blood_pressure_diastolic') }}" placeholder="80" step="1" min="30" max="200">
                        @error('blood_pressure_diastolic')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="pulse_rate" class="form-label">Pulse (bpm)</label>
                        <input type="number" class="form-control @error('pulse_rate') is-invalid @enderror" 
                               id="pulse_rate" name="pulse_rate" 
                               value="{{ old('pulse_rate') }}" placeholder="72" step="1" min="30" max="300">
                        @error('pulse_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="temperature" class="form-label">Temperature (°C)</label>
                        <input type="number" class="form-control @error('temperature') is-invalid @enderror" 
                               id="temperature" name="temperature" 
                               value="{{ old('temperature') }}" placeholder="36.5" step="0.1" min="30" max="45">
                        @error('temperature')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="respiratory_rate" class="form-label">Respiratory Rate</label>
                        <input type="number" class="form-control @error('respiratory_rate') is-invalid @enderror" 
                               id="respiratory_rate" name="respiratory_rate" 
                               value="{{ old('respiratory_rate') }}" placeholder="18" step="1" min="5" max="60">
                        @error('respiratory_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="oxygen_saturation" class="form-label">O2 Saturation (%)</label>
                        <input type="number" class="form-control @error('oxygen_saturation') is-invalid @enderror" 
                               id="oxygen_saturation" name="oxygen_saturation" 
                               value="{{ old('oxygen_saturation') }}" placeholder="98" step="1" min="50" max="100">
                        @error('oxygen_saturation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="height" class="form-label">Height (cm)</label>
                        <input type="number" class="form-control @error('height') is-invalid @enderror" 
                               id="height" name="height" 
                               value="{{ old('height') }}" placeholder="170" step="0.1" min="50" max="250">
                        @error('height')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="weight" class="form-label">Weight (kg)</label>
                        <input type="number" class="form-control @error('weight') is-invalid @enderror" 
                               id="weight" name="weight" 
                               value="{{ old('weight') }}" placeholder="70" step="0.1" min="10" max="300">
                        @error('weight')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="bmi" class="form-label">BMI (Auto-calculated)</label>
                        <input type="number" class="form-control" id="bmi" name="bmi" 
                               readonly placeholder="Auto-calculated" step="0.1">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex align-items-end h-100">
                            <button type="button" class="btn btn-outline-secondary" onclick="clearVitals()">
                                <i class="bi bi-arrow-clockwise"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Note:</strong> Vital signs are optional but recommended for better patient care. BMI will be automatically calculated when height and weight are provided.
                </div>
            </div>
        </div>
        @else
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-pulse"></i> Vital Signs Recording</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Note:</strong> Only nurses can record vital signs. Please have a nurse record the patient's vitals before the consultation.
                </div>
            </div>
        </div>
        @endcan

        <!-- Additional Notes Section -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-sticky"></i> Additional Notes & Instructions</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="reception_notes" class="form-label">Reception Notes (Optional)</label>
                    <textarea class="form-control @error('reception_notes') is-invalid @enderror" 
                              id="reception_notes" name="reception_notes" rows="2" 
                              placeholder="Any additional notes from reception...">{{ old('reception_notes') }}</textarea>
                    @error('reception_notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="doctor_remarks" class="form-label">Notes & Instructions for Doctor</label>
                    <textarea class="form-control @error('doctor_remarks') is-invalid @enderror" 
                              id="doctor_remarks" name="doctor_remarks" rows="6" 
                              placeholder="Leave detailed notes, instructions, or special considerations for the assigned doctor...">{{ old('doctor_remarks') }}</textarea>
                    @error('doctor_remarks')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i>
                        Use this section to provide detailed instructions, special patient considerations, or any important information the doctor should know before the consultation.
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('consultations.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Consultation Request
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill patient when visit is selected
    const visitSelect = document.getElementById('visit_id');
    const patientSelect = document.getElementById('patient_id');
    
    visitSelect.addEventListener('change', function() {
        if (this.value) {
            const patientId = this.options[this.selectedIndex].getAttribute('data-patient-id');
            if (patientId) {
                patientSelect.value = patientId;
            }
        }
    });
    
    // Set current time if consultation date is not set
    const consultationDate = document.getElementById('consultation_date');
    if (!consultationDate.value) {
        consultationDate.value = new Date().toISOString().slice(0, 16);
    }
    
    // BMI calculation
    const heightInput = document.getElementById('height');
    const weightInput = document.getElementById('weight');
    const bmiInput = document.getElementById('bmi');
    
    function calculateBMI() {
        const height = parseFloat(heightInput.value);
        const weight = parseFloat(weightInput.value);
        
        if (height && weight && height > 0 && weight > 0) {
            const heightInMeters = height / 100;
            const bmi = weight / (heightInMeters * heightInMeters);
            bmiInput.value = bmi.toFixed(1);
        } else {
            bmiInput.value = '';
        }
    }
    
    heightInput.addEventListener('input', calculateBMI);
    weightInput.addEventListener('input', calculateBMI);
    
    // Calculate BMI on page load if values exist
    calculateBMI();
    
    // Initialize CKEditor for doctor remarks
    if (typeof ClassicEditor !== 'undefined') {
        ClassicEditor
            .create(document.querySelector('#doctor_remarks'), {
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'underline', '|',
                        'bulletedList', 'numberedList', '|',
                        'outdent', 'indent', '|',
                        'blockQuote', 'insertTable', '|',
                        'undo', 'redo'
                    ]
                },
                language: 'en',
                table: {
                    contentToolbar: [
                        'tableColumn', 'tableRow', 'mergeTableCells'
                    ]
                }
            })
            .then(editor => {
                window.doctorRemarksEditor = editor;
            })
            .catch(error => {
                console.error('Error initializing CKEditor:', error);
            });
    }
});

// Clear vitals function
function clearVitals() {
    const vitalInputs = [
        'blood_pressure_systolic', 'blood_pressure_diastolic', 'pulse_rate',
        'temperature', 'respiratory_rate', 'oxygen_saturation', 'height', 'weight'
    ];
    
    vitalInputs.forEach(inputId => {
        document.getElementById(inputId).value = '';
    });
    
    document.getElementById('bmi').value = '';
}
</script>
@endsection
