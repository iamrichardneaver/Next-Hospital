@extends('layouts.app')

@section('title', 'Continue Consultation')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Continue Consultation</h1>
                <p class="page-subtitle">Complete the consultation for this patient</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('consultations.doctor-queue') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Doctor Queue
                </a>
            </div>
        </div>
    </div>

    <!-- Workflow Progress Tracker -->
    @if(isset($workflowInstance) && $workflowInstance)
        <x-workflow-progress :instance="$workflowInstance" />
    @endif

    <form action="{{ route('consultations.update', $consultation->id) }}" method="POST" id="consultationForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="from_queue" value="1">

        <!-- Patient Information Section (Read-only for doctors) -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-person-check"></i> Patient Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Patient Name:</strong><br>
                        {{ $patient->first_name }} {{ $patient->last_name }}
                    </div>
                    <div class="col-md-3">
                        <strong>Patient Number:</strong><br>
                        {{ $patient->patient_number }}
                    </div>
                    <div class="col-md-3">
                        <strong>Phone:</strong><br>
                        {{ $patient->phone }}
                    </div>
                    <div class="col-md-3">
                        <strong>Gender:</strong><br>
                        {{ $patient->gender }}
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <strong>Date of Birth:</strong><br>
                        {{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('d/m/Y') : 'Not specified' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Age:</strong><br>
                        {{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age . ' years' : 'Not specified' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Address:</strong><br>
                        {{ $patient->address ?? 'Not specified' }}
                    </div>
                </div>
                
                <!-- Show existing chief complaint if any -->
                @if($consultation->chief_complaint)
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <strong>Initial Chief Complaint:</strong><br>
                            {{ $consultation->chief_complaint }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Consultation Details Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-data"></i> Consultation Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="doctor_id" class="form-label">Attending Doctor <span class="text-danger">*</span></label>
                        @if(auth()->user()->hasRole('doctor'))
                            {{-- Doctors cannot change doctor - show as read-only --}}
                            <input type="text" class="form-control" value="Dr. {{ $selectedDoctor->first_name }} {{ $selectedDoctor->last_name }}" disabled>
                            <input type="hidden" name="doctor_id" value="{{ $selectedDoctor->id }}">
                            <small class="form-text text-muted">Doctor is pre-assigned from queue</small>
                        @else
                            <select class="form-select @error('doctor_id') is-invalid @enderror" id="doctor_id" name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor->id }}" {{ $selectedDoctor->id == $doctor->id ? 'selected' : '' }}>
                                        Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('doctor_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="consultation_type" class="form-label">Consultation Type <span class="text-danger">*</span></label>
                        <select class="form-select @error('consultation_type') is-invalid @enderror" id="consultation_type" name="consultation_type" required>
                            <option value="in-person" {{ old('consultation_type', $consultation->consultation_type) == 'in-person' ? 'selected' : '' }}>In-Person</option>
                            <option value="teleconsultation" {{ old('consultation_type', $consultation->consultation_type) == 'teleconsultation' ? 'selected' : '' }}>Teleconsultation</option>
                        </select>
                        @error('consultation_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="consultation_date" class="form-label">Consultation Date <span class="text-danger">*</span></label>
                        <input type="date" 
                               class="form-control @error('consultation_date') is-invalid @enderror" 
                               id="consultation_date" 
                               name="consultation_date" 
                               value="{{ old('consultation_date', $consultation->consultation_date ? $consultation->consultation_date->format('Y-m-d') : now()->format('Y-m-d')) }}" 
                               required>
                        @error('consultation_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="template_id" class="form-label">Consultation Template (Optional)</label>
                        <select class="form-select @error('template_id') is-invalid @enderror" id="template_id" name="template_id">
                            <option value="">Select Template</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" {{ old('template_id', $consultation->template_id) == $template->id ? 'selected' : '' }}>
                                    {{ $template->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Choose a template to pre-fill common fields</small>
                        @error('template_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
                              id="chief_complaint" 
                              name="chief_complaint" 
                              rows="3" 
                              placeholder="What brings the patient in today?" 
                              required>{{ old('chief_complaint', $consultation->chief_complaint) }}</textarea>
                    @error('chief_complaint')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Additional Complaints</label>
                    <div id="additional-complaints">
                        @if($consultation->additional_complaints && count($consultation->additional_complaints) > 0)
                            @foreach($consultation->additional_complaints as $index => $complaint)
                            <div class="row mb-2 complaint-row">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="additional_complaints[{{ $index }}][complaint]" value="{{ $complaint['complaint'] ?? '' }}" placeholder="Complaint (e.g., cough)">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="additional_complaints[{{ $index }}][duration]" value="{{ $complaint['duration'] ?? '' }}" placeholder="Duration (e.g., 2 weeks)">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-complaint">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="row mb-2 complaint-row">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="additional_complaints[0][complaint]" placeholder="Complaint (e.g., cough)">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="additional_complaints[0][duration]" placeholder="Duration (e.g., 2 weeks)">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-complaint" style="display: none;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-complaint">
                        <i class="bi bi-plus"></i> Add Complaint
                    </button>
                </div>
            </div>
        </div>
        @endcan

        <!-- History of Present Illness Section -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> History of Present Illness</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="history_of_present_illness" class="form-label">History of Present Illness</label>
                    <textarea class="form-control @error('history_of_present_illness') is-invalid @enderror" 
                              id="history_of_present_illness" 
                              name="history_of_present_illness" 
                              rows="4" 
                              placeholder="Detailed history of the current illness...">{{ old('history_of_present_illness', $consultation->history_of_present_illness) }}</textarea>
                    @error('history_of_present_illness')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="on_direct_questioning" class="form-label">On Direct Questioning</label>
                    <textarea class="form-control @error('on_direct_questioning') is-invalid @enderror" 
                              id="on_direct_questioning" 
                              name="on_direct_questioning" 
                              rows="3" 
                              placeholder="Findings after direct questioning...">{{ old('on_direct_questioning', $consultation->on_direct_questioning) }}</textarea>
                    @error('on_direct_questioning')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Past Medical History Section -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-heart-pulse"></i> Past Medical History</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="past_medical_history" class="form-label">Past Medical History</label>
                    <textarea class="form-control @error('past_medical_history') is-invalid @enderror" 
                              id="past_medical_history" 
                              name="past_medical_history" 
                              rows="3" 
                              placeholder="Previous medical conditions, surgeries, hospitalizations...">{{ old('past_medical_history', $consultation->past_medical_history) }}</textarea>
                    @error('past_medical_history')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Drug History Section -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-capsule"></i> Drug History</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="drug_history" class="form-label">General Drug History</label>
                        <textarea class="form-control @error('drug_history') is-invalid @enderror" 
                                  id="drug_history" 
                                  name="drug_history" 
                                  rows="3" 
                                  placeholder="General drug history...">{{ old('drug_history', $consultation->drug_history) }}</textarea>
                        @error('drug_history')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="current_medications" class="form-label">Current Medications</label>
                        <textarea class="form-control @error('current_medications') is-invalid @enderror" 
                                  id="current_medications" 
                                  name="current_medications" 
                                  rows="3" 
                                  placeholder="Current medications with dosage...">{{ old('current_medications', $consultation->current_medications) }}</textarea>
                        @error('current_medications')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="drug_allergies" class="form-label">Drug Allergies</label>
                        <textarea class="form-control @error('drug_allergies') is-invalid @enderror" 
                                  id="drug_allergies" 
                                  name="drug_allergies" 
                                  rows="3" 
                                  placeholder="Drug allergies and reactions...">{{ old('drug_allergies', $consultation->drug_allergies) }}</textarea>
                        @error('drug_allergies')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label for="past_drug_usage" class="form-label">Past Drug Usage</label>
                    <textarea class="form-control @error('past_drug_usage') is-invalid @enderror" 
                              id="past_drug_usage" 
                              name="past_drug_usage" 
                              rows="2" 
                              placeholder="Past drug usage if relevant...">{{ old('past_drug_usage', $consultation->past_drug_usage) }}</textarea>
                    @error('past_drug_usage')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="allergy_history" class="form-label">General Allergy History</label>
                    <textarea class="form-control @error('allergy_history') is-invalid @enderror" 
                              id="allergy_history" 
                              name="allergy_history" 
                              rows="2" 
                              placeholder="General allergies (food, environmental, etc.)...">{{ old('allergy_history', $consultation->allergy_history) }}</textarea>
                    @error('allergy_history')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Family & Social History Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-people"></i> Family & Social History</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="family_history" class="form-label">Family History</label>
                        <textarea class="form-control @error('family_history') is-invalid @enderror" 
                                  id="family_history" 
                                  name="family_history" 
                                  rows="3" 
                                  placeholder="Family medical history (diabetes, hypertension, genetic diseases)...">{{ old('family_history', $consultation->family_history) }}</textarea>
                        @error('family_history')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="social_history" class="form-label">Social History</label>
                        <textarea class="form-control @error('social_history') is-invalid @enderror" 
                                  id="social_history" 
                                  name="social_history" 
                                  rows="3" 
                                  placeholder="Occupation, lifestyle, etc...">{{ old('social_history', $consultation->social_history) }}</textarea>
                        @error('social_history')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label for="social_history_details" class="form-label">Social History Details</label>
                    <textarea class="form-control @error('social_history_details') is-invalid @enderror" 
                              id="social_history_details" 
                              name="social_history_details" 
                              rows="3" 
                              placeholder="Smoking, alcohol, diet, exercise, lifestyle notes...">{{ old('social_history_details', $consultation->social_history_details) }}</textarea>
                    @error('social_history_details')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Physical Examination Section -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-pulse"></i> Physical Examination</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="vitals" class="form-label">Vital Signs</label>
                        
                        <!-- Display existing vitals if available -->
                        @if($consultation->vitals && $consultation->vitals->count() > 0)
                        <div class="alert alert-info mb-3">
                            <h6 class="mb-2"><i class="bi bi-heart-pulse"></i> Recorded Vital Signs:</h6>
                            @foreach($consultation->vitals as $vital)
                                <div class="row mb-2">
                                    <div class="col-md-12">
                                        <small>
                                            <strong>Recorded:</strong> {{ $vital->recorded_at ? \Carbon\Carbon::parse($vital->recorded_at)->format('M d, Y H:i') : 'N/A' }}
                                            @if($vital->blood_pressure_systolic)
                                                | <strong>BP:</strong> {{ $vital->blood_pressure_systolic }}/{{ $vital->blood_pressure_diastolic }} mmHg
                                            @endif
                                            @if($vital->pulse_rate)
                                                | <strong>Pulse:</strong> {{ $vital->pulse_rate }} bpm
                                            @endif
                                            @if($vital->temperature)
                                                | <strong>Temp:</strong> {{ $vital->temperature }}°C
                                            @endif
                                            @if($vital->height && $vital->weight)
                                                | <strong>BMI:</strong> {{ $vital->bmi ?? 'N/A' }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @elseif($consultation->blood_pressure_systolic || $consultation->temperature || $consultation->pulse_rate)
                        <div class="alert alert-info mb-3">
                            <h6 class="mb-2"><i class="bi bi-heart-pulse"></i> Vital Signs from Consultation:</h6>
                            <small>
                                @if($consultation->blood_pressure_systolic)
                                    <strong>BP:</strong> {{ $consultation->blood_pressure_systolic }}/{{ $consultation->blood_pressure_diastolic }} mmHg
                                @endif
                                @if($consultation->pulse_rate)
                                    | <strong>Pulse:</strong> {{ $consultation->pulse_rate }} bpm
                                @endif
                                @if($consultation->temperature)
                                    | <strong>Temp:</strong> {{ $consultation->temperature }}°C
                                @endif
                                @if($consultation->height && $consultation->weight)
                                    | <strong>BMI:</strong> {{ $consultation->bmi ?? 'N/A' }}
                                @endif
                            </small>
                        </div>
                        @endif
                        
                        <textarea class="form-control @error('vitals') is-invalid @enderror" 
                                  id="vitals" 
                                  name="vitals" 
                                  rows="3"
                                  placeholder="Additional vital signs notes or updates...">{{ old('vitals', $consultation->vitals) }}</textarea>
                        <small class="form-text text-muted">Add any additional vital signs notes or updates</small>
                        @error('vitals')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="physical_examination" class="form-label">General Physical Examination</label>
                        <textarea class="form-control @error('physical_examination') is-invalid @enderror" 
                                  id="physical_examination" 
                                  name="physical_examination" 
                                  rows="3" 
                                  placeholder="General appearance, pallor, jaundice, etc.">{{ old('physical_examination', $consultation->physical_examination) }}</textarea>
                        @error('physical_examination')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- System-Specific Examinations -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cardiovascular_examination" class="form-label">Cardiovascular System</label>
                        <textarea class="form-control @error('cardiovascular_examination') is-invalid @enderror" 
                                  id="cardiovascular_examination" 
                                  name="cardiovascular_examination" 
                                  rows="2" 
                                  placeholder="Heart sounds, murmurs, etc.">{{ old('cardiovascular_examination', $consultation->cardiovascular_examination) }}</textarea>
                        @error('cardiovascular_examination')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="respiratory_examination" class="form-label">Respiratory System</label>
                        <textarea class="form-control @error('respiratory_examination') is-invalid @enderror" 
                                  id="respiratory_examination" 
                                  name="respiratory_examination" 
                                  rows="2" 
                                  placeholder="Breath sounds, added sounds, etc.">{{ old('respiratory_examination', $consultation->respiratory_examination) }}</textarea>
                        @error('respiratory_examination')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="abdominal_examination" class="form-label">Abdominal Examination</label>
                        <textarea class="form-control @error('abdominal_examination') is-invalid @enderror" 
                                  id="abdominal_examination" 
                                  name="abdominal_examination" 
                                  rows="2" 
                                  placeholder="Liver, spleen, tenderness, masses, bowel sounds">{{ old('abdominal_examination', $consultation->abdominal_examination) }}</textarea>
                        @error('abdominal_examination')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="neurological_examination" class="form-label">Central Nervous System</label>
                        <textarea class="form-control @error('neurological_examination') is-invalid @enderror" 
                                  id="neurological_examination" 
                                  name="neurological_examination" 
                                  rows="2" 
                                  placeholder="Mental status, reflexes, cranial nerves, motor/sensory exam">{{ old('neurological_examination', $consultation->neurological_examination) }}</textarea>
                        @error('neurological_examination')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Prescription Orders Section -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-capsule"></i> Prescription Orders</h5>
            </div>
            <div class="card-body">
                @php $existingPrescriptions = $existingPrescriptions ?? collect(); @endphp
                @if($existingPrescriptions->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i> Showing {{ $existingPrescriptions->count() }} existing prescription(s). You can modify or add more below.
                    </div>
                @endif
                <div id="prescription-orders-container">
                    @php $prescriptionRowIndex = 0; @endphp
                    @forelse($existingPrescriptions as $prescription)
                        @foreach($prescription->orders as $drugOrder)
                            <div class="row prescription-order-row mb-3" style="{{ $prescriptionRowIndex > 0 ? 'background-color: #e3f2fd; padding: 10px; border-radius: 5px; border-left: 3px solid #2196f3;' : '' }}">
                                <div class="col-md-4">
                                    <label class="form-label">Drug/Medication</label>
                                    <select class="form-select drug-select" name="prescription_orders[{{ $prescriptionRowIndex }}][drug_id]">
                                        <option value="">Select Drug</option>
                                        @foreach(\App\Models\Drug::active()->with('stocks')->get() as $drug)
                                            @php $currentStock = $drug->getCurrentStock(); @endphp
                                            <option value="{{ $drug->id }}" data-price="{{ $drug->selling_price }}" data-stock="{{ $currentStock }}" {{ $drugOrder->drug_id == $drug->id ? 'selected' : '' }}>
                                                {{ $drug->name }} ({{ $drug->dosage_form }}) - Stock: {{ $currentStock }} {{ $currentStock == 0 ? '❌' : ($currentStock < 50 ? '⚠️' : '✅') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="prescription_orders[{{ $prescriptionRowIndex }}][prescription_id]" value="{{ $prescription->id }}">
                                    @if($prescriptionRowIndex > 0)<small class="text-success"><i class="bi bi-check-circle-fill"></i> Saved</small>@endif
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" class="form-control quantity-input" name="prescription_orders[{{ $prescriptionRowIndex }}][quantity]" value="{{ old('prescription_orders.'.$prescriptionRowIndex.'.quantity', $drugOrder->quantity) }}" min="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Dosage Instructions</label>
                                    <input type="text" class="form-control" name="prescription_orders[{{ $prescriptionRowIndex }}][dosage_instructions]" value="{{ old('prescription_orders.'.$prescriptionRowIndex.'.dosage_instructions', $drugOrder->dosage_instructions) }}" placeholder="e.g., 1 tablet twice daily">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Duration</label>
                                    <input type="text" class="form-control" name="prescription_orders[{{ $prescriptionRowIndex }}][duration]" value="{{ old('prescription_orders.'.$prescriptionRowIndex.'.duration', $drugOrder->duration) }}" placeholder="e.g., 7 days">
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-prescription"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            @php $prescriptionRowIndex++; @endphp
                        @endforeach
                    @empty
                        <div class="row prescription-order-row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Drug/Medication</label>
                                <select class="form-select drug-select" name="prescription_orders[0][drug_id]">
                                    <option value="">Select Drug</option>
                                    @foreach(\App\Models\Drug::active()->with('stocks')->get() as $drug)
                                        @php $currentStock = $drug->getCurrentStock(); @endphp
                                        <option value="{{ $drug->id }}" data-price="{{ $drug->selling_price }}" data-stock="{{ $currentStock }}">{{ $drug->name }} ({{ $drug->dosage_form }}) - Stock: {{ $currentStock }} {{ $currentStock == 0 ? '❌' : ($currentStock < 50 ? '⚠️' : '✅') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control quantity-input" name="prescription_orders[0][quantity]" min="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Dosage Instructions</label>
                                <input type="text" class="form-control" name="prescription_orders[0][dosage_instructions]" placeholder="e.g., 1 tablet twice daily">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Duration</label>
                                <input type="text" class="form-control" name="prescription_orders[0][duration]" placeholder="e.g., 7 days">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-prescription" style="display: none;"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    @endforelse
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-prescription">
                    <i class="bi bi-plus"></i> Add Medication
                </button>
            </div>
        </div>

        <!-- Lab Test Orders Section -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-flask"></i> Laboratory Orders</h5>
            </div>
            <div class="card-body">
                @php $existingLabOrders = $existingLabOrders ?? collect(); @endphp
                @if($existingLabOrders->count() > 0)
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-info-circle"></i> Showing {{ $existingLabOrders->count() }} existing lab order(s). You can modify or add more below.
                    </div>
                @endif
                <div id="lab-orders-container">
                    @forelse($existingLabOrders as $index => $labOrder)
                        <div class="row lab-order-row mb-3" style="background-color: #fff3cd; padding: 10px; border-radius: 5px; border-left: 3px solid #ffc107;">
                            <div class="col-md-3">
                                <label class="form-label">Test Category</label>
                                <select class="form-select test-category-select" name="lab_orders[{{ $index }}][category]">
                                    <option value="">Select Category</option>
                                    @foreach(\App\Models\LabTestType::active()->select('category')->distinct()->get() as $category)
                                        <option value="{{ $category->category }}" {{ ($labOrder->template && $labOrder->template->category == $category->category) || $labOrder->testType && $labOrder->testType->category == $category->category ? 'selected' : '' }}>{{ $category->category }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="lab_orders[{{ $index }}][lab_request_id]" value="{{ $labOrder->id }}">
                                <small class="text-warning"><i class="bi bi-flask-fill"></i> Saved</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Test Name</label>
                                <select class="form-select test-type-select" name="lab_orders[{{ $index }}][test_type_id]">
                                    <option value="">Select Test</option>
                                    @foreach(\App\Models\LabTestType::active()->get() as $testType)
                                        <option value="{{ $testType->id }}" data-category="{{ $testType->category }}" data-specimen="{{ $testType->specimen_type }}" data-collection="{{ json_encode($testType->collection_instructions) }}" data-preparation="{{ json_encode($testType->preparation_instructions) }}" {{ $labOrder->test_type_id == $testType->id ? 'selected' : '' }}>{{ $testType->test_name }} ({{ $testType->test_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Priority</label>
                                <select class="form-select priority-select" name="lab_orders[{{ $index }}][priority]">
                                    <option value="routine" {{ $labOrder->priority == 'routine' ? 'selected' : '' }}>Routine</option>
                                    <option value="urgent" {{ $labOrder->priority == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                    <option value="stat" {{ $labOrder->priority == 'stat' ? 'selected' : '' }}>STAT</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Specimen Type</label>
                                <input type="text" class="form-control specimen-type-display" name="lab_orders[{{ $index }}][specimen_type]" value="{{ old('lab_orders.'.$index.'.specimen_type', $labOrder->specimen_type) }}" readonly placeholder="Auto-filled from test type">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-lab-order"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    @empty
                        <div class="row lab-order-row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Test Category</label>
                                <select class="form-select test-category-select" name="lab_orders[0][category]">
                                    <option value="">Select Category</option>
                                    @foreach(\App\Models\LabTestType::active()->select('category')->distinct()->get() as $category)
                                        <option value="{{ $category->category }}">{{ $category->category }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Test Name</label>
                                <select class="form-select test-type-select" name="lab_orders[0][test_type_id]">
                                    <option value="">Select Test</option>
                                    @foreach(\App\Models\LabTestType::active()->get() as $testType)
                                        <option value="{{ $testType->id }}" data-category="{{ $testType->category }}" data-specimen="{{ $testType->specimen_type }}" data-collection="{{ json_encode($testType->collection_instructions) }}" data-preparation="{{ json_encode($testType->preparation_instructions) }}">{{ $testType->test_name }} ({{ $testType->test_code }})</option>
                                    @endforeach
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
                                <input type="text" class="form-control specimen-type-display" name="lab_orders[0][specimen_type]" readonly placeholder="Auto-filled from test type">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-lab-order" style="display: none;"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    @endforelse
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-lab-order">
                    <i class="bi bi-plus"></i> Add Lab Test
                </button>
            </div>
        </div>

        <!-- Radiology/Imaging Orders Section -->
        <div class="card mb-4">
            <div class="card-header bg-purple text-white" style="background-color: #6f42c1;">
                <h5 class="mb-0"><i class="bi bi-camera-reels"></i> Radiology/Imaging Orders</h5>
            </div>
            <div class="card-body">
                @php $existingRadiologyOrders = $existingRadiologyOrders ?? collect(); @endphp
                @if($existingRadiologyOrders->count() > 0)
                    <div class="alert alert-secondary mb-3">
                        <i class="bi bi-info-circle"></i> Showing {{ $existingRadiologyOrders->count() }} existing radiology order(s). You can modify or add more below.
                    </div>
                @endif
                <p class="text-muted mb-3">Add radiology/imaging requests that will be queued for the radiology department.</p>
                <div id="radiology-orders-container">
                    @forelse($existingRadiologyOrders as $rIndex => $radOrder)
                        <div class="row radiology-order-row mb-3" style="background-color: #e2d5f1; padding: 10px; border-radius: 5px; border-left: 3px solid #6f42c1;">
                            <div class="col-md-3">
                                <label class="form-label">Imaging Modality</label>
                                <select class="form-select modality-select" name="radiology_orders[{{ $rIndex }}][modality_id]">
                                    <option value="">Select Modality</option>
                                    @foreach(\App\Models\ImagingModality::where('is_active', true)->orderBy('name')->get() as $modality)
                                        <option value="{{ $modality->id }}" {{ $radOrder->modality_id == $modality->id ? 'selected' : '' }}>{{ $modality->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="radiology_orders[{{ $rIndex }}][radiology_request_id]" value="{{ $radOrder->id }}">
                                <small class="text-secondary"><i class="bi bi-camera-reels-fill"></i> Saved</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Department</label>
                                <select class="form-select department-select" name="radiology_orders[{{ $rIndex }}][department_id]">
                                    <option value="">Select Department</option>
                                    @foreach(\App\Models\RadiologyDepartment::where('is_active', true)->orderBy('name')->get() as $department)
                                        <option value="{{ $department->id }}" {{ $radOrder->department_id == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="radiology_orders[{{ $rIndex }}][priority]">
                                    <option value="routine" {{ $radOrder->priority == 'routine' ? 'selected' : '' }}>Routine</option>
                                    <option value="urgent" {{ $radOrder->priority == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                    <option value="stat" {{ $radOrder->priority == 'stat' ? 'selected' : '' }}>Stat</option>
                                    <option value="emergency" {{ ($radOrder->priority ?? '') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Clinical Question/Indication</label>
                                <input type="text" class="form-control" name="radiology_orders[{{ $rIndex }}][clinical_question]" value="{{ old('radiology_orders.'.$rIndex.'.clinical_question', $radOrder->clinical_question) }}" placeholder="e.g., Rule out pneumonia">
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label">Clinical History</label>
                                <textarea class="form-control" name="radiology_orders[{{ $rIndex }}][clinical_history]" rows="2" placeholder="Brief clinical history...">{{ old('radiology_orders.'.$rIndex.'.clinical_history', $radOrder->clinical_history) }}</textarea>
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label">Indication</label>
                                <textarea class="form-control" name="radiology_orders[{{ $rIndex }}][indication]" rows="2" placeholder="Specific indication...">{{ old('radiology_orders.'.$rIndex.'.indication', $radOrder->indication) }}</textarea>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label">Scheduled Date (Optional)</label>
                                <input type="date" class="form-control" name="radiology_orders[{{ $rIndex }}][scheduled_date]" value="{{ old('radiology_orders.'.$rIndex.'.scheduled_date', $radOrder->scheduled_date ? $radOrder->scheduled_date->format('Y-m-d') : '') }}" min="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label">Scheduled Time (Optional)</label>
                                <input type="time" class="form-control" name="radiology_orders[{{ $rIndex }}][scheduled_time]" value="{{ old('radiology_orders.'.$rIndex.'.scheduled_time', $radOrder->scheduled_time ? \Carbon\Carbon::parse($radOrder->scheduled_time)->format('H:i') : '') }}">
                            </div>
                            <div class="col-md-1 d-flex align-items-end mt-2">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-radiology-order"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    @empty
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
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Clinical Question/Indication</label>
                                <input type="text" class="form-control" name="radiology_orders[0][clinical_question]" placeholder="e.g., Rule out pneumonia">
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label">Clinical History</label>
                                <textarea class="form-control" name="radiology_orders[0][clinical_history]" rows="2" placeholder="Brief clinical history..."></textarea>
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label">Indication</label>
                                <textarea class="form-control" name="radiology_orders[0][indication]" rows="2" placeholder="Specific indication..."></textarea>
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
                                <button type="button" class="btn btn-outline-danger btn-sm remove-radiology-order" style="display: none;"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    @endforelse
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-radiology-order">
                    <i class="bi bi-plus"></i> Add Radiology/Imaging Request
                </button>
            </div>
        </div>

        <!-- Assessment and Plan Section -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Assessment and Plan</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="doctors_impression" class="form-label">Doctor's Impression/Diagnosis <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('doctors_impression') is-invalid @enderror" 
                              id="doctors_impression" 
                              name="doctors_impression" 
                              rows="3" 
                              placeholder="Primary diagnosis and differential diagnoses..." 
                              required>{{ old('doctors_impression', $consultation->doctors_impression) }}</textarea>
                    @error('doctors_impression')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="treatment_plan" class="form-label">Treatment Plan</label>
                    <textarea class="form-control @error('treatment_plan') is-invalid @enderror" 
                              id="treatment_plan" 
                              name="treatment_plan" 
                              rows="3" 
                              placeholder="Medications, procedures, follow-up instructions...">{{ old('treatment_plan', $consultation->treatment_plan) }}</textarea>
                    @error('treatment_plan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex justify-content-between">
            <a href="{{ route('consultations.doctor-queue') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Cancel
            </a>
            <div>
                <button type="submit" name="is_draft" value="1" class="btn btn-outline-primary me-2">
                    <i class="bi bi-save"></i> Save as Draft
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Complete Consultation
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let complaintIndex = {{ $consultation->additional_complaints ? count($consultation->additional_complaints) : 1 }};
    @php
        $prescriptionCount = isset($existingPrescriptions) ? $existingPrescriptions->sum(fn($p) => $p->orders->count()) : 0;
        $labCount = isset($existingLabOrders) ? $existingLabOrders->count() : 0;
        $radiologyCount = isset($existingRadiologyOrders) ? $existingRadiologyOrders->count() : 0;
    @endphp
    let prescriptionIndex = {{ $prescriptionCount > 0 ? $prescriptionCount : 1 }};
    let labOrderIndex = {{ $labCount > 0 ? $labCount : 1 }};
    let radiologyOrderIndex = {{ $radiologyCount > 0 ? $radiologyCount : 1 }};
    
    // Real-time workflow progress updates
    @if(isset($workflowInstance) && $workflowInstance)
    const workflowInstanceId = {{ $workflowInstance->id }};
    let progressUpdateInterval = null;
    
    function updateWorkflowProgress() {
        fetch(`/api/workflow/instance/${workflowInstanceId}/progress`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const progressPercentage = data.data.progress_percentage || 0;
                const progressBar = document.getElementById(`progress-bar-${workflowInstanceId}`);
                const progressBadge = document.getElementById(`progress-percentage-${workflowInstanceId}`);
                
                if (progressBar) {
                    progressBar.style.width = progressPercentage + '%';
                    progressBar.setAttribute('aria-valuenow', progressPercentage);
                }
                
                if (progressBadge) {
                    progressBadge.textContent = progressPercentage + '% Complete';
                }
            }
        })
        .catch(error => {
            console.error('Error updating workflow progress:', error);
        });
    }
    
    // Update progress every 5 seconds while on the page
    if (workflowInstanceId) {
        progressUpdateInterval = setInterval(updateWorkflowProgress, 5000);
        
        // Also update on form field changes (debounced)
        let progressUpdateTimeout = null;
        const formFields = document.querySelectorAll('#consultationForm input, #consultationForm textarea, #consultationForm select');
        formFields.forEach(field => {
            field.addEventListener('input', function() {
                clearTimeout(progressUpdateTimeout);
                progressUpdateTimeout = setTimeout(updateWorkflowProgress, 2000); // Update 2 seconds after last input
            });
        });
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (progressUpdateInterval) {
                clearInterval(progressUpdateInterval);
            }
        });
    }
    @endif
    
    // Add complaint functionality
    document.getElementById('add-complaint').addEventListener('click', function() {
        const container = document.getElementById('additional-complaints');
        const newRow = document.createElement('div');
        newRow.className = 'row mb-2 complaint-row';
        newRow.innerHTML = `
            <div class="col-md-5">
                <input type="text" class="form-control" name="additional_complaints[${complaintIndex}][complaint]" placeholder="Complaint (e.g., cough)">
            </div>
            <div class="col-md-5">
                <input type="text" class="form-control" name="additional_complaints[${complaintIndex}][duration]" placeholder="Duration (e.g., 2 weeks)">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm remove-complaint">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(newRow);
        complaintIndex++;
        
        // Show remove buttons for all rows
        document.querySelectorAll('.remove-complaint').forEach(btn => {
            btn.style.display = 'inline-block';
        });
    });
    
    // Remove complaint functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-complaint')) {
            e.target.closest('.complaint-row').remove();
            
            // Hide remove button if only one row left
            const remainingRows = document.querySelectorAll('.complaint-row');
            if (remainingRows.length === 1) {
                document.querySelector('.remove-complaint').style.display = 'none';
            }
        }
    });
    
    // Show remove buttons if there are multiple complaints
    const complaintRows = document.querySelectorAll('.complaint-row');
    if (complaintRows.length > 1) {
        document.querySelectorAll('.remove-complaint').forEach(btn => {
            btn.style.display = 'inline-block';
        });
    }

    // Add/Remove prescription orders
    document.getElementById('add-prescription').addEventListener('click', function() {
        const container = document.getElementById('prescription-orders-container');
        const newRow = document.createElement('div');
        newRow.className = 'row prescription-order-row mb-3';
        newRow.innerHTML = `
            <div class="col-md-4">
                <label class="form-label">Drug/Medication</label>
                <select class="form-select drug-select" name="prescription_orders[${prescriptionIndex}][drug_id]">
                    <option value="">Select Drug</option>
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

    function updatePrescriptionRemoveButtons() {
        const prescriptionRows = document.querySelectorAll('.prescription-order-row');
        prescriptionRows.forEach((row, index) => {
            const removeBtn = row.querySelector('.remove-prescription');
            if (prescriptionRows.length > 1) {
                removeBtn.style.display = 'inline-block';
            } else {
                removeBtn.style.display = 'none';
            }
        });
    }

    // Add/Remove lab orders
    document.getElementById('add-lab-order').addEventListener('click', function() {
        const container = document.getElementById('lab-orders-container');
        const newRow = document.createElement('div');
        newRow.className = 'row lab-order-row mb-3';
        newRow.innerHTML = `
            <div class="col-md-3">
                <label class="form-label">Test Category</label>
                <select class="form-select test-category-select" name="lab_orders[${labOrderIndex}][category]">
                    <option value="">Select Category</option>
                    @foreach(\App\Models\LabTestType::active()->select('category')->distinct()->get() as $category)
                        <option value="{{ $category->category }}">{{ $category->category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Test Name</label>
                <select class="form-select test-type-select" name="lab_orders[${labOrderIndex}][test_type_id]">
                    <option value="">Select Test</option>
                    @foreach(\App\Models\LabTestType::active()->get() as $testType)
                        <option value="{{ $testType->id }}" 
                                data-category="{{ $testType->category }}"
                                data-specimen="{{ $testType->specimen_type }}"
                                data-collection="{{ json_encode($testType->collection_instructions) }}"
                                data-preparation="{{ json_encode($testType->preparation_instructions) }}">
                            {{ $testType->test_name }} ({{ $testType->test_code }})
                        </option>
                    @endforeach
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
        container.appendChild(newRow);
        labOrderIndex++;
        updateLabOrderRemoveButtons();
    });

    // Remove lab order
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-lab-order')) {
            e.target.closest('.lab-order-row').remove();
            updateLabOrderRemoveButtons();
        }
    });

    function updateLabOrderRemoveButtons() {
        const labOrderRows = document.querySelectorAll('.lab-order-row');
        labOrderRows.forEach((row, index) => {
            const removeBtn = row.querySelector('.remove-lab-order');
            if (labOrderRows.length > 1) {
                removeBtn.style.display = 'inline-block';
            } else {
                removeBtn.style.display = 'none';
            }
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
                    <option value="emergency">Emergency</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Clinical Question/Indication</label>
                <input type="text" class="form-control" name="radiology_orders[${radiologyOrderIndex}][clinical_question]" 
                       placeholder="e.g., Rule out pneumonia">
            </div>
            <div class="col-md-12 mt-2">
                <label class="form-label">Clinical History</label>
                <textarea class="form-control" name="radiology_orders[${radiologyOrderIndex}][clinical_history]" rows="2" 
                          placeholder="Brief clinical history relevant to this imaging request..."></textarea>
            </div>
            <div class="col-md-12 mt-2">
                <label class="form-label">Indication</label>
                <textarea class="form-control" name="radiology_orders[${radiologyOrderIndex}][indication]" rows="2" 
                          placeholder="Specific indication for this imaging study..."></textarea>
            </div>
            <div class="col-md-6 mt-2">
                <label class="form-label">Scheduled Date (Optional)</label>
                <input type="date" class="form-control" name="radiology_orders[${radiologyOrderIndex}][scheduled_date]" 
                       min="{{ date('Y-m-d') }}">
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

    // Remove radiology order
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-radiology-order')) {
            e.target.closest('.radiology-order-row').remove();
            updateRadiologyOrderRemoveButtons();
        }
    });

    function updateRadiologyOrderRemoveButtons() {
        const radiologyOrderRows = document.querySelectorAll('.radiology-order-row');
        radiologyOrderRows.forEach((row, index) => {
            const removeBtn = row.querySelector('.remove-radiology-order');
            if (radiologyOrderRows.length > 1) {
                removeBtn.style.display = 'inline-block';
            } else {
                removeBtn.style.display = 'none';
            }
        });
    }

    // Test category change handler - filter test names by category
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('test-category-select')) {
            const selectedCategory = e.target.value;
            const labOrderRow = e.target.closest('.lab-order-row');
            const testTypeSelect = labOrderRow.querySelector('.test-type-select');
            
            if (testTypeSelect) {
                // Reset test type selection
                testTypeSelect.value = '';
                
                // Show/hide options based on category
                const options = testTypeSelect.querySelectorAll('option');
                options.forEach(option => {
                    if (option.value === '') {
                        // Always show the "Select Test" option
                        option.style.display = '';
                    } else {
                        const optionCategory = option.dataset.category;
                        if (selectedCategory && optionCategory === selectedCategory) {
                            option.style.display = '';
                        } else if (!selectedCategory) {
                            // If no category selected, show all options
                            option.style.display = '';
                        } else {
                            option.style.display = 'none';
                        }
                    }
                });
                
                // Clear specimen type and instructions when category changes
                const specimenInput = labOrderRow.querySelector('.specimen-type-display');
                if (specimenInput) {
                    specimenInput.value = '';
                }
                
                const instructionsRow = labOrderRow.nextElementSibling;
                if (instructionsRow && instructionsRow.classList.contains('lab-instructions-row')) {
                    const collectionTextarea = instructionsRow.querySelector('.collection-instructions');
                    const preparationTextarea = instructionsRow.querySelector('.preparation-instructions');
                    if (collectionTextarea) collectionTextarea.value = '';
                    if (preparationTextarea) preparationTextarea.value = '';
                    instructionsRow.style.display = 'none';
                }
            }
        }
    });
    
    // Lab test type change handler
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('test-type-select')) {
            const selectedOption = e.target.selectedOptions[0];
            if (selectedOption) {
                const specimenType = selectedOption.dataset.specimen;
                const collectionInstructions = selectedOption.dataset.collection;
                const preparationInstructions = selectedOption.dataset.preparation;
                
                // Update specimen type
                const specimenInput = e.target.closest('.lab-order-row').querySelector('.specimen-type-display');
                if (specimenInput) {
                    specimenInput.value = specimenType || '';
                }
                
                // Update instructions
                const instructionsRow = e.target.closest('.lab-order-row').nextElementSibling;
                if (instructionsRow && instructionsRow.classList.contains('lab-instructions-row')) {
                    const collectionTextarea = instructionsRow.querySelector('.collection-instructions');
                    const preparationTextarea = instructionsRow.querySelector('.preparation-instructions');
                    
                    if (collectionTextarea) {
                        collectionTextarea.value = collectionInstructions ? JSON.parse(collectionInstructions) : '';
                    }
                    if (preparationTextarea) {
                        preparationTextarea.value = preparationInstructions ? JSON.parse(preparationInstructions) : '';
                    }
                    
                    instructionsRow.style.display = 'block';
                }
            }
        }
    });

    // Form submission with workflow determination
    document.getElementById('consultationForm').addEventListener('submit', function(e) {
        const submitButton = e.submitter || document.activeElement;
        const isDraftButton = submitButton && submitButton.name === 'is_draft' && submitButton.value === '1';
        
        // If saving as draft, skip validation
        if (isDraftButton) {
            // Add is_draft hidden input
            const draftInput = document.createElement('input');
            draftInput.type = 'hidden';
            draftInput.name = 'is_draft';
            draftInput.value = '1';
            this.appendChild(draftInput);
            return true; // Allow form submission
        }
        
        // For completion, validate diagnosis
        const diagnosis = document.getElementById('doctors_impression');
        if (diagnosis && !diagnosis.value.trim()) {
            e.preventDefault();
            alert('Please provide a diagnosis before completing the consultation.');
            return false;
        }

        // Add consultation_status for completion
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'consultation_status';
        statusInput.value = 'completed';
        this.appendChild(statusInput);
        
        // Add is_draft = false for completion
        const draftInput = document.createElement('input');
        draftInput.type = 'hidden';
        draftInput.name = 'is_draft';
        draftInput.value = '0';
        this.appendChild(draftInput);

           // Determine next stage based on diagnosis and orders
           const prescriptionOrders = document.querySelectorAll('.prescription-order-row');
           const labOrders = document.querySelectorAll('.lab-order-row');
           const radiologyOrders = document.querySelectorAll('.radiology-order-row');
           
           let nextStage = 'completed';
           let hasPrescriptions = false;
           let hasLabOrders = false;
           let hasRadiologyOrders = false;
           
           // Check for valid prescription orders
           prescriptionOrders.forEach(row => {
               const drugSelect = row.querySelector('.drug-select');
               if (drugSelect && drugSelect.value) {
                   hasPrescriptions = true;
               }
           });
           
           // Check for valid lab orders
           labOrders.forEach(row => {
               const testSelect = row.querySelector('.test-type-select');
               if (testSelect && testSelect.value) {
                   hasLabOrders = true;
               }
           });
           
           // Check for valid radiology orders
           radiologyOrders.forEach(row => {
               const modalitySelect = row.querySelector('.modality-select');
               const departmentSelect = row.querySelector('.department-select');
               if (modalitySelect && modalitySelect.value && departmentSelect && departmentSelect.value) {
                   hasRadiologyOrders = true;
               }
           });
           
           // Determine next stage based on all orders
           if (hasPrescriptions && hasLabOrders && hasRadiologyOrders) {
               nextStage = 'pharmacy_lab_radiology';
           } else if (hasPrescriptions && hasLabOrders) {
               nextStage = 'pharmacy_lab';
           } else if (hasPrescriptions && hasRadiologyOrders) {
               nextStage = 'pharmacy_radiology';
           } else if (hasLabOrders && hasRadiologyOrders) {
               nextStage = 'lab_radiology';
           } else if (hasPrescriptions) {
               nextStage = 'pharmacy';
           } else if (hasLabOrders) {
               nextStage = 'laboratory';
           } else if (hasRadiologyOrders) {
               nextStage = 'radiology';
           }

        // Add workflow information to form
        const workflowInput = document.createElement('input');
        workflowInput.type = 'hidden';
        workflowInput.name = 'next_stage';
        workflowInput.value = nextStage;
        this.appendChild(workflowInput);
    });
});
</script>
@endsection
