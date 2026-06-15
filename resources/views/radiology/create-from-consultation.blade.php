@extends('layouts.app')

@section('title', 'Request Imaging from Consultation')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Request Imaging</h1>
                <p class="page-subtitle">Create radiology request from consultation</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('consultations.show', $consultation) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Consultation
                </a>
            </div>
        </div>
    </div>

    <!-- Patient Information Card -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-person-check"></i> Patient Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Patient Name:</strong><br>
                    {{ $consultation->patient->first_name }} {{ $consultation->patient->last_name }}
                </div>
                <div class="col-md-3">
                    <strong>Patient Number:</strong><br>
                    {{ $consultation->patient->patient_number }}
                </div>
                <div class="col-md-3">
                    <strong>Consultation Date:</strong><br>
                    {{ $consultation->consultation_date->format('d/m/Y') }}
                </div>
                <div class="col-md-3">
                    <strong>Doctor:</strong><br>
                    Dr. {{ $consultation->doctor->first_name ?? '' }} {{ $consultation->doctor->last_name ?? '' }}
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('radiology.store') }}" method="POST" id="radiologyForm">
        @csrf
        <input type="hidden" name="consultation_id" value="{{ $consultation->id }}">
        <input type="hidden" name="patient_id" value="{{ $consultation->patient_id }}">
        <input type="hidden" name="doctor_id" value="{{ $consultation->doctor_id }}">
        <input type="hidden" name="from_consultation" value="1">
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Imaging Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Imaging Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="modality_id" class="form-label">Imaging Modality <span class="text-danger">*</span></label>
                                    <select class="form-select @error('modality_id') is-invalid @enderror" id="modality_id" name="modality_id" required>
                                        <option value="">Select Modality</option>
                                        @foreach($modalities as $modality)
                                            <option value="{{ $modality->id }}" {{ old('modality_id') == $modality->id ? 'selected' : '' }}>
                                                {{ $modality->name }} ({{ $modality->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('modality_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                                    <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                        <option value="">Select Department</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                    <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                        <option value="routine" {{ old('priority', 'routine') == 'routine' ? 'selected' : '' }}>Routine</option>
                                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                        <option value="stat" {{ old('priority') == 'stat' ? 'selected' : '' }}>STAT</option>
                                        <option value="emergency" {{ old('priority') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="scheduled_date" class="form-label">Scheduled Date</label>
                                    <input type="date" class="form-control @error('scheduled_date') is-invalid @enderror" 
                                           id="scheduled_date" name="scheduled_date" 
                                           value="{{ old('scheduled_date') }}" min="{{ date('Y-m-d') }}">
                                    @error('scheduled_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="scheduled_time" class="form-label">Scheduled Time</label>
                                    <input type="time" class="form-control @error('scheduled_time') is-invalid @enderror" 
                                           id="scheduled_time" name="scheduled_time" 
                                           value="{{ old('scheduled_time') }}">
                                    @error('scheduled_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clinical Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Clinical Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="clinical_history" class="form-label">Clinical History <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('clinical_history') is-invalid @enderror" 
                                      id="clinical_history" name="clinical_history" rows="4" required>{{ old('clinical_history', $consultation->chief_complaint . "\n\n" . $consultation->history_of_present_illness) }}</textarea>
                            @error('clinical_history')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="clinical_question" class="form-label">Clinical Question <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('clinical_question') is-invalid @enderror" 
                                      id="clinical_question" name="clinical_question" rows="3" required>{{ old('clinical_question') }}</textarea>
                            @error('clinical_question')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="indication" class="form-label">Indication <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('indication') is-invalid @enderror" 
                                      id="indication" name="indication" rows="3" required>{{ old('indication') }}</textarea>
                            @error('indication')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('consultations.show', $consultation) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/ckeditor-config.js') }}"></script>
@endpush

