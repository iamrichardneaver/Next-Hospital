@extends('layouts.app')

@section('title', 'Create Teleconsultation')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Create Teleconsultation</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('teleconsultations.index') }}">Teleconsultations</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('teleconsultations.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Content -->
    <form action="{{ route('teleconsultations.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark">Teleconsultation Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><span class="text-danger">*</span> Patient</label>
                                <select name="patient_id" class="form-select @error('patient_id') is-invalid @enderror" required>
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                                            {{ $patient->first_name }} {{ $patient->last_name }} - {{ $patient->phone }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('patient_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><span class="text-danger">*</span> Doctor</label>
                                @if(auth()->user()->hasRole('doctor'))
                                    {{-- Doctors can only create for themselves --}}
                                    <input type="text" class="form-control" value="Dr. {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}" disabled>
                                    <input type="hidden" name="doctor_id" value="{{ auth()->id() }}">
                                    <small class="form-text text-muted">You can only create teleconsultations for yourself</small>
                                @else
                                    <select name="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror" required>
                                        <option value="">Select Doctor</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>
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
                                <label class="form-label"><span class="text-danger">*</span> Scheduled Date & Time</label>
                                <input type="datetime-local" name="scheduled_at" class="form-control @error('scheduled_at') is-invalid @enderror" 
                                       value="{{ old('scheduled_at') }}" required>
                                @error('scheduled_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><span class="text-danger">*</span> Consultation Type</label>
                                <select name="consultation_type" class="form-select @error('consultation_type') is-invalid @enderror" required>
                                    <option value="">Select Type</option>
                                    <option value="video" {{ old('consultation_type') == 'video' ? 'selected' : '' }}>
                                        <i class="bi bi-camera-video"></i> Video Call
                                    </option>
                                    <option value="audio" {{ old('consultation_type') == 'audio' ? 'selected' : '' }}>
                                        <i class="bi bi-mic"></i> Audio Call
                                    </option>
                                    <option value="chat" {{ old('consultation_type') == 'chat' ? 'selected' : '' }}>
                                        <i class="bi bi-chat"></i> Chat Only
                                    </option>
                                </select>
                                @error('consultation_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Consultation Notes</label>
                                <textarea name="consultation_notes" class="form-control @error('consultation_notes') is-invalid @enderror" 
                                          rows="4" placeholder="Enter any notes about this teleconsultation...">{{ old('consultation_notes') }}</textarea>
                                @error('consultation_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <!-- Meeting Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark">Meeting Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="video_enabled" value="1" 
                                   id="video_enabled" {{ old('video_enabled', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="video_enabled">
                                <i class="bi bi-camera-video text-primary"></i> Enable Video
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="audio_enabled" value="1" 
                                   id="audio_enabled" {{ old('audio_enabled', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="audio_enabled">
                                <i class="bi bi-mic text-success"></i> Enable Audio
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="recording_enabled" value="1" 
                                   id="recording_enabled" {{ old('recording_enabled') ? 'checked' : '' }}>
                            <label class="form-check-label" for="recording_enabled">
                                <i class="bi bi-record-circle text-danger"></i> Enable Recording
                            </label>
                        </div>

                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Jitsi Meet Integration</h6>
                                <p class="mb-0 small">This teleconsultation will use Jitsi Meet for video conferencing. The meeting link will be generated automatically.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Teleconsultation
                            </button>
                            <a href="{{ route('teleconsultations.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
