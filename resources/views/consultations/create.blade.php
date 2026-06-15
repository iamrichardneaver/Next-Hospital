@extends('layouts.app')

@section('title', 'New Consultation')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
        <div>
                <h1 class="page-title">New Consultation</h1>
                <p class="page-subtitle">Start a comprehensive patient consultation</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('consultations.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Consultations
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('consultations.store') }}" method="POST" id="consultationForm">
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
                                        @if($visit->patient)
                                            <option value="{{ $visit->id }}" data-patient-id="{{ $visit->patient_id }}" {{ old('visit_id') == $visit->id ? 'selected' : '' }}>
                                                {{ $visit->visit_token }} - {{ $visit->patient->full_name }} ({{ $visit->patient->patient_number }})
                                            </option>
                                        @endif
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
                                            {{ $patient->patient_number }} - {{ $patient->full_name }} ({{ $patient->age }} yrs, {{ $patient->gender }})
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

        <!-- Consultation Details Section -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-pulse"></i> Consultation Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                                <label for="doctor_id" class="form-label">Attending Doctor <span class="text-danger">*</span></label>
                                @if(auth()->user()->hasRole('doctor'))
                                    {{-- Doctors can only create for themselves --}}
                                    <input type="text" class="form-control" value="Dr. {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}" disabled>
                                    <input type="hidden" name="doctor_id" value="{{ auth()->id() }}">
                                    <small class="form-text text-muted">You can only create consultations for yourself</small>
                                @else
                                    <select class="form-select @error('doctor_id') is-invalid @enderror" id="doctor_id" name="doctor_id" required>
                                        <option value="">Select Doctor</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}" {{ old('doctor_id', auth()->id()) == $doctor->id ? 'selected' : '' }}>
                                        Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('doctor_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </div>
                    <div class="col-md-3 mb-3">
                                <label for="consultation_type" class="form-label">Consultation Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('consultation_type') is-invalid @enderror" id="consultation_type" name="consultation_type" required>
                                    <option value="">Select Type</option>
                                    <option value="in-person" {{ old('consultation_type', 'in-person') == 'in-person' ? 'selected' : '' }}>In-Person</option>
                                    <option value="teleconsultation" {{ old('consultation_type') == 'teleconsultation' ? 'selected' : '' }}>Teleconsultation</option>
                                </select>
                                @error('consultation_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="consultation_date" class="form-label">Consultation Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('consultation_date') is-invalid @enderror" 
                               id="consultation_date" name="consultation_date" 
                               value="{{ old('consultation_date', now()->toDateString()) }}" required>
                        @error('consultation_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="template_id" class="form-label">Consultation Template (Optional)</label>
                        <select class="form-select @error('template_id') is-invalid @enderror" id="template_id" name="template_id">
                            <option value="">Select Template</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                    {{ $template->name }} ({{ $template->specialty }})
                                </option>
                            @endforeach
                        </select>
                        @error('template_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-secondary">Choose a template to pre-fill common fields</small>
                    </div>
                </div>
            </div>
                            </div>

        <!-- Presenting Complaints Section - Only for Doctors -->
        @can('edit_consultations')
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Presenting Complaints</h5>
            </div>
            <div class="card-body">
                            <div class="mb-3">
                                <label for="chief_complaint" class="form-label">Chief Complaint <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('chief_complaint') is-invalid @enderror" 
                              id="chief_complaint" name="chief_complaint" rows="3" required 
                              placeholder="What brings the patient in today?">{{ old('chief_complaint') }}</textarea>
                                @error('chief_complaint')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                </div>
                
                <!-- Multiple Complaints Section -->
                <div class="mb-3">
                    <label class="form-label">Additional Complaints</label>
                    <div id="complaints-container">
                        <div class="row complaint-row mb-2">
                            <div class="col-md-6">
                                <input type="text" class="form-control complaint-text" placeholder="Complaint (e.g., cough)" name="complaints[0][text]">
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control complaint-duration" placeholder="Duration (e.g., 2 weeks)" name="complaints[0][duration]">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-complaint" style="display: none;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-complaint">
                        <i class="bi bi-plus"></i> Add Complaint
                    </button>
                </div>
            </div>
        </div>
        @endcan

        <!-- History Section -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Medical History</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="history_of_present_illness" class="form-label">History of Presenting Illness</label>
                        <textarea class="form-control @error('history_of_present_illness') is-invalid @enderror" 
                                  id="history_of_present_illness" name="history_of_present_illness" rows="4" 
                                  placeholder="Detailed narrative of the current illness">{{ old('history_of_present_illness') }}</textarea>
                        @error('history_of_present_illness')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="on_direct_questioning" class="form-label">On Direct Questioning</label>
                        <textarea class="form-control @error('on_direct_questioning') is-invalid @enderror" 
                                  id="on_direct_questioning" name="on_direct_questioning" rows="4" 
                                  placeholder="Additional findings from direct questioning">{{ old('on_direct_questioning') }}</textarea>
                        @error('on_direct_questioning')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                            </div>
                        </div>

                <!-- Past Medical History -->
                <div class="mb-3">
                    <label class="form-label">Past Medical History</label>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="past_medical_history_details[hypertension]" id="hypertension" value="1">
                                <label class="form-check-label" for="hypertension">Hypertension</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="past_medical_history_details[diabetes]" id="diabetes" value="1">
                                <label class="form-check-label" for="diabetes">Diabetes</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="past_medical_history_details[asthma]" id="asthma" value="1">
                                <label class="form-check-label" for="asthma">Asthma</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="past_medical_history_details[tuberculosis]" id="tuberculosis" value="1">
                                <label class="form-check-label" for="tuberculosis">Tuberculosis</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <textarea class="form-control" name="past_medical_history_others" rows="2" 
                                  placeholder="Other medical conditions..."></textarea>
                    </div>
                </div>

                <!-- Drug History -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="drug_history" class="form-label">Drug History</label>
                        <textarea class="form-control @error('drug_history') is-invalid @enderror" 
                                  id="drug_history" name="drug_history" rows="3" 
                                  placeholder="Current medications and past drug usage">{{ old('drug_history') }}</textarea>
                        @error('drug_history')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="allergy_history" class="form-label">Allergy History</label>
                        <textarea class="form-control @error('allergy_history') is-invalid @enderror" 
                                  id="allergy_history" name="allergy_history" rows="3" 
                                  placeholder="Drug allergies and reactions">{{ old('allergy_history') }}</textarea>
                        @error('allergy_history')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Family & Social History -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="family_history" class="form-label">Family History</label>
                        <textarea class="form-control @error('family_history') is-invalid @enderror" 
                                  id="family_history" name="family_history" rows="3" 
                                  placeholder="Family medical history (diabetes, hypertension, genetic diseases)">{{ old('family_history') }}</textarea>
                        @error('family_history')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="social_history" class="form-label">Social History</label>
                        <textarea class="form-control @error('social_history') is-invalid @enderror" 
                                  id="social_history" name="social_history" rows="3" 
                                  placeholder="Smoking, alcohol, occupation, lifestyle">{{ old('social_history') }}</textarea>
                        @error('social_history')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
                        </div>

        <!-- Physical Examination Section -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-pulse"></i> Physical Examination</h5>
            </div>
            <div class="card-body">
                <!-- Vitals - Editable for Nurses, Read-only for Doctors -->
                <div class="mb-4">
                    <h6 class="text-dark mb-3">Vital Signs</h6>
                    @can('record_vitals')
                        <!-- Nurse can record and edit vitals -->
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label for="blood_pressure_systolic" class="form-label">BP Systolic</label>
                            <input type="number" class="form-control" id="blood_pressure_systolic" 
                                   name="blood_pressure_systolic" placeholder="120" step="1">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="blood_pressure_diastolic" class="form-label">BP Diastolic</label>
                            <input type="number" class="form-control" id="blood_pressure_diastolic" 
                                   name="blood_pressure_diastolic" placeholder="80" step="1">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="pulse_rate" class="form-label">Pulse (bpm)</label>
                            <input type="number" class="form-control" id="pulse_rate" 
                                   name="pulse_rate" placeholder="72" step="1">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="temperature" class="form-label">Temperature (°C)</label>
                            <input type="number" class="form-control" id="temperature" 
                                   name="temperature" placeholder="36.5" step="0.1">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="respiratory_rate" class="form-label">Resp Rate</label>
                            <input type="number" class="form-control" id="respiratory_rate" 
                                   name="respiratory_rate" placeholder="16" step="1">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="oxygen_saturation" class="form-label">O2 Sat (%)</label>
                            <input type="number" class="form-control" id="oxygen_saturation" 
                                   name="oxygen_saturation" placeholder="98" step="1" min="0" max="100">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="height" class="form-label">Height (cm)</label>
                            <input type="number" class="form-control" id="height" 
                                   name="height" placeholder="170" step="0.1">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control" id="weight" 
                                   name="weight" placeholder="70" step="0.1">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="bmi" class="form-label">BMI</label>
                            <input type="number" class="form-control" id="bmi" 
                                   name="bmi" placeholder="24.2" step="0.1" readonly>
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="calculate-bmi">
                                Calculate BMI
                            </button>
                        </div>
                    </div>
                </div>
                    @else
                        <!-- Doctor can only view vitals (read-only) -->
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> Only nurses can record vital signs. Please have a nurse record the patient's vitals before the consultation.
                        </div>
                    @endcan

                <!-- General Examination -->
                <div class="mb-3">
                    <label for="general_examination" class="form-label">General Examination</label>
                    <textarea class="form-control @error('general_examination') is-invalid @enderror" 
                              id="general_examination" name="general_examination" rows="3" 
                              placeholder="General appearance, pallor, jaundice, etc.">{{ old('general_examination') }}</textarea>
                    @error('general_examination')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- System-specific Examinations -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cardiovascular_examination" class="form-label">Cardiovascular System</label>
                        <textarea class="form-control" id="cardiovascular_examination" 
                                  name="cardiovascular_examination" rows="3" 
                                  placeholder="Heart sounds, murmurs, etc."></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="respiratory_examination" class="form-label">Respiratory System</label>
                        <textarea class="form-control" id="respiratory_examination" 
                                  name="respiratory_examination" rows="3" 
                                  placeholder="Breath sounds, added sounds, etc."></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="abdominal_examination" class="form-label">Abdominal Examination</label>
                        <textarea class="form-control" id="abdominal_examination" 
                                  name="abdominal_examination" rows="3" 
                                  placeholder="Liver, spleen, tenderness, masses, bowel sounds"></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="neurological_examination" class="form-label">Central Nervous System</label>
                        <textarea class="form-control" id="neurological_examination" 
                                  name="neurological_examination" rows="3" 
                                  placeholder="Mental status, reflexes, cranial nerves, motor/sensory exam"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Diagnosis & Treatment Section -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Diagnosis & Treatment</h5>
            </div>
            <div class="card-body">
                <!-- Diagnosis Section -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="doctors_impression" class="form-label">Doctor's Impression/Diagnosis <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('doctors_impression') is-invalid @enderror" 
                                  id="doctors_impression" name="doctors_impression" rows="3" 
                                  placeholder="Clinical impression and diagnosis">{{ old('doctors_impression') }}</textarea>
                        @error('doctors_impression')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="icd_10_code" class="form-label">ICD-10 Code</label>
                        <input type="text" class="form-control @error('icd_10_code') is-invalid @enderror" 
                               id="icd_10_code" name="icd_10_code" 
                               placeholder="e.g., I10 for hypertension" value="{{ old('icd_10_code') }}">
                        @error('icd_10_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Prescription Orders Section -->
                <div class="mb-4">
                    <h6 class="text-dark mb-3"><i class="bi bi-capsule"></i> Prescription Orders</h6>
                    <div id="prescription-orders-container">
                        <div class="row prescription-order-row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Drug/Medication</label>
                                <select class="form-select drug-select" name="prescription_orders[0][drug_id]">
                                    <option value="">Select Drug (optional)</option>
                                    @foreach(\App\Models\Drug::active()->with('stocks')->get() as $drug)
                                        @php
                                            $currentStock = $drug->getCurrentStock();
                                        @endphp
                                        <option value="{{ $drug->id }}" data-price="{{ $drug->selling_price }}" data-stock="{{ $currentStock }}">
                                            {{ $drug->name }} ({{ $drug->dosage_form }}) - Stock: {{ $currentStock }} {{ $currentStock == 0 ? '❌' : ($currentStock < 50 ? '⚠️' : '✅') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control quantity-input" name="prescription_orders[0][quantity]" min="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Dosage Instructions</label>
                                <input type="text" class="form-control" name="prescription_orders[0][dosage_instructions]" 
                                       placeholder="e.g., 1 tablet twice daily">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Duration</label>
                                <input type="text" class="form-control" name="prescription_orders[0][duration]" 
                                       placeholder="e.g., 7 days">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-prescription" style="display: none;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-prescription">
                        <i class="bi bi-plus"></i> Add Medication
                    </button>
                </div>

                <!-- Lab Test Orders Section -->
                <div class="mb-4">
                    <h6 class="text-dark mb-3"><i class="bi bi-flask"></i> Laboratory Orders</h6>
                    <div id="lab-orders-container">
                        <div class="row lab-order-row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Test Category</label>
                                <select class="form-select test-category-select" name="lab_orders[0][category_id]">
                                    <option value="">Select Category (optional)</option>
                                    @foreach(\App\Models\LabTestCategory::active()->ordered()->get() as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Test Type</label>
                                <select class="form-select test-type-select" name="lab_orders[0][test_type_id]" disabled>
                                    <option value="">Select category first</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Priority</label>
                                <select class="form-select priority-select" name="lab_orders[0][priority]">
                                    <option value="routine">Routine</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="stat">STAT</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Specimen Type</label>
                                <input type="text" class="form-control specimen-type-display" 
                                       name="lab_orders[0][specimen_type]" readonly 
                                       placeholder="Auto-filled from test type">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-lab-order" style="display: none;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Collection Instructions (Auto-populated) -->
                        <div class="row lab-instructions-row mb-3" style="display: none;">
                            <div class="col-md-6">
                                <label class="form-label">Collection Instructions</label>
                                <textarea class="form-control collection-instructions" rows="2" readonly></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preparation Instructions</label>
                                <textarea class="form-control preparation-instructions" rows="2" readonly></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-lab-order">
                        <i class="bi bi-plus"></i> Add Lab Test
                    </button>
                </div>

                <!-- Radiology/Imaging Orders Section -->
                <div class="mb-4">
                    <h6 class="text-dark mb-3"><i class="bi bi-camera-reels"></i> Radiology/Imaging Orders</h6>
                    <p class="text-muted small mb-3">Add radiology/imaging requests that will be queued for the radiology department on completion.</p>
                    <div id="radiology-orders-container">
                        <div class="row radiology-order-row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Imaging Modality</label>
                                <select class="form-select modality-select" name="radiology_orders[0][modality_id]">
                                    <option value="">Select Modality</option>
                                    @foreach(\App\Models\ImagingModality::where('is_active', true)->orderBy('name')->get() as $modality)
                                        <option value="{{ $modality->id }}">{{ $modality->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Department</label>
                                <select class="form-select department-select" name="radiology_orders[0][department_id]">
                                    <option value="">Select Department</option>
                                    @foreach(\App\Models\RadiologyDepartment::where('is_active', true)->orderBy('name')->get() as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="radiology_orders[0][priority]">
                                    <option value="routine">Routine</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="stat">Stat</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Clinical Question</label>
                                <input type="text" class="form-control" name="radiology_orders[0][clinical_question]" placeholder="e.g., Rule out pneumonia">
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label">Clinical History</label>
                                <textarea class="form-control" name="radiology_orders[0][clinical_history]" rows="2" placeholder="Brief clinical history relevant to this imaging request..."></textarea>
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label">Indication</label>
                                <textarea class="form-control" name="radiology_orders[0][indication]" rows="2" placeholder="Specific indication for this imaging study..."></textarea>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label">Scheduled Date (Optional)</label>
                                <input type="date" class="form-control" name="radiology_orders[0][scheduled_date]" min="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label">Scheduled Time (Optional)</label>
                                <input type="time" class="form-control" name="radiology_orders[0][scheduled_time]">
                            </div>
                            <div class="col-md-1 d-flex align-items-end mt-2">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-radiology-order" style="display: none;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-radiology-order">
                        <i class="bi bi-plus"></i> Add Radiology/Imaging Request
                    </button>
                </div>

                <!-- Clinical Notes -->
                <div class="mb-3">
                    <label for="clinical_notes" class="form-label">Clinical Notes</label>
                    <textarea class="form-control" id="clinical_notes" 
                              name="clinical_notes" rows="3" 
                              placeholder="Additional clinical notes and observations"></textarea>
                </div>

                <!-- Follow-up Instructions -->
                <div class="mb-3">
                    <label for="follow_up_instructions" class="form-label">Follow-up Instructions</label>
                    <textarea class="form-control" id="follow_up_instructions" 
                              name="follow_up_instructions" rows="3" 
                              placeholder="Instructions for patient follow-up"></textarea>
        </div>
    </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <button type="button" class="btn btn-outline-warning" id="save-draft">
                    <i class="bi bi-file-earmark-text"></i> Save as Draft
                </button>
            </div>
            <div>
                <a href="{{ route('consultations.index') }}" class="btn btn-secondary me-2">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Complete Consultation
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
// Auto-select patient when visit is selected
document.getElementById('visit_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const patientId = selectedOption.getAttribute('data-patient-id');
    if (patientId) {
        document.getElementById('patient_id').value = patientId;
    }
    });

    // Add/Remove complaints
    let complaintIndex = 1;
    const addComplaintBtn = document.getElementById('add-complaint');
    if (addComplaintBtn) addComplaintBtn.addEventListener('click', function() {
        const container = document.getElementById('complaints-container');
        const newRow = document.createElement('div');
        newRow.className = 'row complaint-row mb-2';
        newRow.innerHTML = `
            <div class="col-md-6">
                <input type="text" class="form-control complaint-text" placeholder="Complaint" name="complaints[${complaintIndex}][text]">
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control complaint-duration" placeholder="Duration" name="complaints[${complaintIndex}][duration]">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm remove-complaint">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(newRow);
        complaintIndex++;
        updateComplaintRemoveButtons();
    });

    // Remove complaint
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-complaint')) {
            e.target.closest('.complaint-row').remove();
            updateComplaintRemoveButtons();
        }
    });

    function updateComplaintRemoveButtons() {
        const rows = document.querySelectorAll('.complaint-row');
        rows.forEach((row, index) => {
            const removeBtn = row.querySelector('.remove-complaint');
            removeBtn.style.display = rows.length > 1 ? 'block' : 'none';
        });
    }

    // Add/Remove prescription orders
    let prescriptionIndex = 1;
    document.getElementById('add-prescription').addEventListener('click', function() {
        const container = document.getElementById('prescription-orders-container');
        const newRow = document.createElement('div');
        newRow.className = 'row prescription-order-row mb-3';
        newRow.innerHTML = `
            <div class="col-md-4">
                <label class="form-label">Drug/Medication</label>
                <select class="form-select drug-select" name="prescription_orders[${prescriptionIndex}][drug_id]">
                    <option value="">Select Drug (optional)</option>
                    @foreach(\App\Models\Drug::active()->with('stocks')->get() as $drug)
                        @php
                            $currentStock = $drug->getCurrentStock();
                        @endphp
                        <option value="{{ $drug->id }}" data-price="{{ $drug->selling_price }}" data-stock="{{ $currentStock }}">
                            {{ $drug->name }} ({{ $drug->dosage_form }}) - Stock: {{ $currentStock }} {{ $currentStock == 0 ? '❌' : ($currentStock < 50 ? '⚠️' : '✅') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-control quantity-input" name="prescription_orders[${prescriptionIndex}][quantity]" min="1">
            </div>
            <div class="col-md-3">
                <label class="form-label">Dosage Instructions</label>
                <input type="text" class="form-control" name="prescription_orders[${prescriptionIndex}][dosage_instructions]" 
                       placeholder="e.g., 1 tablet twice daily">
            </div>
            <div class="col-md-2">
                <label class="form-label">Duration</label>
                <input type="text" class="form-control" name="prescription_orders[${prescriptionIndex}][duration]" 
                       placeholder="e.g., 7 days">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger btn-sm remove-prescription">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(newRow);
        prescriptionIndex++;
        updatePrescriptionRemoveButtons();
    });

    // Remove prescription order
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-prescription')) {
            e.target.closest('.prescription-order-row').remove();
            updatePrescriptionRemoveButtons();
        }
    });

    // Stock validation for prescription orders
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('drug-select')) {
            const row = e.target.closest('.prescription-order-row');
            const quantityInput = row.querySelector('.quantity-input');
            const stock = parseInt(e.target.selectedOptions[0]?.dataset.stock || 0);
            
            // Update quantity input max value
            quantityInput.max = stock;
            
            // Show stock warning if low
            let stockWarning = row.querySelector('.stock-warning');
            if (!stockWarning) {
                stockWarning = document.createElement('div');
                stockWarning.className = 'stock-warning mt-1';
                row.querySelector('.col-md-4').appendChild(stockWarning);
            }
            
            if (stock === 0) {
                stockWarning.innerHTML = '<small class="text-danger">❌ Out of Stock</small>';
                quantityInput.disabled = true;
            } else if (stock < 50) {
                stockWarning.innerHTML = '<small class="text-warning">⚠️ Low Stock (' + stock + ' units)</small>';
                quantityInput.disabled = false;
            } else {
                stockWarning.innerHTML = '<small class="text-success">✅ In Stock (' + stock + ' units)</small>';
                quantityInput.disabled = false;
            }
        }
        
        if (e.target.classList.contains('quantity-input')) {
            const row = e.target.closest('.prescription-order-row');
            const drugSelect = row.querySelector('.drug-select');
            const stock = parseInt(drugSelect.selectedOptions[0]?.dataset.stock || 0);
            const quantity = parseInt(e.target.value || 0);
            
            if (quantity > stock) {
                e.target.setCustomValidity('Quantity cannot exceed available stock (' + stock + ' units)');
                e.target.reportValidity();
            } else {
                e.target.setCustomValidity('');
            }
        }
    });

    function updatePrescriptionRemoveButtons() {
        const rows = document.querySelectorAll('.prescription-order-row');
        rows.forEach((row, index) => {
            const removeBtn = row.querySelector('.remove-prescription');
            removeBtn.style.display = rows.length > 1 ? 'block' : 'none';
        });
    }

    // Add/Remove lab orders
    let labOrderIndex = 1;
    let radiologyOrderIndex = 1;
    document.getElementById('add-lab-order').addEventListener('click', function() {
        const container = document.getElementById('lab-orders-container');
        const newRow = document.createElement('div');
        newRow.className = 'row lab-order-row mb-3';
        newRow.innerHTML = `
            <div class="col-md-3">
                <label class="form-label">Test Category</label>
                <select class="form-select test-category-select" name="lab_orders[${labOrderIndex}][category_id]">
                    <option value="">Select Category (optional)</option>
                    @foreach(\App\Models\LabTestCategory::active()->ordered()->get() as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Test Type</label>
                <select class="form-select test-type-select" name="lab_orders[${labOrderIndex}][test_type_id]" disabled>
                    <option value="">Select category first</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Priority</label>
                <select class="form-select priority-select" name="lab_orders[${labOrderIndex}][priority]">
                    <option value="routine">Routine</option>
                    <option value="urgent">Urgent</option>
                    <option value="stat">STAT</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Specimen Type</label>
                <input type="text" class="form-control specimen-type-display" 
                       name="lab_orders[${labOrderIndex}][specimen_type]" readonly 
                       placeholder="Auto-filled from test type">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger btn-sm remove-lab-order">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        
        // Add instructions row
        const instructionsRow = document.createElement('div');
        instructionsRow.className = 'row lab-instructions-row mb-3';
        instructionsRow.style.display = 'none';
        instructionsRow.innerHTML = `
            <div class="col-md-6">
                <label class="form-label">Collection Instructions</label>
                <textarea class="form-control collection-instructions" rows="2" readonly></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Preparation Instructions</label>
                <textarea class="form-control preparation-instructions" rows="2" readonly></textarea>
            </div>
        `;
        
        container.appendChild(newRow);
        container.appendChild(instructionsRow);
        labOrderIndex++;
        updateLabOrderRemoveButtons();
        
        // Add event listeners to the new row
        addLabOrderEventListeners(newRow);
    });

    // Remove lab order
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-lab-order')) {
            e.target.closest('.lab-order-row').remove();
            updateLabOrderRemoveButtons();
        }
    });

    function updateLabOrderRemoveButtons() {
        const rows = document.querySelectorAll('.lab-order-row');
        rows.forEach((row, index) => {
            const removeBtn = row.querySelector('.remove-lab-order');
            removeBtn.style.display = rows.length > 1 ? 'block' : 'none';
        });
    }

    // Add/Remove radiology orders
    document.getElementById('add-radiology-order').addEventListener('click', function() {
        const container = document.getElementById('radiology-orders-container');
        const newRow = document.createElement('div');
        newRow.className = 'row radiology-order-row mb-3';
        newRow.innerHTML = `
            <div class="col-md-3">
                <label class="form-label">Imaging Modality</label>
                <select class="form-select modality-select" name="radiology_orders[${radiologyOrderIndex}][modality_id]">
                    <option value="">Select Modality</option>
                    @foreach(\App\Models\ImagingModality::where('is_active', true)->orderBy('name')->get() as $modality)
                        <option value="{{ $modality->id }}">{{ $modality->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select class="form-select department-select" name="radiology_orders[${radiologyOrderIndex}][department_id]">
                    <option value="">Select Department</option>
                    @foreach(\App\Models\RadiologyDepartment::where('is_active', true)->orderBy('name')->get() as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Priority</label>
                <select class="form-select" name="radiology_orders[${radiologyOrderIndex}][priority]">
                    <option value="routine">Routine</option>
                    <option value="urgent">Urgent</option>
                    <option value="stat">Stat</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Clinical Question</label>
                <input type="text" class="form-control" name="radiology_orders[${radiologyOrderIndex}][clinical_question]" placeholder="e.g., Rule out pneumonia">
            </div>
            <div class="col-md-12 mt-2">
                <label class="form-label">Clinical History</label>
                <textarea class="form-control" name="radiology_orders[${radiologyOrderIndex}][clinical_history]" rows="2" placeholder="Brief clinical history relevant to this imaging request..."></textarea>
            </div>
            <div class="col-md-12 mt-2">
                <label class="form-label">Indication</label>
                <textarea class="form-control" name="radiology_orders[${radiologyOrderIndex}][indication]" rows="2" placeholder="Specific indication for this imaging study..."></textarea>
            </div>
            <div class="col-md-6 mt-2">
                <label class="form-label">Scheduled Date (Optional)</label>
                <input type="date" class="form-control" name="radiology_orders[${radiologyOrderIndex}][scheduled_date]" min="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-6 mt-2">
                <label class="form-label">Scheduled Time (Optional)</label>
                <input type="time" class="form-control" name="radiology_orders[${radiologyOrderIndex}][scheduled_time]">
            </div>
            <div class="col-md-1 d-flex align-items-end mt-2">
                <button type="button" class="btn btn-outline-danger btn-sm remove-radiology-order">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(newRow);
        radiologyOrderIndex++;
        updateRadiologyOrderRemoveButtons();
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-radiology-order')) {
            e.target.closest('.radiology-order-row').remove();
            updateRadiologyOrderRemoveButtons();
        }
    });

    function updateRadiologyOrderRemoveButtons() {
        const rows = document.querySelectorAll('.radiology-order-row');
        rows.forEach((row) => {
            const removeBtn = row.querySelector('.remove-radiology-order');
            if (removeBtn) {
                removeBtn.style.display = rows.length > 1 ? 'inline-block' : 'none';
            }
        });
    }

    // Calculate BMI (nurses only — vitals fields may be absent for doctors)
    const calculateBmiBtn = document.getElementById('calculate-bmi');
    const heightInput = document.getElementById('height');
    const weightInput = document.getElementById('weight');
    const bmiInput = document.getElementById('bmi');

    function updateBmi() {
        if (!heightInput || !weightInput || !bmiInput) return;
        const height = parseFloat(heightInput.value);
        const weight = parseFloat(weightInput.value);
        if (height && weight) {
            const heightInMeters = height / 100;
            bmiInput.value = (weight / (heightInMeters * heightInMeters)).toFixed(1);
        }
    }

    if (calculateBmiBtn) {
        calculateBmiBtn.addEventListener('click', updateBmi);
    }
    if (heightInput) heightInput.addEventListener('input', updateBmi);
    if (weightInput) weightInput.addEventListener('input', updateBmi);

    // Save as draft — bypass HTML5 validation on optional order rows
    document.getElementById('save-draft').addEventListener('click', function() {
        const form = document.getElementById('consultationForm');
        enableFilledLabSelects(form);
        form.setAttribute('novalidate', 'novalidate');
        if (!form.querySelector('input[name="is_draft"]')) {
            const draftInput = document.createElement('input');
            draftInput.type = 'hidden';
            draftInput.name = 'is_draft';
            draftInput.value = '1';
            form.appendChild(draftInput);
        }
        form.submit();
    });

    // Lab order event listeners
    function addLabOrderEventListeners(row) {
        const categorySelect = row.querySelector('.test-category-select');
        const testTypeSelect = row.querySelector('.test-type-select');
        const specimenDisplay = row.querySelector('.specimen-type-display');
        const instructionsRow = row.nextElementSibling;
        const collectionInstructions = instructionsRow.querySelector('.collection-instructions');
        const preparationInstructions = instructionsRow.querySelector('.preparation-instructions');

        // Category filter
        categorySelect.addEventListener('change', function() {
            const selectedCategoryId = this.value;
            
            // Reset test type selection and related fields
            testTypeSelect.value = '';
            testTypeSelect.disabled = true;
            specimenDisplay.value = '';
            collectionInstructions.value = '';
            preparationInstructions.value = '';
            instructionsRow.style.display = 'none';
            
            if (!selectedCategoryId) {
                testTypeSelect.innerHTML = '<option value="">Select category first</option>';
                return;
            }
            
            // Show loading state
            testTypeSelect.innerHTML = '<option value="">Loading test types...</option>';
            
            // Fetch test types for the selected category via AJAX
            fetch(`{{ route('lab.test-types-by-category') }}?category_id=${selectedCategoryId}`)
                .then(response => response.json())
                .then(data => {
                    testTypeSelect.innerHTML = '<option value="">Select Test Type</option>';
                    
                    if (data.test_types && data.test_types.length > 0) {
                        data.test_types.forEach(testType => {
                            const option = document.createElement('option');
                            option.value = testType.id;
                            option.textContent = `${testType.test_name} (${testType.test_code})`;
                            option.setAttribute('data-specimen', testType.specimen_type || '');
                            option.setAttribute('data-collection', JSON.stringify(testType.collection_instructions || ''));
                            option.setAttribute('data-preparation', JSON.stringify(testType.preparation_instructions || ''));
                            testTypeSelect.appendChild(option);
                        });
                        testTypeSelect.disabled = false;
                    } else {
                        testTypeSelect.innerHTML = '<option value="">No test types available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching test types:', error);
                    testTypeSelect.innerHTML = '<option value="">Error loading test types</option>';
                });
        });

        // Test type selection
        testTypeSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                // Update specimen type
                specimenDisplay.value = selectedOption.dataset.specimen || '';
                
                // Update collection instructions
                const collectionData = selectedOption.dataset.collection;
                if (collectionData && collectionData !== 'null') {
                    try {
                        const collection = JSON.parse(collectionData);
                        collectionInstructions.value = Array.isArray(collection) ? collection.join('\n') : collection;
                    } catch (e) {
                        collectionInstructions.value = collectionData;
                    }
                } else {
                    collectionInstructions.value = '';
                }
                
                // Update preparation instructions
                const preparationData = selectedOption.dataset.preparation;
                if (preparationData && preparationData !== 'null') {
                    try {
                        const preparation = JSON.parse(preparationData);
                        preparationInstructions.value = Array.isArray(preparation) ? preparation.join('\n') : preparation;
                    } catch (e) {
                        preparationInstructions.value = preparationData;
                    }
                } else {
                    preparationInstructions.value = '';
                }
                
                // Show instructions row
                instructionsRow.style.display = 'block';
            } else {
                specimenDisplay.value = '';
                collectionInstructions.value = '';
                preparationInstructions.value = '';
                instructionsRow.style.display = 'none';
            }
        });
    }

    // Initialize event listeners for existing lab order rows
    document.querySelectorAll('.lab-order-row').forEach(row => {
        addLabOrderEventListeners(row);
    });

    // Initialize remove buttons visibility
    updateComplaintRemoveButtons();
    updatePrescriptionRemoveButtons();
    updateLabOrderRemoveButtons();
    updateRadiologyOrderRemoveButtons();

    // Template loading functionality
    document.getElementById('template_id').addEventListener('change', function() {
        const templateId = this.value;
        if (templateId) {
            // Load template data via AJAX
            fetch(`/api/consultation-templates/${templateId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.template_data) {
                        // Pre-fill form fields with template placeholders
                        Object.keys(data.template_data).forEach(field => {
                            const element = document.getElementById(field);
                            if (element && element.tagName === 'TEXTAREA') {
                                element.placeholder = data.template_data[field];
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading template:', error);
                });
        }
    });

    // Disabled selects are omitted from POST — enable any with a value before submit
    function enableFilledLabSelects(form) {
        form.querySelectorAll('.test-type-select').forEach(select => {
            if (select.value) {
                select.disabled = false;
            }
        });
    }

    // Form submission with workflow determination (complete only)
    document.getElementById('consultationForm').addEventListener('submit', function(e) {
        enableFilledLabSelects(this);

        if (this.hasAttribute('novalidate')) {
            return true;
        }

        this.removeAttribute('novalidate');

        const diagnosis = document.getElementById('doctors_impression').value.trim();
        if (!diagnosis) {
            e.preventDefault();
            alert('Please provide a diagnosis before completing the consultation.');
            return false;
        }

        const chiefComplaint = document.getElementById('chief_complaint');
        if (chiefComplaint && !chiefComplaint.value.trim()) {
            e.preventDefault();
            alert('Please provide a chief complaint before completing the consultation.');
            return false;
        }

        // Validate filled prescription rows have required fields
        let prescriptionValid = true;
        document.querySelectorAll('.prescription-order-row').forEach(row => {
            const drugSelect = row.querySelector('.drug-select');
            if (drugSelect && drugSelect.value) {
                const qty = row.querySelector('.quantity-input');
                const dosage = row.querySelector('[name*="dosage_instructions"]');
                if (!qty || !qty.value || !dosage || !dosage.value.trim()) {
                    prescriptionValid = false;
                }
            }
        });
        if (!prescriptionValid) {
            e.preventDefault();
            alert('Please complete quantity and dosage for each selected medication.');
            return false;
        }

        // Validate filled lab rows have test type selected
        let labValid = true;
        document.querySelectorAll('.lab-order-row').forEach(row => {
            const categorySelect = row.querySelector('.test-category-select');
            const testSelect = row.querySelector('.test-type-select');
            if (categorySelect && categorySelect.value && (!testSelect || !testSelect.value)) {
                labValid = false;
            }
        });
        if (!labValid) {
            e.preventDefault();
            alert('Please select a test type for each lab category chosen.');
            return false;
        }

        // Validate filled radiology rows have both modality and department
        let radiologyValid = true;
        document.querySelectorAll('.radiology-order-row').forEach(row => {
            const modalitySelect = row.querySelector('.modality-select');
            const departmentSelect = row.querySelector('.department-select');
            const hasModality = modalitySelect && modalitySelect.value;
            const hasDepartment = departmentSelect && departmentSelect.value;
            if ((hasModality && !hasDepartment) || (!hasModality && hasDepartment)) {
                radiologyValid = false;
            }
        });
        if (!radiologyValid) {
            e.preventDefault();
            alert('Please select both modality and department for each radiology request, or leave the row empty.');
            return false;
        }

        let nextStage = 'completed';
        let workflowMessage = 'Consultation completed successfully!';
        let hasPrescriptions = false;
        let hasLabOrders = false;
        let hasRadiologyOrders = false;

        document.querySelectorAll('.prescription-order-row').forEach(row => {
            const drugSelect = row.querySelector('.drug-select');
            if (drugSelect && drugSelect.value) hasPrescriptions = true;
        });

        document.querySelectorAll('.lab-order-row').forEach(row => {
            const testSelect = row.querySelector('.test-type-select');
            if (testSelect && testSelect.value) hasLabOrders = true;
        });

        document.querySelectorAll('.radiology-order-row').forEach(row => {
            const modalitySelect = row.querySelector('.modality-select');
            const departmentSelect = row.querySelector('.department-select');
            if (modalitySelect && modalitySelect.value && departmentSelect && departmentSelect.value) {
                hasRadiologyOrders = true;
            }
        });

        if (hasPrescriptions && hasLabOrders && hasRadiologyOrders) {
            nextStage = 'pharmacy_lab_radiology';
            workflowMessage += ' Patient has been directed to pharmacy, laboratory, and radiology.';
        } else if (hasPrescriptions && hasLabOrders) {
            nextStage = 'pharmacy_lab';
            workflowMessage += ' Patient will proceed to pharmacy and laboratory.';
        } else if (hasPrescriptions && hasRadiologyOrders) {
            nextStage = 'pharmacy_radiology';
            workflowMessage += ' Patient will proceed to pharmacy and radiology.';
        } else if (hasLabOrders && hasRadiologyOrders) {
            nextStage = 'lab_radiology';
            workflowMessage += ' Patient will proceed to laboratory and radiology.';
        } else if (hasPrescriptions) {
            nextStage = 'pharmacy';
            workflowMessage += ' Patient has been directed to pharmacy for medication dispensing.';
        } else if (hasLabOrders) {
            nextStage = 'laboratory';
            workflowMessage += ' Patient has been directed to laboratory for testing.';
        } else if (hasRadiologyOrders) {
            nextStage = 'radiology';
            workflowMessage += ' Patient has been directed to radiology for imaging.';
        }

        if (!this.querySelector('input[name="next_stage"]')) {
            const workflowInput = document.createElement('input');
            workflowInput.type = 'hidden';
            workflowInput.name = 'next_stage';
            workflowInput.value = nextStage;
            this.appendChild(workflowInput);
        }

        if (!this.querySelector('input[name="is_draft"]')) {
            const draftInput = document.createElement('input');
            draftInput.type = 'hidden';
            draftInput.name = 'is_draft';
            draftInput.value = '0';
            this.appendChild(draftInput);
        }

        if (nextStage !== 'completed') {
            setTimeout(() => alert(workflowMessage), 100);
        }
    });
});
</script>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/ckeditor-config.js') }}"></script>
@endpush