@extends('layouts.app')

@section('title', 'Admit to ICU')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-heart-pulse me-2"></i>Admit Patient to ICU</h1>
        <a href="{{ route('icu.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('icu.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Patient <span class="text-danger">*</span></label>
                            <select name="patient_id" class="form-select @error('patient_id') is-invalid @enderror" required>
                                <option value="">Select patient</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}" @selected(old('patient_id') == $patient->id)>
                                        {{ $patient->patient_number }} — {{ $patient->full_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Active IPD Visit</label>
                                <select name="visit_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($visits as $visit)
                                        <option value="{{ $visit->id }}" @selected(old('visit_id') == $visit->id)>
                                            {{ $visit->visit_token }} — {{ $visit->patient?->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ICU Bed</label>
                                <select name="bed_id" class="form-select">
                                    <option value="">Unassigned</option>
                                    @foreach($beds as $bed)
                                        <option value="{{ $bed->id }}" @selected(old('bed_id') == $bed->id)>
                                            {{ $bed->ward?->name }} — Bed {{ $bed->bed_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Admission Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="admission_time" class="form-control @error('admission_time') is-invalid @enderror"
                                    value="{{ old('admission_time', now()->format('Y-m-d\TH:i')) }}" required>
                                @error('admission_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Admission Type <span class="text-danger">*</span></label>
                                <select name="admission_type" class="form-select" required>
                                    <option value="emergency" @selected(old('admission_type') === 'emergency')>Emergency</option>
                                    <option value="elective" @selected(old('admission_type') === 'elective')>Elective</option>
                                    <option value="transfer" @selected(old('admission_type') === 'transfer')>Transfer</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Attending Doctor <span class="text-danger">*</span></label>
                                <select name="attending_doctor_id" class="form-select" required>
                                    <option value="">Select doctor</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" @selected(old('attending_doctor_id', auth()->id()) == $doctor->id)>
                                            Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assigned Nurse</label>
                                <select name="assigned_nurse_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($nurses as $nurse)
                                        <option value="{{ $nurse->id }}" @selected(old('assigned_nurse_id') == $nurse->id)>
                                            {{ $nurse->first_name }} {{ $nurse->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Patient Condition</label>
                            <select name="patient_condition" class="form-select">
                                <option value="stable">Stable</option>
                                <option value="serious" selected>Serious</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Admission Diagnosis</label>
                            <textarea name="admission_diagnosis" class="form-control" rows="2">{{ old('admission_diagnosis') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Chief Complaint</label>
                            <textarea name="chief_complaint" class="form-control" rows="2">{{ old('chief_complaint') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-danger"><i class="bi bi-heart-pulse"></i> Admit to ICU</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
