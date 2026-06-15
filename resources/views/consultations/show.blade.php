@extends('layouts.app')

@section('title', 'Consultation Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Consultation Details</h1>
            <p class="text-secondary mb-0">
                {{ $consultation->consultation_number }} - {{ $consultation->consultation_date->format('M d, Y') }}
                @if($consultation->is_draft)
                    <span class="badge bg-warning ms-2">Draft</span>
                @endif
                @if($consultation->amended_at)
                    <span class="badge bg-info ms-2" title="Last amended {{ $consultation->amended_at->format('M d, Y H:i') }}">Amended</span>
                @endif
            </p>
        </div>
        <div>
            <a href="{{ route('consultations.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            @can('edit_consultations')
            <a href="{{ route('consultations.edit', $consultation) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> {{ $consultation->consultation_status === 'completed' ? 'Amend' : 'Edit' }}
            </a>
            @endcan
            @if($consultation->is_draft)
                <button type="button" class="btn btn-success" onclick="markAsCompleted()">
                    <i class="bi bi-check-circle"></i> Complete
                </button>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Patient Information Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-person"></i> Patient</h6></div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="user-avatar bg-primary mx-auto" style="width: 80px; height: 80px; font-size: 2.5rem; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                            @if($consultation->patient)
                                {{ strtoupper(substr($consultation->patient->first_name, 0, 1)) }}{{ strtoupper(substr($consultation->patient->last_name, 0, 1)) }}
                            @else
                                ??
                            @endif
                        </div>
                    </div>
                    <h6 class="text-center mb-3">{{ $consultation->patient ? $consultation->patient->full_name : 'Patient Not Found' }}</h6>
                    @if($consultation->patient)
                        <p class="mb-1 small"><strong>ID:</strong> {{ $consultation->patient->patient_number }}</p>
                        <p class="mb-1 small"><strong>Age:</strong> {{ $consultation->patient->age }} years</p>
                        <p class="mb-1 small"><strong>Gender:</strong> {{ $consultation->patient->gender }}</p>
                    @else
                        <p class="mb-1 small text-danger">Patient information not available</p>
                    @endif
                    @if($consultation->patient)
                        <p class="mb-0 small"><strong>Phone:</strong> {{ $consultation->patient->phone ?? 'N/A' }}</p>
                        <a href="{{ route('patients.show', $consultation->patient) }}" class="btn btn-sm btn-outline-primary w-100 mt-3"><i class="bi bi-eye"></i> View Profile</a>
                    @endif
                </div>
            </div>

            <!-- Consultation Status -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-info text-white"><h6 class="mb-0"><i class="bi bi-clipboard-check"></i> Consultation Info</h6></div>
                <div class="card-body">
                    <p class="mb-1 small"><strong>Doctor:</strong> Dr. {{ $consultation->doctor->firstname }} {{ $consultation->doctor->lastname }}</p>
                    <p class="mb-1 small"><strong>Type:</strong> <span class="badge bg-info">{{ ucfirst($consultation->consultation_type) }}</span></p>
                    <p class="mb-1 small"><strong>Status:</strong> 
                        <span class="badge bg-{{ $consultation->consultation_status === 'completed' ? 'success' : 'warning' }}">
                            {{ ucfirst($consultation->consultation_status) }}
                        </span>
                    </p>
                    @if($consultation->is_draft)
                        <p class="mb-0 small"><strong>Draft:</strong> <span class="badge bg-warning">Yes</span></p>
                    @endif
                    @if($consultation->amended_at)
                        <p class="mb-0 small mt-2"><strong>Amended:</strong> {{ $consultation->amended_at->format('M d, Y H:i') }}
                            @if($consultation->amendedBy)
                                by {{ $consultation->amendedBy->first_name }} {{ $consultation->amendedBy->last_name }}
                            @endif
                        </p>
                        @if($consultation->amendment_notes)
                            <p class="mb-0 small text-muted">{{ $consultation->amendment_notes }}</p>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Vitals Summary (from nurse at check-in or from consultation) -->
            @php
                $vitalsCollection = $consultation->relationLoaded('vitals') ? $consultation->getRelation('vitals')->sortByDesc('recorded_at') : $consultation->vitals()->orderBy('recorded_at', 'desc')->get();
                $vitalsFromRecord = $vitalsCollection->isNotEmpty() ? $vitalsCollection->first() : null;
                $hasVitalsOnConsultation = $consultation->blood_pressure_systolic || $consultation->temperature || $consultation->pulse_rate || $consultation->height || $consultation->weight;
                $hasVitalsFromNurse = $vitalsFromRecord && (
                    ($vitalsFromRecord->blood_pressure_systolic !== null && $vitalsFromRecord->blood_pressure_systolic !== '') ||
                    ($vitalsFromRecord->blood_pressure_diastolic !== null && $vitalsFromRecord->blood_pressure_diastolic !== '') ||
                    ($vitalsFromRecord->pulse_rate !== null && $vitalsFromRecord->pulse_rate !== '') ||
                    ($vitalsFromRecord->temperature !== null && $vitalsFromRecord->temperature !== '') ||
                    ($vitalsFromRecord->respiratory_rate !== null && $vitalsFromRecord->respiratory_rate !== '') ||
                    ($vitalsFromRecord->oxygen_saturation !== null && $vitalsFromRecord->oxygen_saturation !== '') ||
                    ($vitalsFromRecord->height !== null && $vitalsFromRecord->height !== '') ||
                    ($vitalsFromRecord->weight !== null && $vitalsFromRecord->weight !== '') ||
                    ($vitalsFromRecord->bmi !== null && $vitalsFromRecord->bmi !== '')
                );
                $showVitalsSummary = $hasVitalsOnConsultation || $hasVitalsFromNurse;
            @endphp
            @if($showVitalsSummary)
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-success text-white"><h6 class="mb-0"><i class="bi bi-heart-pulse"></i> Vitals (at check-in)</h6></div>
                <div class="card-body">
                    @if($hasVitalsFromNurse)
                        @if($vitalsFromRecord->recorded_at)
                            <p class="mb-2 small text-muted"><i class="bi bi-clock"></i> Recorded {{ $vitalsFromRecord->recorded_at->format('M d, Y H:i') }}@if($vitalsFromRecord->recordedBy) by {{ $vitalsFromRecord->recordedBy->name ?? $vitalsFromRecord->recordedBy->first_name . ' ' . $vitalsFromRecord->recordedBy->last_name }}</p>@endif
                        @endif
                        @if(($vitalsFromRecord->blood_pressure_systolic !== null && $vitalsFromRecord->blood_pressure_systolic !== '') && ($vitalsFromRecord->blood_pressure_diastolic !== null && $vitalsFromRecord->blood_pressure_diastolic !== ''))
                            <p class="mb-1 small"><strong>BP:</strong> {{ $vitalsFromRecord->blood_pressure_systolic }}/{{ $vitalsFromRecord->blood_pressure_diastolic }} mmHg</p>
                        @endif
                        @if($vitalsFromRecord->pulse_rate !== null && $vitalsFromRecord->pulse_rate !== '')
                            <p class="mb-1 small"><strong>Pulse:</strong> {{ $vitalsFromRecord->pulse_rate }} bpm</p>
                        @endif
                        @if($vitalsFromRecord->temperature !== null && $vitalsFromRecord->temperature !== '')
                            <p class="mb-1 small"><strong>Temp:</strong> {{ is_numeric($vitalsFromRecord->temperature) ? number_format((float)$vitalsFromRecord->temperature, 1) : $vitalsFromRecord->temperature }}°C</p>
                        @endif
                        @if($vitalsFromRecord->respiratory_rate !== null && $vitalsFromRecord->respiratory_rate !== '')
                            <p class="mb-1 small"><strong>Resp. rate:</strong> {{ $vitalsFromRecord->respiratory_rate }}/min</p>
                        @endif
                        @if($vitalsFromRecord->oxygen_saturation !== null && $vitalsFromRecord->oxygen_saturation !== '')
                            <p class="mb-1 small"><strong>SpO₂:</strong> {{ $vitalsFromRecord->oxygen_saturation }}%</p>
                        @endif
                        @if(($vitalsFromRecord->height !== null && $vitalsFromRecord->height !== '') || ($vitalsFromRecord->weight !== null && $vitalsFromRecord->weight !== ''))
                            @if($vitalsFromRecord->height)
                                <p class="mb-1 small"><strong>Height:</strong> {{ is_numeric($vitalsFromRecord->height) ? number_format((float)$vitalsFromRecord->height, 1) : $vitalsFromRecord->height }} cm</p>
                            @endif
                            @if($vitalsFromRecord->weight)
                                <p class="mb-1 small"><strong>Weight:</strong> {{ is_numeric($vitalsFromRecord->weight) ? number_format((float)$vitalsFromRecord->weight, 1) : $vitalsFromRecord->weight }} kg</p>
                            @endif
                            @if($vitalsFromRecord->bmi !== null && $vitalsFromRecord->bmi !== '')
                                <p class="mb-1 small"><strong>BMI:</strong> {{ is_numeric($vitalsFromRecord->bmi) ? number_format((float)$vitalsFromRecord->bmi, 1) : $vitalsFromRecord->bmi }}</p>
                            @endif
                        @endif
                    @elseif($hasVitalsOnConsultation)
                        @if($consultation->blood_pressure_systolic)
                            <p class="mb-1 small"><strong>BP:</strong> {{ $consultation->blood_pressure_systolic }}/{{ $consultation->blood_pressure_diastolic }} mmHg</p>
                            @if(method_exists($consultation, 'blood_pressure_category') && $consultation->blood_pressure_category)
                                <p class="mb-1 small text-muted">{{ $consultation->blood_pressure_category }}</p>
                            @endif
                        @endif
                        @if($consultation->pulse_rate)
                            <p class="mb-1 small"><strong>Pulse:</strong> {{ $consultation->pulse_rate }} bpm</p>
                        @endif
                        @if($consultation->temperature)
                            <p class="mb-1 small"><strong>Temp:</strong> {{ $consultation->temperature }}°C</p>
                        @endif
                        @if($consultation->height && $consultation->weight && isset($consultation->bmi))
                            <p class="mb-1 small"><strong>BMI:</strong> {{ $consultation->bmi }}@if(method_exists($consultation, 'bmi_category') && $consultation->bmi_category) ({{ $consultation->bmi_category }})@endif</p>
                        @endif
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            @include('consultations.partials.lab-requests', ['consultation' => $consultation])

            @php
                // Define dynamic tabs based on available data
                $tabs = [];
                
                // Always show Complaints & History tab if there's any complaint data
                if ($consultation->chief_complaint || $consultation->history_of_present_illness || 
                    $consultation->on_direct_questioning || $consultation->past_medical_history_details || 
                    $consultation->past_medical_history || $consultation->family_history || 
                    $consultation->social_history || $consultation->drug_history || 
                    $consultation->allergy_history || $consultation->past_medical_history_others) {
                    $tabs['complaints'] = [
                        'id' => 'complaints-tab',
                        'target' => 'complaints',
                        'icon' => 'bi-exclamation-triangle',
                        'title' => 'Complaints & History',
                        'active' => true
                    ];
                }
                
                // Show Examination tab if there's examination data
                if ($consultation->general_examination || $consultation->cardiovascular_examination || 
                    $consultation->respiratory_examination || $consultation->abdominal_examination || 
                    $consultation->neurological_examination || $consultation->physical_examination ||
                    $consultation->vitals || $consultation->blood_pressure_systolic || 
                    $consultation->blood_pressure_diastolic || $consultation->pulse_rate || 
                    $consultation->temperature || $consultation->respiratory_rate || 
                    $consultation->oxygen_saturation || $consultation->height || 
                    $consultation->weight || $consultation->bmi) {
                    $tabs['examination'] = [
                        'id' => 'examination-tab',
                        'target' => 'examination',
                        'icon' => 'bi-clipboard-pulse',
                        'title' => 'Examination',
                        'active' => !isset($tabs['complaints'])
                    ];
                }
                
                // Show Diagnosis & Treatment tab if there's diagnosis data
                if ($consultation->doctors_impression || $consultation->treatment_plan || 
                    $consultation->icd_10_code || $consultation->medications_prescribed || 
                    $consultation->investigations_ordered || $consultation->follow_up_instructions ||
                    $consultation->assessment || $consultation->plan) {
                    $tabs['diagnosis'] = [
                        'id' => 'diagnosis-tab',
                        'target' => 'diagnosis',
                        'icon' => 'bi-clipboard-check',
                        'title' => 'Diagnosis & Treatment',
                        'active' => !isset($tabs['complaints']) && !isset($tabs['examination'])
                    ];
                }
                
                // Show Additional Complaints tab if there are presenting complaints
                if ($consultation->presenting_complaints && is_array($consultation->presenting_complaints) && count($consultation->presenting_complaints) > 0) {
                    $tabs['additional'] = [
                        'id' => 'additional-tab',
                        'target' => 'additional',
                        'icon' => 'bi-list-ul',
                        'title' => 'Additional Complaints',
                        'active' => false
                    ];
                }
                
                // Show Vitals tab if there are vital signs (from vitals relationship)
                if (isset($consultation->vitals) && is_countable($consultation->vitals) && count($consultation->vitals) > 0) {
                    $tabs['vitals'] = [
                        'id' => 'vitals-tab',
                        'target' => 'vitals',
                        'icon' => 'bi-heart-pulse',
                        'title' => 'Vital Signs',
                        'active' => false
                    ];
                }
                
                // Show Diagnoses tab if there are diagnoses
                if (isset($consultation->consultationDiagnoses) && is_countable($consultation->consultationDiagnoses) && count($consultation->consultationDiagnoses) > 0) {
                    $tabs['diagnoses'] = [
                        'id' => 'diagnoses-tab',
                        'target' => 'diagnoses',
                        'icon' => 'bi-clipboard-medical',
                        'title' => 'Diagnoses',
                        'active' => false
                    ];
                }
                
                // Show Interventions tab if there are interventions
                if (isset($consultation->interventions) && is_countable($consultation->interventions) && count($consultation->interventions) > 0) {
                    $tabs['interventions'] = [
                        'id' => 'interventions-tab',
                        'target' => 'interventions',
                        'icon' => 'bi-tools',
                        'title' => 'Interventions',
                        'active' => false
                    ];
                }
                
                // If no tabs have data, show a default tab
                if (empty($tabs)) {
                    $tabs['overview'] = [
                        'id' => 'overview-tab',
                        'target' => 'overview',
                        'icon' => 'bi-info-circle',
                        'title' => 'Overview',
                        'active' => true
                    ];
                }
            @endphp
            
            <ul class="nav nav-tabs mb-3" role="tablist">
                @foreach($tabs as $key => $tab)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $tab['active'] ? 'active' : '' }}" 
                            id="{{ $tab['id'] }}" 
                            data-bs-toggle="tab" 
                            data-bs-target="#{{ $tab['target'] }}" 
                            type="button" 
                            role="tab">
                        <i class="bi {{ $tab['icon'] }}"></i> {{ $tab['title'] }}
                    </button>
                </li>
                @endforeach
            </ul>

            <div class="tab-content">
                @foreach($tabs as $key => $tab)
                    @if($key === 'complaints')
                    <!-- Complaints & History Tab -->
                    <div class="tab-pane fade {{ $tab['active'] ? 'show active' : '' }}" id="{{ $tab['target'] }}" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-dark mb-3"><i class="bi bi-exclamation-triangle"></i> Chief Complaint</h5>
                            <div class="mb-4 consultation-content">{!! $consultation->chief_complaint !!}</div>

                            @if($consultation->doctor_remarks)
                            <h5 class="text-dark mb-3"><i class="bi bi-sticky"></i> Notes & Instructions for Doctor</h5>
                            <div class="mb-4 consultation-content">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Reception/Nurse Notes:</strong>
                                </div>
                                <div class="p-3 bg-light rounded">{!! $consultation->doctor_remarks !!}</div>
                            </div>
                            @endif

                            @if($consultation->history_of_present_illness)
                            <h5 class="text-dark mb-3"><i class="bi bi-clock-history"></i> History of Presenting Illness</h5>
                            <div class="mb-4 consultation-content">{!! $consultation->history_of_present_illness !!}</div>
                            @endif

                            @if($consultation->on_direct_questioning)
                            <h5 class="text-dark mb-3"><i class="bi bi-question-circle"></i> On Direct Questioning</h5>
                            <div class="mb-4 consultation-content">{!! $consultation->on_direct_questioning !!}</div>
                            @endif

                            <!-- Past Medical History -->
                            @if($consultation->past_medical_history_details || $consultation->past_medical_history)
                            <h5 class="text-dark mb-3"><i class="bi bi-heart-pulse"></i> Past Medical History</h5>
                            @if($consultation->past_medical_history_details)
                                <div class="row mb-3">
                                    @foreach($consultation->past_medical_history_details as $condition => $value)
                                        @if($value)
                                            <div class="col-md-3">
                                                <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $condition)) }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            @if($consultation->past_medical_history)
                                <div class="mb-4 consultation-content">{!! $consultation->past_medical_history !!}</div>
                            @endif
                            @if($consultation->past_medical_history_others)
                                <div class="mb-4 consultation-content"><strong>Others:</strong> {!! $consultation->past_medical_history_others !!}</div>
                            @endif
                            @endif

                            <!-- Drug History -->
                            <div class="row">
                                @if($consultation->drug_history)
                                <div class="col-md-6">
                                    <h5 class="text-dark mb-3"><i class="bi bi-capsule"></i> Drug History</h5>
                                    <div class="mb-4 consultation-content">{!! $consultation->drug_history !!}</div>
                                </div>
                                @endif
                                @if($consultation->allergy_history)
                                <div class="col-md-6">
                                    <h5 class="text-dark mb-3"><i class="bi bi-exclamation-triangle-fill text-danger"></i> Allergy History</h5>
                                    <div class="mb-4 consultation-content">{!! $consultation->allergy_history !!}</div>
                                </div>
                                @endif
                            </div>

                            <!-- Current Medications & Drug Details -->
                            @if($consultation->current_medications || $consultation->drug_allergies || $consultation->past_drug_usage)
                            <div class="row">
                                @if($consultation->current_medications)
                                <div class="col-md-4">
                                    <h5 class="text-dark mb-3"><i class="bi bi-prescription2"></i> Current Medications</h5>
                                    <div class="mb-4 consultation-content">{!! $consultation->current_medications !!}</div>
                                </div>
                                @endif
                                @if($consultation->drug_allergies)
                                <div class="col-md-4">
                                    <h5 class="text-dark mb-3"><i class="bi bi-exclamation-triangle-fill text-warning"></i> Drug Allergies</h5>
                                    <div class="mb-4 consultation-content">{!! $consultation->drug_allergies !!}</div>
                                </div>
                                @endif
                                @if($consultation->past_drug_usage)
                                <div class="col-md-4">
                                    <h5 class="text-dark mb-3"><i class="bi bi-clock-history"></i> Past Drug Usage</h5>
                                    <div class="mb-4 consultation-content">{!! $consultation->past_drug_usage !!}</div>
                                </div>
                                @endif
                            </div>
                            @endif

                            <!-- Family & Social History -->
                            <div class="row">
                                @if($consultation->family_history)
                                <div class="col-md-6">
                                    <h5 class="text-dark mb-3"><i class="bi bi-people"></i> Family History</h5>
                                    <div class="mb-4 consultation-content">{!! $consultation->family_history !!}</div>
                                </div>
                                @endif
                                @if($consultation->social_history)
                                <div class="col-md-6">
                                    <h5 class="text-dark mb-3"><i class="bi bi-person-lines-fill"></i> Social History</h5>
                                    <div class="mb-4 consultation-content">{!! $consultation->social_history !!}</div>
                                </div>
                                @endif
                            </div>

                            <!-- Social History Details -->
                            @if($consultation->social_history_details)
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="text-dark mb-3"><i class="bi bi-person-gear"></i> Social History Details</h5>
                                    <div class="mb-4 consultation-content">{!! $consultation->social_history_details !!}</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    </div>
                    @endif
                    
                    @if($key === 'examination')
                    <!-- Examination Tab -->
                    <div class="tab-pane fade {{ $tab['active'] ? 'show active' : '' }}" id="{{ $tab['target'] }}" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <!-- Vitals -->
                            @if($consultation->blood_pressure_systolic || $consultation->temperature || $consultation->pulse_rate)
                            <h5 class="text-dark mb-3"><i class="bi bi-heart-pulse"></i> Vital Signs</h5>
                            <div class="row mb-4">
                                @if($consultation->blood_pressure_systolic)
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-1">Blood Pressure</h6>
                                            <h4 class="text-dark mb-1">{{ $consultation->blood_pressure_systolic }}/{{ $consultation->blood_pressure_diastolic }}</h4>
                                            <small class="text-muted">{{ $consultation->blood_pressure_category }}</small>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if($consultation->pulse_rate)
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-1">Pulse Rate</h6>
                                            <h4 class="text-dark mb-1">{{ $consultation->pulse_rate }}</h4>
                                            <small class="text-muted">bpm</small>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if($consultation->temperature)
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-1">Temperature</h6>
                                            <h4 class="text-dark mb-1">{{ $consultation->temperature }}</h4>
                                            <small class="text-muted">°C</small>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if($consultation->respiratory_rate)
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-1">Respiratory Rate</h6>
                                            <h4 class="text-dark mb-1">{{ $consultation->respiratory_rate }}</h4>
                                            <small class="text-muted">breaths/min</small>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if($consultation->oxygen_saturation)
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-1">O2 Saturation</h6>
                                            <h4 class="text-dark mb-1">{{ $consultation->oxygen_saturation }}</h4>
                                            <small class="text-muted">%</small>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if($consultation->height && $consultation->weight)
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted mb-1">BMI</h6>
                                            <h4 class="text-dark mb-1">{{ $consultation->bmi }}</h4>
                                            <small class="text-muted">{{ $consultation->bmi_category }}</small>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endif

                            <!-- Physical Examination -->
                            @if($consultation->physical_examination)
                            <h5 class="text-dark mb-3"><i class="bi bi-clipboard-pulse"></i> Physical Examination</h5>
                            <div class="mb-4 consultation-content">{!! $consultation->physical_examination !!}</div>
                            @endif

                            <!-- Vital Signs -->
                            @if($consultation->vitals)
                            <h5 class="text-dark mb-3"><i class="bi bi-heart-pulse"></i> Vital Signs</h5>
                            <div class="mb-4 consultation-content">{!! $consultation->vitals !!}</div>
                            @endif

                            <!-- General Examination -->
                            @if($consultation->general_examination)
                            <h5 class="text-dark mb-3"><i class="bi bi-eye"></i> General Examination</h5>
                            <div class="mb-4 consultation-content">{!! $consultation->general_examination !!}</div>
                            @endif

                            <!-- System-specific Examinations -->
                            <div class="row">
                                @if($consultation->cardiovascular_examination)
                                <div class="col-md-6 mb-3">
                                    <h5 class="text-dark mb-3"><i class="bi bi-heart"></i> Cardiovascular System</h5>
                                    <div class="mb-4 consultation-content">{!! $consultation->cardiovascular_examination !!}</div>
                                </div>
                                @endif
                                @if($consultation->respiratory_examination)
                                <div class="col-md-6 mb-3">
                                    <h5 class="text-dark mb-3"><i class="bi bi-lungs"></i> Respiratory System</h5>
                                    <div class="mb-4 consultation-content">{!! $consultation->respiratory_examination !!}</div>
                                </div>
                                @endif
                                @if($consultation->abdominal_examination)
                                <div class="col-md-6 mb-3">
                                    <h5 class="text-dark mb-3"><i class="bi bi-body-text"></i> Abdominal Examination</h5>
                                    <div class="mb-4 consultation-content">{!! $consultation->abdominal_examination !!}</div>
                                </div>
                                @endif
                                @if($consultation->neurological_examination)
                                <div class="col-md-6 mb-3">
                                    <h5 class="text-dark mb-3"><i class="bi bi-cpu"></i> Central Nervous System</h5>
                                    <div class="mb-4 consultation-content">{!! $consultation->neurological_examination !!}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    </div>
                    @endif
                    
                    @if($key === 'diagnosis')
                    <!-- Diagnosis & Treatment Tab -->
                    <div class="tab-pane fade {{ $tab['active'] ? 'show active' : '' }}" id="{{ $tab['target'] }}" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            @if($consultation->doctors_impression)
                            <h5 class="text-dark mb-3"><i class="bi bi-clipboard-check"></i> Doctor's Impression/Diagnosis</h5>
                            <div class="mb-4 consultation-content">{!! $consultation->doctors_impression !!}</div>
                            @endif

                            @if($consultation->treatment_plan)
                            <h5 class="text-dark mb-3"><i class="bi bi-clipboard-medical"></i> Treatment Plan</h5>
                            <div class="mb-4 consultation-content">{!! $consultation->treatment_plan !!}</div>
                            @endif

                            @if($consultation->icd_10_code)
                            <h5 class="text-dark mb-3"><i class="bi bi-code-square"></i> ICD-10 Code</h5>
                            <p class="mb-4"><span class="badge bg-info">{{ $consultation->icd_10_code }}</span></p>
                            @endif

                            <div class="row">
                                @if($consultation->medications_prescribed)
                                <div class="col-md-6">
                                    <h5 class="text-dark mb-3"><i class="bi bi-capsule-pill"></i> Medications Prescribed</h5>
                                    <div class="consultation-content">
                                        {!! $consultation->medications_prescribed !!}
                                    </div>
                                </div>
                                @endif
                                @if($consultation->investigations_ordered)
                                <div class="col-md-6">
                                    <h5 class="text-dark mb-3"><i class="bi bi-clipboard-data"></i> Investigations Ordered</h5>
                                    <div class="consultation-content">
                                        {!! $consultation->investigations_ordered !!}
                                    </div>
                                </div>
                                @endif
                            </div>

                            @if($consultation->follow_up_instructions)
                            <h5 class="text-dark mb-3 mt-4"><i class="bi bi-arrow-repeat"></i> Follow-up Instructions</h5>
                            <div class="consultation-content">
                                {!! $consultation->follow_up_instructions !!}
                            </div>
                            @endif
                        </div>
                    </div>
                    </div>
                    @endif
                    
                    @if($key === 'additional')
                    <!-- Additional Complaints Tab -->
                    <div class="tab-pane fade {{ $tab['active'] ? 'show active' : '' }}" id="{{ $tab['target'] }}" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="text-dark mb-3"><i class="bi bi-list-ul"></i> Additional Complaints</h5>
                                <div class="row">
                                    @foreach($consultation->presenting_complaints as $complaint)
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-left-primary">
                                            <div class="card-body">
                                                <h6 class="text-primary">{{ $complaint['text'] }}</h6>
                                                @if($complaint['duration'])
                                                    <p class="mb-0 text-muted"><i class="bi bi-clock"></i> {{ $complaint['duration'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if($key === 'vitals')
                    <!-- Vitals Tab (nurse-recorded at check-in) -->
                    <div class="tab-pane fade {{ $tab['active'] ? 'show active' : '' }}" id="{{ $tab['target'] }}" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="text-dark mb-3"><i class="bi bi-heart-pulse"></i> Vital Signs (recorded at check-in)</h5>
                                @foreach($consultation->vitals as $vital)
                                <div class="card border mb-3">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-clock"></i> Recorded {{ $vital->recorded_at ? $vital->recorded_at->format('M d, Y H:i') : 'N/A' }}</span>
                                        @if($vital->recordedBy)
                                            <span class="text-muted small">By {{ $vital->recordedBy->name ?? ($vital->recordedBy->first_name . ' ' . $vital->recordedBy->last_name) }}</span>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            @if(($vital->blood_pressure_systolic !== null && $vital->blood_pressure_systolic !== '') && ($vital->blood_pressure_diastolic !== null && $vital->blood_pressure_diastolic !== ''))
                                            <div class="col-md-4 col-sm-6">
                                                <div class="bg-light rounded p-3 text-center">
                                                    <div class="text-muted small">Blood Pressure</div>
                                                    <div class="fw-bold">{{ $vital->blood_pressure_systolic }}/{{ $vital->blood_pressure_diastolic }}</div>
                                                    <div class="small">mmHg</div>
                                                </div>
                                            </div>
                                            @endif
                                            @if($vital->pulse_rate !== null && $vital->pulse_rate !== '')
                                            <div class="col-md-4 col-sm-6">
                                                <div class="bg-light rounded p-3 text-center">
                                                    <div class="text-muted small">Pulse Rate</div>
                                                    <div class="fw-bold">{{ $vital->pulse_rate }}</div>
                                                    <div class="small">bpm</div>
                                                </div>
                                            </div>
                                            @endif
                                            @if($vital->temperature !== null && $vital->temperature !== '')
                                            <div class="col-md-4 col-sm-6">
                                                <div class="bg-light rounded p-3 text-center">
                                                    <div class="text-muted small">Temperature</div>
                                                    <div class="fw-bold">{{ is_numeric($vital->temperature) ? number_format((float)$vital->temperature, 1) : $vital->temperature }}</div>
                                                    <div class="small">°C</div>
                                                </div>
                                            </div>
                                            @endif
                                            @if($vital->respiratory_rate !== null && $vital->respiratory_rate !== '')
                                            <div class="col-md-4 col-sm-6">
                                                <div class="bg-light rounded p-3 text-center">
                                                    <div class="text-muted small">Respiratory Rate</div>
                                                    <div class="fw-bold">{{ $vital->respiratory_rate }}</div>
                                                    <div class="small">/min</div>
                                                </div>
                                            </div>
                                            @endif
                                            @if($vital->oxygen_saturation !== null && $vital->oxygen_saturation !== '')
                                            <div class="col-md-4 col-sm-6">
                                                <div class="bg-light rounded p-3 text-center">
                                                    <div class="text-muted small">Oxygen Saturation</div>
                                                    <div class="fw-bold">{{ $vital->oxygen_saturation }}</div>
                                                    <div class="small">%</div>
                                                </div>
                                            </div>
                                            @endif
                                            @if($vital->height !== null && $vital->height !== '')
                                            <div class="col-md-4 col-sm-6">
                                                <div class="bg-light rounded p-3 text-center">
                                                    <div class="text-muted small">Height</div>
                                                    <div class="fw-bold">{{ is_numeric($vital->height) ? number_format((float)$vital->height, 1) : $vital->height }}</div>
                                                    <div class="small">cm</div>
                                                </div>
                                            </div>
                                            @endif
                                            @if($vital->weight !== null && $vital->weight !== '')
                                            <div class="col-md-4 col-sm-6">
                                                <div class="bg-light rounded p-3 text-center">
                                                    <div class="text-muted small">Weight</div>
                                                    <div class="fw-bold">{{ is_numeric($vital->weight) ? number_format((float)$vital->weight, 1) : $vital->weight }}</div>
                                                    <div class="small">kg</div>
                                                </div>
                                            </div>
                                            @endif
                                            @if($vital->bmi !== null && $vital->bmi !== '')
                                            <div class="col-md-4 col-sm-6">
                                                <div class="bg-light rounded p-3 text-center">
                                                    <div class="text-muted small">BMI</div>
                                                    <div class="fw-bold">{{ is_numeric($vital->bmi) ? number_format((float)$vital->bmi, 1) : $vital->bmi }}</div>
                                                    <div class="small">kg/m²</div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if($key === 'diagnoses')
                    <!-- Diagnoses Tab -->
                    <div class="tab-pane fade {{ $tab['active'] ? 'show active' : '' }}" id="{{ $tab['target'] }}" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="text-dark mb-3"><i class="bi bi-clipboard-medical"></i> Diagnoses</h5>
                                @foreach($consultation->consultationDiagnoses as $diagnosis)
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <div class="card border-left-info">
                                            <div class="card-body">
                                                <h6 class="text-info">{{ $diagnosis->diagnosis_description }}</h6>
                                                @if($diagnosis->icd_code)
                                                    <p class="mb-1"><strong>ICD Code:</strong> <span class="badge bg-info">{{ $diagnosis->icd_code }}</span></p>
                                                @endif
                                                @if($diagnosis->diagnosis_type)
                                                    <p class="mb-1"><strong>Type:</strong> {{ ucfirst($diagnosis->diagnosis_type) }}</p>
                                                @endif
                                                @if($diagnosis->confidence_level)
                                                    <p class="mb-0"><strong>Confidence:</strong> {{ ucfirst($diagnosis->confidence_level) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if($key === 'interventions')
                    <!-- Interventions Tab -->
                    <div class="tab-pane fade {{ $tab['active'] ? 'show active' : '' }}" id="{{ $tab['target'] }}" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="text-dark mb-3"><i class="bi bi-tools"></i> Interventions</h5>
                                @foreach($consultation->interventions as $intervention)
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <div class="card border-left-success">
                                            <div class="card-body">
                                                <h6 class="text-success">{{ ucfirst($intervention->intervention_type) }}</h6>
                                                <p class="mb-1"><strong>Description:</strong> {{ $intervention->description }}</p>
                                                @if($intervention->medication_id)
                                                    <p class="mb-1"><strong>Medication:</strong> {{ $intervention->medication->name ?? 'Unknown' }}</p>
                                                @endif
                                                @if($intervention->dosage_instructions)
                                                    <p class="mb-1"><strong>Dosage:</strong> {{ $intervention->dosage_instructions }}</p>
                                                @endif
                                                @if($intervention->frequency)
                                                    <p class="mb-1"><strong>Frequency:</strong> {{ $intervention->frequency }}</p>
                                                @endif
                                                @if($intervention->duration)
                                                    <p class="mb-0"><strong>Duration:</strong> {{ $intervention->duration }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if($key === 'overview')
                    <!-- Overview Tab (Default when no data) -->
                    <div class="tab-pane fade {{ $tab['active'] ? 'show active' : '' }}" id="{{ $tab['target'] }}" role="tabpanel">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="py-5">
                                    <i class="bi bi-info-circle display-1 text-muted"></i>
                                    <h4 class="text-muted mt-3">Consultation Overview</h4>
                                    <p class="text-muted">This consultation is currently in progress. More details will be added as the consultation progresses.</p>
                                    @if($consultation->chief_complaint)
                                        <div class="mt-4">
                                            <h6 class="text-dark">Chief Complaint:</h6>
                                            <p class="text-muted">{{ $consultation->chief_complaint }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

@if($consultation->is_draft || $consultation->consultation_status === 'ongoing')
<!-- Intelligent Completion Modal -->
<div class="modal fade" id="completeConsultationModal" tabindex="-1" aria-labelledby="completeConsultationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="completeConsultationModalLabel">
                    <i class="bi bi-check-circle"></i> Complete Consultation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Review Status:</strong> Please review pending items before completing the consultation.
                </div>

                <!-- Consultation Summary -->
                <h6 class="mb-3"><i class="bi bi-clipboard-check"></i> Consultation Summary</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Patient:</strong> {{ $consultation->patient->full_name }}</p>
                        <p class="mb-1"><strong>Doctor:</strong> Dr. {{ $consultation->doctor->first_name }} {{ $consultation->doctor->last_name }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Date:</strong> {{ $consultation->consultation_date->format('M d, Y') }}</p>
                        <p class="mb-1"><strong>Type:</strong> {{ ucfirst($consultation->consultation_type) }}</p>
                    </div>
                </div>

                <hr>

                <!-- Pending Items Check -->
                <h6 class="mb-3"><i class="bi bi-list-check"></i> Pending Items Status</h6>
                
                @php
                    $prescriptions = $consultation->prescriptions ?? collect();
                    $labOrders = $consultation->labRequests ?? \App\Models\LabRequest::where('consultation_id', $consultation->id)->get();
                    $radiologyOrders = \App\Models\RadiologyRequest::where('consultation_id', $consultation->id)->get();
                    
                    $pendingPrescriptions = $prescriptions->where('status', '!=', 'dispensed')->count();
                    $pendingLabOrders = $labOrders->whereIn('status', ['pending', 'in_progress'])->count();
                    $pendingRadiology = $radiologyOrders->whereIn('status', ['scheduled', 'in_progress'])->count();
                    
                    $hasPendingItems = $pendingPrescriptions > 0 || $pendingLabOrders > 0 || $pendingRadiology > 0;
                @endphp

                <div class="list-group mb-3">
                    <!-- Prescriptions -->
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-capsule me-2"></i>
                                <strong>Prescriptions:</strong> {{ $prescriptions->count() }} total
                            </div>
                            <div>
                                @if($pendingPrescriptions > 0)
                                    <span class="badge bg-warning">{{ $pendingPrescriptions }} Pending</span>
                                @else
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Complete</span>
                                @endif
                            </div>
                        </div>
                        @if($prescriptions->count() > 0)
                            <small class="text-muted d-block mt-2">
                                @foreach($prescriptions as $rx)
                                    @if($rx->orders && $rx->orders->count() > 0)
                                        @foreach($rx->orders as $order)
                                            <div class="ms-4">• {{ $order->drug->name ?? 'Unknown Drug' }} 
                                                (Qty: {{ $order->quantity }}, Status: 
                                                <span class="badge badge-sm bg-{{ $order->status === 'dispensed' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($order->status) }}
                                                </span>)
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="ms-4">• Prescription #{{ $rx->prescription_number ?? $rx->id }}: 
                                            <span class="badge badge-sm bg-{{ $rx->status === 'dispensed' ? 'success' : 'warning' }}">
                                                {{ ucfirst($rx->status) }}
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                            </small>
                        @endif
                    </div>

                    <!-- Lab Orders -->
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-flask me-2"></i>
                                <strong>Lab Tests:</strong> {{ $labOrders->count() }} total
                            </div>
                            <div>
                                @if($pendingLabOrders > 0)
                                    <span class="badge bg-warning">{{ $pendingLabOrders }} Pending</span>
                                @else
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Complete</span>
                                @endif
                            </div>
                        </div>
                        @if($labOrders->count() > 0)
                            <small class="text-muted d-block mt-2">
                                @foreach($labOrders as $lab)
                                    <div class="ms-4">• {{ $lab->test_type_name ?? $lab->test_type }}: 
                                        <span class="badge badge-sm bg-{{ $lab->status === 'completed' ? 'success' : ($lab->status === 'in_progress' ? 'info' : 'warning') }}">
                                            {{ ucfirst(str_replace('_', ' ', $lab->status)) }}
                                        </span>
                                    </div>
                                @endforeach
                            </small>
                        @endif
                    </div>

                    <!-- Radiology Orders -->
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-x-ray me-2"></i>
                                <strong>Radiology:</strong> {{ $radiologyOrders->count() }} total
                            </div>
                            <div>
                                @if($radiologyOrders->count() > 0)
                                    @if($pendingRadiology > 0)
                                        <span class="badge bg-warning">{{ $pendingRadiology }} Pending</span>
                                    @else
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Complete</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @if($radiologyOrders->count() > 0)
                            <small class="text-muted d-block mt-2">
                                @foreach($radiologyOrders as $rad)
                                    <div class="ms-4">• {{ $rad->modality->name ?? 'Unknown' }} ({{ $rad->request_number }}): 
                                        <span class="badge badge-sm bg-{{ $rad->status === 'completed' ? 'success' : ($rad->status === 'in_progress' ? 'info' : 'warning') }}">
                                            {{ ucfirst(str_replace('_', ' ', $rad->status)) }}
                                        </span>
                                    </div>
                                @endforeach
                            </small>
                        @endif
                        @can('create_radiology_requests')
                            <div class="mt-2">
                                <a href="{{ route('consultations.radiology.create', $consultation) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus-circle"></i> Request Imaging
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>

                @if($hasPendingItems)
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>Warning:</strong> There are pending items. The patient may need to:
                        <ul class="mb-0 mt-2">
                            @if($pendingLabOrders > 0)
                                <li>Complete {{ $pendingLabOrders }} lab test(s)</li>
                            @endif
                            @if($pendingPrescriptions > 0)
                                <li>Collect {{ $pendingPrescriptions }} prescription(s) from pharmacy</li>
                            @endif
                            @if($pendingRadiology > 0)
                                <li>Complete {{ $pendingRadiology }} radiology study/studies</li>
                            @endif
                        </ul>
                    </div>
                @else
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> 
                        <strong>All Clear!</strong> All orders have been processed. Safe to complete consultation.
                    </div>
                @endif

                <!-- Completion Options -->
                <hr>
                <h6 class="mb-3"><i class="bi bi-gear"></i> Completion Options</h6>
                
                <form id="completeForm" action="{{ route('consultations.update', $consultation) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="consultation_status" value="completed">
                    <input type="hidden" name="is_draft" value="0">
                    <input type="hidden" name="from_queue" value="{{ str_contains(request()->header('referer', ''), 'doctor-queue') ? '1' : '0' }}">
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="completion_type" id="completeNow" value="complete_now" checked>
                        <label class="form-check-label" for="completeNow">
                            <strong>Complete Now</strong>
                            <small class="d-block text-muted">Mark consultation as complete regardless of pending items</small>
                        </label>
                    </div>
                    
                    @if($hasPendingItems)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="completion_type" id="completeWithPending" value="complete_with_pending">
                        <label class="form-check-label" for="completeWithPending">
                            <strong>Complete with Pending Items</strong>
                            <small class="d-block text-muted">Patient will continue with pending orders after consultation</small>
                        </label>
                    </div>
                    @endif
                    
                    <div class="mb-3 mt-3">
                        <label for="completion_notes" class="form-label">Completion Notes (Optional)</label>
                        <textarea class="form-control" id="completion_notes" name="completion_notes" rows="2" 
                                  placeholder="Add any final notes about this consultation..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" onclick="submitCompletionForm()">
                    <i class="bi bi-check-circle"></i> Complete Consultation
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function markAsCompleted() {
    // Show the intelligent completion modal
    const modal = new bootstrap.Modal(document.getElementById('completeConsultationModal'));
    modal.show();
}

function submitCompletionForm() {
    // Confirm and submit
    const completionType = document.querySelector('input[name="completion_type"]:checked').value;
    let confirmMessage = 'Are you sure you want to complete this consultation?';
    
    if (completionType === 'complete_with_pending') {
        confirmMessage = 'Complete consultation with pending items? The patient will need to complete these items.';
    }
    
    if (confirm(confirmMessage)) {
        document.getElementById('completeForm').submit();
    }
}
</script>

@endif
@endsection