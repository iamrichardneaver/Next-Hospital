@extends('layouts.app')

@section('title', 'Vital Signs Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Vital Signs Details</h1>
            <p class="text-secondary mb-0">
                @php
                    // Try to get patient through consultation (null-safe access)
                    $patient = null;
                    if ($vital->consultation && $vital->consultation->patient) {
                        $patient = $vital->consultation->patient;
                    }
                @endphp
                @if($patient)
                    Patient: {{ $patient->full_name }} ({{ $patient->patient_number }})
                @else
                    <span class="text-muted">Patient information not available (no consultation linked)</span>
                @endif
            </p>
        </div>
        <div>
            <a href="{{ route('vitals.index') }}" class="btn btn-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            @php
                // Try to get patient through consultation (null-safe access)
                $patient = null;
                if ($vital->consultation && $vital->consultation->patient) {
                    $patient = $vital->consultation->patient;
                }
            @endphp
            @if($patient)
            <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-person"></i> View Patient
            </a>
            @endif
            @if(session('show_queue_link'))
            <a href="{{ route('queues.index') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-list-ul"></i> View Queues
            </a>
            @endif
            @can('record_vitals')
            <a href="{{ route('vitals.edit', $vital) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Vital Signs Record</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Recorded Date & Time</div>
                            <div class="fw-bold">{{ $vital->recorded_at ? $vital->recorded_at->format('M d, Y h:i A') : 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Recorded By</div>
                            <div class="fw-bold">{{ $vital->recordedBy ? $vital->recordedBy->name : 'N/A' }}</div>
                        </div>
                    </div>

                    <hr>

                    @php
                        $hasAnyVital = ($vital->blood_pressure_systolic !== null && $vital->blood_pressure_systolic !== '')
                            || ($vital->blood_pressure_diastolic !== null && $vital->blood_pressure_diastolic !== '')
                            || ($vital->pulse_rate !== null && $vital->pulse_rate !== '')
                            || ($vital->temperature !== null && $vital->temperature !== '')
                            || ($vital->respiratory_rate !== null && $vital->respiratory_rate !== '')
                            || ($vital->oxygen_saturation !== null && $vital->oxygen_saturation !== '')
                            || ($vital->height !== null && $vital->height !== '')
                            || ($vital->weight !== null && $vital->weight !== '')
                            || ($vital->bmi !== null && $vital->bmi !== '');
                    @endphp

                    <div class="row g-3">
                        @if($vital->blood_pressure_systolic !== null && $vital->blood_pressure_systolic !== '' && $vital->blood_pressure_diastolic !== null && $vital->blood_pressure_diastolic !== '')
                        <div class="col-md-4 col-sm-6">
                            <div class="vital-item bg-light rounded p-3 text-center">
                                <div class="text-muted small mb-2">Blood Pressure</div>
                                <div class="fw-bold text-dark fs-4">{{ $vital->blood_pressure_systolic }}/{{ $vital->blood_pressure_diastolic }}</div>
                                <div class="text-muted small">mmHg</div>
                            </div>
                        </div>
                        @endif
                        
                        @if($vital->pulse_rate !== null && $vital->pulse_rate !== '')
                        <div class="col-md-4 col-sm-6">
                            <div class="vital-item bg-light rounded p-3 text-center">
                                <div class="text-muted small mb-2">Pulse Rate</div>
                                <div class="fw-bold text-dark fs-4">{{ $vital->pulse_rate }}</div>
                                <div class="text-muted small">bpm</div>
                            </div>
                        </div>
                        @endif
                        
                        @if($vital->temperature !== null && $vital->temperature !== '')
                        <div class="col-md-4 col-sm-6">
                            <div class="vital-item bg-light rounded p-3 text-center">
                                <div class="text-muted small mb-2">Temperature</div>
                                <div class="fw-bold text-dark fs-4">{{ is_numeric($vital->temperature) ? number_format((float)$vital->temperature, 1) : $vital->temperature }}</div>
                                <div class="text-muted small">°C</div>
                            </div>
                        </div>
                        @endif
                        
                        @if($vital->respiratory_rate !== null && $vital->respiratory_rate !== '')
                        <div class="col-md-4 col-sm-6">
                            <div class="vital-item bg-light rounded p-3 text-center">
                                <div class="text-muted small mb-2">Respiratory Rate</div>
                                <div class="fw-bold text-dark fs-4">{{ $vital->respiratory_rate }}</div>
                                <div class="text-muted small">breaths/min</div>
                            </div>
                        </div>
                        @endif
                        
                        @if($vital->oxygen_saturation !== null && $vital->oxygen_saturation !== '')
                        <div class="col-md-4 col-sm-6">
                            <div class="vital-item bg-light rounded p-3 text-center">
                                <div class="text-muted small mb-2">Oxygen Saturation</div>
                                <div class="fw-bold text-dark fs-4">{{ $vital->oxygen_saturation }}</div>
                                <div class="text-muted small">%</div>
                            </div>
                        </div>
                        @endif
                        
                        @if($vital->height !== null && $vital->height !== '')
                        <div class="col-md-4 col-sm-6">
                            <div class="vital-item bg-light rounded p-3 text-center">
                                <div class="text-muted small mb-2">Height</div>
                                <div class="fw-bold text-dark fs-4">{{ is_numeric($vital->height) ? number_format((float)$vital->height, 1) : $vital->height }}</div>
                                <div class="text-muted small">cm</div>
                            </div>
                        </div>
                        @endif
                        
                        @if($vital->weight !== null && $vital->weight !== '')
                        <div class="col-md-4 col-sm-6">
                            <div class="vital-item bg-light rounded p-3 text-center">
                                <div class="text-muted small mb-2">Weight</div>
                                <div class="fw-bold text-dark fs-4">{{ is_numeric($vital->weight) ? number_format((float)$vital->weight, 1) : $vital->weight }}</div>
                                <div class="text-muted small">kg</div>
                            </div>
                        </div>
                        @endif
                        
                        @if($vital->bmi !== null && $vital->bmi !== '')
                        <div class="col-md-4 col-sm-6">
                            <div class="vital-item bg-light rounded p-3 text-center">
                                <div class="text-muted small mb-2">BMI</div>
                                <div class="fw-bold text-dark fs-4">{{ is_numeric($vital->bmi) ? number_format((float)$vital->bmi, 1) : $vital->bmi }}</div>
                                <div class="text-muted small">kg/m²</div>
                            </div>
                        </div>
                        @endif

                        @if(!$hasAnyVital)
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>No vital sign values were recorded for this entry. The record may have been created before values were saved, or the data could not be loaded.
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Related Information</h6>
                </div>
                <div class="card-body">
                    @php
                        // Try to get patient through consultation (null-safe access)
                        $patient = null;
                        if ($vital->consultation && $vital->consultation->patient) {
                            $patient = $vital->consultation->patient;
                        }
                    @endphp
                    @if($patient)
                    <div class="mb-3">
                        <div class="text-muted small">Patient</div>
                        <div class="fw-bold">{{ $patient->full_name }}</div>
                        <div class="text-muted small">{{ $patient->patient_number }}</div>
                        <a href="{{ route('patients.show', $patient) }}" class="btn btn-sm btn-outline-primary mt-2">
                            <i class="bi bi-eye"></i> View Patient
                        </a>
                    </div>
                    @else
                    <div class="mb-3">
                        <div class="text-muted small">Patient</div>
                        <div class="text-muted">Not available (no consultation linked)</div>
                    </div>
                    @endif

                    @if($vital->consultation)
                    <div class="mb-3">
                        <div class="text-muted small">Consultation</div>
                        <div class="fw-bold">Consultation #{{ $vital->consultation->id }}</div>
                        <div class="text-muted small">{{ $vital->consultation->consultation_date ? $vital->consultation->consultation_date->format('M d, Y') : 'N/A' }}</div>
                        <a href="{{ route('consultations.show', $vital->consultation) }}" class="btn btn-sm btn-outline-primary mt-2">
                            <i class="bi bi-eye"></i> View Consultation
                        </a>
                    </div>
                    @endif

                    <div class="mb-3">
                        <div class="text-muted small">Record ID</div>
                        <div class="fw-bold">#{{ $vital->id }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
