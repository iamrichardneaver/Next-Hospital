@extends('layouts.app')

@section('title', 'Edit Vital Signs')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Edit Vital Signs</h1>
                <p class="page-subtitle">Update patient vital signs record</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('vitals.show', $vital) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Details
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('vitals.update', $vital) }}" method="POST" id="vitalsForm">
        @csrf
        @method('PUT')

        <!-- Patient Information (Read-only) -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person-check"></i> Patient Information</h5>
            </div>
            <div class="card-body">
                @if($vital->patient)
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Patient:</strong> {{ $vital->patient->full_name }}</p>
                            <p><strong>Patient Number:</strong> {{ $vital->patient->patient_number }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Recorded:</strong> {{ $vital->recorded_at ? $vital->recorded_at->format('M d, Y h:i A') : 'N/A' }}</p>
                            <p><strong>Recorded By:</strong> {{ $vital->recordedBy ? $vital->recordedBy->name : 'N/A' }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-muted">Patient information not available</p>
                @endif
            </div>
        </div>

        <!-- Vital Signs Recording Section -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-pulse"></i> Vital Signs</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <label for="blood_pressure_systolic" class="form-label">BP Systolic (mmHg)</label>
                        <input type="number" class="form-control @error('blood_pressure_systolic') is-invalid @enderror" 
                               id="blood_pressure_systolic" name="blood_pressure_systolic" 
                               value="{{ old('blood_pressure_systolic', $vital->blood_pressure_systolic) }}" 
                               placeholder="120" step="1" min="50" max="300">
                        @error('blood_pressure_systolic')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="blood_pressure_diastolic" class="form-label">BP Diastolic (mmHg)</label>
                        <input type="number" class="form-control @error('blood_pressure_diastolic') is-invalid @enderror" 
                               id="blood_pressure_diastolic" name="blood_pressure_diastolic" 
                               value="{{ old('blood_pressure_diastolic', $vital->blood_pressure_diastolic) }}" 
                               placeholder="80" step="1" min="30" max="200">
                        @error('blood_pressure_diastolic')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="pulse_rate" class="form-label">Pulse Rate (bpm)</label>
                        <input type="number" class="form-control @error('pulse_rate') is-invalid @enderror" 
                               id="pulse_rate" name="pulse_rate" 
                               value="{{ old('pulse_rate', $vital->pulse_rate) }}" 
                               placeholder="72" step="1" min="30" max="300">
                        @error('pulse_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="temperature" class="form-label">Temperature (°C)</label>
                        <input type="number" class="form-control @error('temperature') is-invalid @enderror" 
                               id="temperature" name="temperature" 
                               value="{{ old('temperature', $vital->temperature) }}" 
                               placeholder="36.5" step="0.1" min="30" max="45">
                        @error('temperature')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="respiratory_rate" class="form-label">Respiratory Rate (breaths/min)</label>
                        <input type="number" class="form-control @error('respiratory_rate') is-invalid @enderror" 
                               id="respiratory_rate" name="respiratory_rate" 
                               value="{{ old('respiratory_rate', $vital->respiratory_rate) }}" 
                               placeholder="16" step="1" min="5" max="60">
                        @error('respiratory_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="oxygen_saturation" class="form-label">Oxygen Saturation (%)</label>
                        <input type="number" class="form-control @error('oxygen_saturation') is-invalid @enderror" 
                               id="oxygen_saturation" name="oxygen_saturation" 
                               value="{{ old('oxygen_saturation', $vital->oxygen_saturation) }}" 
                               placeholder="98" step="1" min="50" max="100">
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
                               value="{{ old('height', $vital->height) }}" 
                               placeholder="170" step="0.1" min="50" max="250">
                        @error('height')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="weight" class="form-label">Weight (kg)</label>
                        <input type="number" class="form-control @error('weight') is-invalid @enderror" 
                               id="weight" name="weight" 
                               value="{{ old('weight', $vital->weight) }}" 
                               placeholder="70" step="0.1" min="10" max="300">
                        @error('weight')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="bmi" class="form-label">BMI (kg/m²)</label>
                        <input type="number" class="form-control @error('bmi') is-invalid @enderror" 
                               id="bmi" name="bmi" 
                               value="{{ old('bmi', $vital->bmi) }}" 
                               placeholder="24.2" step="0.1" min="10" max="50" readonly>
                        <small class="text-muted">Auto-calculated from height and weight</small>
                        @error('bmi')
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
                    <a href="{{ route('vitals.show', $vital) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Vital Signs
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const heightInput = document.getElementById('height');
    const weightInput = document.getElementById('weight');
    const bmiInput = document.getElementById('bmi');
    
    function calculateBMI() {
        const height = parseFloat(heightInput.value);
        const weight = parseFloat(weightInput.value);
        
        if (height && weight && height > 0 && weight > 0) {
            const heightInMeters = height / 100;
            const bmi = (weight / (heightInMeters * heightInMeters)).toFixed(1);
            bmiInput.value = bmi;
        } else {
            bmiInput.value = '';
        }
    }
    
    heightInput.addEventListener('input', calculateBMI);
    weightInput.addEventListener('input', calculateBMI);
});
</script>
@endpush
@endsection
