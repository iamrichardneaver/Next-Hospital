@extends('layouts.app')

@section('title', 'Create Radiology Request from Walk-in Visit')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Create Radiology Request</h1>
            <p class="text-secondary mb-0">Create radiology request for walk-in patient</p>
        </div>
        <a href="{{ route('walk-ins.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Walk-ins
        </a>
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
                    {{ $visit->patient->first_name }} {{ $visit->patient->last_name }}
                </div>
                <div class="col-md-3">
                    <strong>Patient Number:</strong><br>
                    {{ $visit->patient->patient_number }}
                </div>
                <div class="col-md-3">
                    <strong>Visit Token:</strong><br>
                    <span class="badge badge-light-primary">{{ $visit->visit_token }}</span>
                </div>
                <div class="col-md-3">
                    <strong>Visit Type:</strong><br>
                    <span class="badge badge-light-info">{{ $visit->visit_type }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clipboard-plus"></i> Imaging Request Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('radiology.walk-in.store', $visit->id) }}" method="POST" id="radiologyRequestForm">
                        @csrf
                        <input type="hidden" name="from_walk_in" value="1">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
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
                                                {{ $doctor->first_name }} {{ $doctor->last_name }} - {{ $doctor->specialization ?? 'General' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('doctor_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label for="radiologist_id" class="form-label">Assigned Radiologist</label>
                                <select class="form-select @error('radiologist_id') is-invalid @enderror" id="radiologist_id" name="radiologist_id">
                                    <option value="">Select Radiologist (Optional)</option>
                                    @php
                                        $radiologists = \App\Models\User::whereHas('roles', function($q) {
                                            $q->where('name', 'radiologist');
                                        })->orderBy('first_name')->get();
                                    @endphp
                                    @foreach($radiologists as $radiologist)
                                        <option value="{{ $radiologist->id }}" {{ old('radiologist_id') == $radiologist->id ? 'selected' : '' }}>
                                            Dr. {{ $radiologist->first_name }} {{ $radiologist->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Select the radiologist who will review and report on this study</small>
                                @error('radiologist_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
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
                            <div class="col-md-6">
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

                        <div class="row mb-3">
                            <div class="col-md-6">
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
                            <div class="col-md-6">
                                <label for="scheduled_date" class="form-label">Scheduled Date</label>
                                <input type="date" class="form-control @error('scheduled_date') is-invalid @enderror" 
                                       id="scheduled_date" name="scheduled_date" 
                                       value="{{ old('scheduled_date') }}" min="{{ date('Y-m-d') }}">
                                @error('scheduled_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="scheduled_date" class="form-label">Scheduled Date</label>
                                <input type="date" class="form-control @error('scheduled_date') is-invalid @enderror" 
                                       id="scheduled_date" name="scheduled_date" 
                                       value="{{ old('scheduled_date') }}" min="{{ date('Y-m-d') }}">
                                @error('scheduled_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="scheduled_time" class="form-label">Scheduled Time</label>
                                <input type="time" class="form-control @error('scheduled_time') is-invalid @enderror" 
                                       id="scheduled_time" name="scheduled_time" 
                                       value="{{ old('scheduled_time') }}">
                                @error('scheduled_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

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

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('walk-ins.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

