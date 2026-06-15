@extends('layouts.app')

@section('title', 'Record Vital Signs')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Record Vital Signs</h1>
                <p class="page-subtitle">Record patient vital signs for consultation</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('consultations.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Consultations
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('vitals.store') }}" method="POST" id="vitalsForm">
        @csrf
        
        <!-- Hidden field to track if coming from create patient flow -->
        @if(!isset($selectedVisitId) || !$selectedVisitId)
            <input type="hidden" name="from_create" value="1">
        @endif
        @if(isset($selectedVisitId) && $selectedVisitId)
            <input type="hidden" name="visit_id" value="{{ $selectedVisitId }}">
        @endif

        <!-- Patient Selection Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person-check"></i> Patient Selection</h5>
            </div>
            <div class="card-body">
                @if($selectedPatient)
                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <div>
                            <strong>Patient Pre-selected:</strong> {{ $selectedPatient->first_name }} {{ $selectedPatient->last_name }} ({{ $selectedPatient->patient_number }})
                            <br><small>
                                @if(isset($selectedVisitId))
                                    Please record the vital signs for this patient who just checked in. Visit ID: {{ $selectedVisitId }}
                                @else
                                    Please record the vital signs for this patient.
                                @endif
                            </small>
                        </div>
                    </div>
                @endif
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                        <select class="form-select @error('patient_id') is-invalid @enderror" id="patient_id" name="patient_id" required>
                            <option value="">Select Patient</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" {{ (old('patient_id') == $patient->id || $selectedPatientId == $patient->id) ? 'selected' : '' }}>
                                    {{ $patient->first_name }} {{ $patient->last_name }} ({{ $patient->patient_number }})
                                </option>
                            @endforeach
                        </select>
                        @error('patient_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="consultation_id" class="form-label">Associated Consultation (Optional)</label>
                        <select class="form-select @error('consultation_id') is-invalid @enderror" id="consultation_id" name="consultation_id">
                            <option value="">Select Consultation</option>
                            @foreach($consultations as $consultation)
                                <option value="{{ $consultation->id }}" {{ (old('consultation_id') == $consultation->id || (isset($selectedConsultationId) && $selectedConsultationId == $consultation->id)) ? 'selected' : '' }}>
                                    {{ $consultation->patient ? $consultation->patient->first_name . ' ' . $consultation->patient->last_name : 'Patient Not Found' }} - {{ $consultation->created_at->format('M d, Y H:i') }}
                                </option>
                            @endforeach
                        </select>
                        @error('consultation_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Vital Signs Recording Section -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
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
                                <i class="bi bi-arrow-clockwise"></i> Clear All
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Notes -->
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes (Optional)</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                              id="notes" name="notes" rows="2" 
                              placeholder="Any additional notes about the vital signs...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Note:</strong> Vital signs are essential for patient care. BMI will be automatically calculated when height and weight are provided.
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
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Record Vital Signs
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
