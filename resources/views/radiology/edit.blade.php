@extends('layouts.app')

@section('title', 'Edit Radiology Request')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Edit Radiology Request</h1>
                <p class="page-subtitle">Request #{{ $radiology->request_number }}</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('radiology.show', $radiology) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Details
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('radiology.update', $radiology) }}" method="POST" id="radiologyForm">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Patient Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Patient Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                                    <select class="form-select @error('patient_id') is-invalid @enderror" id="patient_id" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        @foreach($patients as $patient)
                                            <option value="{{ $patient->id }}" {{ old('patient_id', $radiology->patient_id) == $patient->id ? 'selected' : '' }}>
                                                {{ $patient->patient_number }} - {{ $patient->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('patient_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="doctor_id" class="form-label">Requesting Doctor <span class="text-danger">*</span></label>
                                    @if(auth()->user()->hasRole('doctor'))
                                        {{-- Doctors cannot change requesting doctor - show as read-only --}}
                                        <input type="text" class="form-control" value="{{ $radiology->doctor->first_name ?? '' }} {{ $radiology->doctor->last_name ?? '' }} - {{ $radiology->doctor->specialization ?? 'General' }}" disabled>
                                        <input type="hidden" name="doctor_id" value="{{ $radiology->doctor_id }}">
                                        <small class="form-text text-muted">Requesting doctor cannot be changed</small>
                                    @else
                                        <select class="form-select @error('doctor_id') is-invalid @enderror" id="doctor_id" name="doctor_id" required>
                                            <option value="">Select Doctor</option>
                                            @foreach($doctors as $doctor)
                                                <option value="{{ $doctor->id }}" {{ old('doctor_id', $radiology->doctor_id) == $doctor->id ? 'selected' : '' }}>
                                                    {{ $doctor->full_name ?? ($doctor->first_name . ' ' . $doctor->last_name) }} - {{ $doctor->specialization ?? 'General' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('doctor_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                                            <option value="{{ $modality->id }}" {{ old('modality_id', $radiology->modality_id) == $modality->id ? 'selected' : '' }}>
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
                                            <option value="{{ $department->id }}" {{ old('department_id', $radiology->department_id) == $department->id ? 'selected' : '' }}>
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
                                        <option value="">Select Priority</option>
                                        <option value="routine" {{ old('priority', $radiology->priority) == 'routine' ? 'selected' : '' }}>Routine</option>
                                        <option value="urgent" {{ old('priority', $radiology->priority) == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                        <option value="stat" {{ old('priority', $radiology->priority) == 'stat' ? 'selected' : '' }}>STAT</option>
                                        <option value="emergency" {{ old('priority', $radiology->priority) == 'emergency' ? 'selected' : '' }}>Emergency</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                        <option value="requested" {{ old('status', $radiology->status) == 'requested' ? 'selected' : '' }}>Requested</option>
                                        <option value="scheduled" {{ old('status', $radiology->status) == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                        <option value="in_progress" {{ old('status', $radiology->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="completed" {{ old('status', $radiology->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="cancelled" {{ old('status', $radiology->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                        <option value="rejected" {{ old('status', $radiology->status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="scheduled_date" class="form-label">Scheduled Date</label>
                                    <input type="date" class="form-control @error('scheduled_date') is-invalid @enderror" 
                                           id="scheduled_date" name="scheduled_date" 
                                           value="{{ old('scheduled_date', $radiology->scheduled_date?->format('Y-m-d')) }}">
                                    @error('scheduled_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="scheduled_time" class="form-label">Scheduled Time</label>
                                    <input type="time" class="form-control @error('scheduled_time') is-invalid @enderror" 
                                           id="scheduled_time" name="scheduled_time" 
                                           value="{{ old('scheduled_time', $radiology->scheduled_time?->format('H:i')) }}">
                                    @error('scheduled_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Staff Assignment -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Staff Assignment</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="technician_id" class="form-label">Technician</label>
                                    <select class="form-select @error('technician_id') is-invalid @enderror" id="technician_id" name="technician_id">
                                        <option value="">Select Technician</option>
                                        @foreach($technicians as $technician)
                                            <option value="{{ $technician->id }}" {{ old('technician_id', $radiology->technician_id) == $technician->id ? 'selected' : '' }}>
                                                {{ $technician->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('technician_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="radiologist_id" class="form-label">Radiologist</label>
                                    <select class="form-select @error('radiologist_id') is-invalid @enderror" id="radiologist_id" name="radiologist_id">
                                        <option value="">Select Radiologist</option>
                                        @foreach($radiologists as $radiologist)
                                            <option value="{{ $radiologist->id }}" {{ old('radiologist_id', $radiology->radiologist_id) == $radiologist->id ? 'selected' : '' }}>
                                                {{ $radiologist->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('radiologist_id')
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
                                      id="clinical_history" name="clinical_history" rows="4" required>{{ old('clinical_history', $radiology->clinical_history) }}</textarea>
                            @error('clinical_history')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="clinical_question" class="form-label">Clinical Question <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('clinical_question') is-invalid @enderror" 
                                      id="clinical_question" name="clinical_question" rows="3" required>{{ old('clinical_question', $radiology->clinical_question) }}</textarea>
                            @error('clinical_question')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="indication" class="form-label">Indication <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('indication') is-invalid @enderror" 
                                      id="indication" name="indication" rows="3" required>{{ old('indication', $radiology->indication) }}</textarea>
                            @error('indication')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Additional Notes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Additional Notes</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="technician_notes" class="form-label">Technician Notes</label>
                            <textarea class="form-control @error('technician_notes') is-invalid @enderror" 
                                      id="technician_notes" name="technician_notes" rows="3">{{ old('technician_notes', $radiology->technician_notes) }}</textarea>
                            @error('technician_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">Rejection Reason</label>
                            <textarea class="form-control @error('rejection_reason') is-invalid @enderror" 
                                      id="rejection_reason" name="rejection_reason" rows="3">{{ old('rejection_reason', $radiology->rejection_reason) }}</textarea>
                            @error('rejection_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('radiology.show', $radiology) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Request
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
