@extends('layouts.app')

@section('title', 'Edit Visit')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1" style="color: #1e3a5f;">Edit Visit</h1><p class="text-secondary mb-0">Visit Token: {{ $visit->visit_token }}</p></div>
        <a href="{{ route('visits.show', $visit) }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('visits.update', $visit) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                            <select class="form-select @error('patient_id') is-invalid @enderror" id="patient_id" name="patient_id" required>
                                <option value="">Select Patient</option>
                                @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" 
                                    @if(old('patient_id', $visit->patient_id) == $patient->id) selected @endif>
                                    {{ $patient->patient_number }} - {{ $patient->full_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="visit_type" class="form-label">Visit Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('visit_type') is-invalid @enderror" id="visit_type" name="visit_type" required>
                                    <option value="">Select Type</option>
                                    <option value="OPD" @if(old('visit_type', $visit->visit_type) == 'OPD') selected @endif>OPD (Outpatient)</option>
                                    <option value="IPD" @if(old('visit_type', $visit->visit_type) == 'IPD') selected @endif>IPD (Inpatient)</option>
                                    <option value="Emergency" @if(old('visit_type', $visit->visit_type) == 'Emergency') selected @endif>Emergency</option>
                                    <option value="LabOnly" @if(old('visit_type', $visit->visit_type) == 'LabOnly') selected @endif>Lab Only</option>
                                    <option value="PharmacyOnly" @if(old('visit_type', $visit->visit_type) == 'PharmacyOnly') selected @endif>Pharmacy Only</option>
                                </select>
                                @error('visit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="routine" @if(old('priority', $visit->priority) == 'routine') selected @endif>Routine</option>
                                    <option value="urgent" @if(old('priority', $visit->priority) == 'urgent') selected @endif>Urgent</option>
                                    <option value="critical" @if(old('priority', $visit->priority) == 'critical') selected @endif>Critical</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="assigned_doctor_id" class="form-label">Assign Doctor</label>
                                @if(auth()->user()->hasRole('doctor'))
                                    {{-- Doctors cannot change assigned doctor - show as read-only --}}
                                    <input type="text" class="form-control" value="{{ $visit->assignedDoctor ? 'Dr. ' . $visit->assignedDoctor->first_name . ' ' . $visit->assignedDoctor->last_name : 'Not assigned' }}" disabled>
                                    <input type="hidden" name="assigned_doctor_id" value="{{ $visit->assigned_doctor_id ?? '' }}">
                                    <small class="form-text text-muted">Assigned doctor cannot be changed</small>
                                @else
                                    <select class="form-select" id="assigned_doctor_id" name="assigned_doctor_id">
                                        <option value="">Select Doctor</option>
                                        @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" @if(old('assigned_doctor_id', $visit->assigned_doctor_id) == $doctor->id) selected @endif>
                                            Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="assigned_nurse_id" class="form-label">Assign Nurse</label>
                                <select class="form-select" id="assigned_nurse_id" name="assigned_nurse_id">
                                    <option value="">Select Nurse</option>
                                    @foreach($nurses as $nurse)
                                    <option value="{{ $nurse->id }}" @if(old('assigned_nurse_id', $visit->assigned_nurse_id) == $nurse->id) selected @endif>
                                        {{ $nurse->first_name }} {{ $nurse->last_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="chief_complaint" class="form-label">Chief Complaint</label>
                            <textarea class="form-control" id="chief_complaint" name="chief_complaint" rows="3">{{ old('chief_complaint', $visit->chief_complaint) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="active" @if(old('status', $visit->status) == 'active') selected @endif>Active</option>
                                <option value="completed" @if(old('status', $visit->status) == 'completed') selected @endif>Completed</option>
                                <option value="cancelled" @if(old('status', $visit->status) == 'cancelled') selected @endif>Cancelled</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('visits.show', $visit) }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Visit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
