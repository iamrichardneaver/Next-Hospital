@extends('layouts.app')

@section('title', 'New Radiology Request')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">New Radiology Request</h1>
                <p class="page-subtitle">Create a new radiology imaging request</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('radiology.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Requests
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('radiology.store') }}" method="POST" id="radiologyForm">
        @csrf
        
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
                                            <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
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
                                    {{-- Doctors can only create requests for themselves --}}
                                    <input type="text" class="form-control" value="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }} - {{ auth()->user()->specialization ?? 'General' }}" disabled>
                                        <input type="hidden" name="doctor_id" value="{{ auth()->id() }}">
                                        <small class="form-text text-muted">You can only create radiology requests for yourself</small>
                                    @else
                                        <select class="form-select @error('doctor_id') is-invalid @enderror" id="doctor_id" name="doctor_id" required>
                                            <option value="">Select Doctor</option>
                                            @foreach($doctors as $doctor)
                                                <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>
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
                                        <option value="">Select Priority</option>
                                        <option value="routine" {{ old('priority') == 'routine' ? 'selected' : '' }}>Routine</option>
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
                                      id="clinical_history" name="clinical_history" rows="4" required>{{ old('clinical_history') }}</textarea>
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
                            <a href="{{ route('radiology.index') }}" class="btn btn-secondary">
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
