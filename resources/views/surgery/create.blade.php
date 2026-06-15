@extends('layouts.app')

@section('title', 'Schedule Surgery')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-scissors me-2"></i>Schedule Surgery</h1>
        <a href="{{ route('surgery.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('surgery.store') }}" method="POST">
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

                        <div class="mb-3">
                            <label class="form-label">Procedure <span class="text-danger">*</span></label>
                            <select name="procedure_id" class="form-select @error('procedure_id') is-invalid @enderror" required>
                                <option value="">Select procedure</option>
                                @foreach($procedures as $procedure)
                                    <option value="{{ $procedure->id }}" @selected(old('procedure_id') == $procedure->id)>
                                        {{ $procedure->name }} ({{ ucfirst($procedure->procedure_type ?? 'general') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('procedure_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lead Surgeon <span class="text-danger">*</span></label>
                                <select name="surgeon_id" class="form-select" required>
                                    <option value="">Select surgeon</option>
                                    @foreach($surgeons as $surgeon)
                                        <option value="{{ $surgeon->id }}" @selected(old('surgeon_id') == $surgeon->id)>Dr. {{ $surgeon->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Theatre <span class="text-danger">*</span></label>
                                <select name="theatre_id" class="form-select" required>
                                    <option value="">Select theatre</option>
                                    @foreach($theatres as $theatre)
                                        <option value="{{ $theatre->id }}" @selected(old('theatre_id') == $theatre->id)>{{ $theatre->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assistant Surgeon</label>
                                <select name="assistant_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($surgeons as $surgeon)
                                        <option value="{{ $surgeon->id }}" @selected(old('assistant_id') == $surgeon->id)>Dr. {{ $surgeon->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Anaesthetist</label>
                                <select name="anaesthetist_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($surgeons as $surgeon)
                                        <option value="{{ $surgeon->id }}" @selected(old('anaesthetist_id') == $surgeon->id)>Dr. {{ $surgeon->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Surgery Date <span class="text-danger">*</span></label>
                                <input type="date" name="surgery_date" class="form-control" value="{{ old('surgery_date') }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Surgery Time <span class="text-danger">*</span></label>
                                <input type="time" name="surgery_time" class="form-control" value="{{ old('surgery_time', '08:00') }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Duration (mins) <span class="text-danger">*</span></label>
                                <input type="number" name="estimated_duration" class="form-control" min="15" value="{{ old('estimated_duration', 60) }}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    @foreach(['elective','urgent','emergency'] as $p)
                                        <option value="{{ $p }}" @selected(old('priority', 'elective') === $p)>{{ ucfirst($p) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Surgery Type</label>
                                <select name="surgery_type" class="form-select">
                                    @foreach(['major','minor','diagnostic','therapeutic'] as $t)
                                        <option value="{{ $t }}" @selected(old('surgery_type', 'major') === $t)>{{ ucfirst($t) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Anesthesia Type</label>
                                <select name="anesthesia_type" class="form-select">
                                    @foreach(['general','regional','local','conscious_sedation'] as $a)
                                        <option value="{{ $a }}" @selected(old('anesthesia_type', 'general') === $a)>{{ ucfirst(str_replace('_', ' ', $a)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('surgery.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Schedule Surgery</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
