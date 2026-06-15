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
                <a href="{{ route('consultations.doctor-queue') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Doctor Queue
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('consultations.store') }}" method="POST" id="consultationForm">
        @csrf

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
                
                <!-- Hidden fields for patient and visit -->
                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                @if($visits->isNotEmpty())
                    <input type="hidden" name="visit_id" value="{{ $visits->first()->id }}">
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
                    </div>
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
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="consultation_date" class="form-label">Consultation Date <span class="text-danger">*</span></label>
                        <input type="date" 
                               class="form-control @error('consultation_date') is-invalid @enderror" 
                               id="consultation_date" 
                               name="consultation_date" 
                               value="{{ old('consultation_date', now()->format('Y-m-d')) }}" 
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
                                <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
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
                              required>{{ old('chief_complaint') }}</textarea>
                    @error('chief_complaint')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Additional Complaints</label>
                    <div id="additional-complaints">
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
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-complaint">
                        <i class="bi bi-plus"></i> Add Complaint
                    </button>
                </div>
            </div>
        </div>

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
                              placeholder="Detailed history of the current illness...">{{ old('history_of_present_illness') }}</textarea>
                    @error('history_of_present_illness')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
        @endcan

        <!-- Physical Examination Section -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-clipboard-pulse"></i> Physical Examination</h5>
            </div>
            <div class="card-body">
                @can('record_vitals')
                    <!-- Nurse can record and edit vitals -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="vitals" class="form-label">Vital Signs</label>
                            <textarea class="form-control @error('vitals') is-invalid @enderror" 
                                      id="vitals" 
                                      name="vitals" 
                                      rows="3" 
                                      placeholder="Blood pressure, temperature, pulse, etc.">{{ old('vitals') }}</textarea>
                            @error('vitals')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    <div class="col-md-6 mb-3">
                        <label for="physical_examination" class="form-label">Physical Examination Findings</label>
                        <textarea class="form-control @error('physical_examination') is-invalid @enderror" 
                                  id="physical_examination" 
                                  name="physical_examination" 
                                  rows="3" 
                                  placeholder="General appearance, systems examination...">{{ old('physical_examination') }}</textarea>
                        @error('physical_examination')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                @else
                    <!-- Doctor can only view vitals (read-only) -->
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Note:</strong> Only nurses can record vital signs. Please have a nurse record the patient's vitals before the consultation.
                    </div>
                @endcan
            </div>
        </div>

        <!-- Prescription Orders Section -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-capsule"></i> Prescription Orders</h5>
            </div>
            <div class="card-body">
                <div id="prescription-orders-container">
                    <div class="row prescription-order-row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Drug/Medication</label>
                            <select class="form-select drug-select" name="prescription_orders[0][drug_id]">
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
        </div>

        <!-- Lab Test Orders Section -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-flask"></i> Laboratory Orders</h5>
            </div>
            <div class="card-body">
                <div id="lab-orders-container">
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
                            <label class="form-label">Test Type</label>
                            <select class="form-select test-type-select" name="lab_orders[0][test_type_id]">
                                <option value="">Select Test Type</option>
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
                              required>{{ old('doctors_impression') }}</textarea>
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
                              placeholder="Medications, procedures, follow-up instructions...">{{ old('treatment_plan') }}</textarea>
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
    let complaintIndex = 1;
    let prescriptionIndex = 1;
    let labOrderIndex = 1;
    
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
                <label class="form-label">Test Type</label>
                <select class="form-select test-type-select" name="lab_orders[${labOrderIndex}][test_type_id]">
                    <option value="">Select Test Type</option>
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
        const diagnosis = document.getElementById('doctors_impression').value.trim();
        
        if (!diagnosis) {
            e.preventDefault();
            alert('Please provide a diagnosis before completing the consultation.');
            return false;
        }

        // Determine next stage based on diagnosis and orders
        const prescriptionOrders = document.querySelectorAll('.prescription-order-row');
        const labOrders = document.querySelectorAll('.lab-order-row');
        
        let nextStage = 'completed';
        let workflowMessage = 'Consultation completed successfully!';
        
        if (prescriptionOrders.length > 0 && prescriptionOrders[0].querySelector('.drug-select').value) {
            nextStage = 'pharmacy';
            workflowMessage += ' Patient has been directed to pharmacy for medication dispensing.';
        }
        
        if (labOrders.length > 0 && labOrders[0].querySelector('.test-type-select').value) {
            if (nextStage === 'pharmacy') {
                nextStage = 'pharmacy_lab';
                workflowMessage += ' Patient will proceed to laboratory after pharmacy.';
            } else {
                nextStage = 'laboratory';
                workflowMessage += ' Patient has been directed to laboratory for testing.';
            }
        }

        // Add workflow information to form
        const workflowInput = document.createElement('input');
        workflowInput.type = 'hidden';
        workflowInput.name = 'next_stage';
        workflowInput.value = nextStage;
        this.appendChild(workflowInput);

        // Show workflow message
        if (nextStage !== 'completed') {
            setTimeout(() => {
                alert(workflowMessage);
            }, 100);
        }
    });
});
</script>
@endsection
