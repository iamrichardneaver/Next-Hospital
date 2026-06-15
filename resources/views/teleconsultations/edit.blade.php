@extends('layouts.app')

@section('title', 'Edit Teleconsultation')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Edit Teleconsultation</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('teleconsultations.index') }}">Teleconsultations</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('teleconsultations.show', $teleconsultation) }}">Details</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('teleconsultations.show', $teleconsultation) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Content -->
    <form action="{{ route('teleconsultations.update', $teleconsultation) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark">Teleconsultation Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><span class="text-danger">*</span> Patient</label>
                                <select name="patient_id" class="form-select @error('patient_id') is-invalid @enderror" required disabled>
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}" {{ $teleconsultation->patient_id == $patient->id ? 'selected' : '' }}>
                                            {{ $patient->first_name }} {{ $patient->last_name }} - {{ $patient->phone }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="patient_id" value="{{ $teleconsultation->patient_id }}">
                                @error('patient_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Patient cannot be changed after creation</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><span class="text-danger">*</span> Doctor</label>
                                <select name="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror" required disabled>
                                    <option value="">Select Doctor</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" {{ $teleconsultation->doctor_id == $doctor->id ? 'selected' : '' }}>
                                            Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="doctor_id" value="{{ $teleconsultation->doctor_id }}">
                                @error('doctor_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Doctor cannot be changed after creation</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><span class="text-danger">*</span> Scheduled Date & Time</label>
                                <input type="datetime-local" name="scheduled_at" class="form-control @error('scheduled_at') is-invalid @enderror" 
                                       value="{{ $teleconsultation->scheduled_at->format('Y-m-d\TH:i') }}" required>
                                @error('scheduled_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><span class="text-danger">*</span> Consultation Type</label>
                                <select name="consultation_type" class="form-select @error('consultation_type') is-invalid @enderror" required>
                                    <option value="video" {{ $teleconsultation->consultation_type == 'video' ? 'selected' : '' }}>
                                        <i class="bi bi-camera-video"></i> Video Call
                                    </option>
                                    <option value="audio" {{ $teleconsultation->consultation_type == 'audio' ? 'selected' : '' }}>
                                        <i class="bi bi-mic"></i> Audio Call
                                    </option>
                                    <option value="chat" {{ $teleconsultation->consultation_type == 'chat' ? 'selected' : '' }}>
                                        <i class="bi bi-chat"></i> Chat Only
                                    </option>
                                </select>
                                @error('consultation_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="scheduled" {{ $teleconsultation->status == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                    <option value="waiting" {{ $teleconsultation->status == 'waiting' ? 'selected' : '' }}>Waiting</option>
                                    <option value="in_progress" {{ $teleconsultation->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ $teleconsultation->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ $teleconsultation->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    <option value="failed" {{ $teleconsultation->status == 'failed' ? 'selected' : '' }}>Failed</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Outcome</label>
                                <select name="outcome" class="form-select @error('outcome') is-invalid @enderror">
                                    <option value="">Select Outcome</option>
                                    <option value="successful" {{ $teleconsultation->outcome == 'successful' ? 'selected' : '' }}>Successful</option>
                                    <option value="partial" {{ $teleconsultation->outcome == 'partial' ? 'selected' : '' }}>Partial</option>
                                    <option value="failed" {{ $teleconsultation->outcome == 'failed' ? 'selected' : '' }}>Failed</option>
                                    <option value="requires_follow_up" {{ $teleconsultation->outcome == 'requires_follow_up' ? 'selected' : '' }}>Requires Follow-up</option>
                                </select>
                                @error('outcome')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Consultation Notes</label>
                                <textarea name="consultation_notes" class="form-control @error('consultation_notes') is-invalid @enderror" 
                                          rows="4" placeholder="Enter any notes about this teleconsultation...">{{ old('consultation_notes', $teleconsultation->consultation_notes) }}</textarea>
                                @error('consultation_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Follow-up Notes</label>
                                <textarea name="follow_up_notes" class="form-control @error('follow_up_notes') is-invalid @enderror" 
                                          rows="3" placeholder="Enter follow-up notes...">{{ old('follow_up_notes', $teleconsultation->follow_up_notes) }}</textarea>
                                @error('follow_up_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Follow-up Scheduled At</label>
                                <input type="datetime-local" name="follow_up_scheduled_at" class="form-control @error('follow_up_scheduled_at') is-invalid @enderror" 
                                       value="{{ $teleconsultation->follow_up_scheduled_at ? $teleconsultation->follow_up_scheduled_at->format('Y-m-d\TH:i') : '' }}">
                                @error('follow_up_scheduled_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label d-block">Options</label>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="requires_follow_up" value="1" 
                                           id="requires_follow_up" {{ $teleconsultation->requires_follow_up ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_follow_up">
                                        Requires Follow-up
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="patient_consent_given" value="1" 
                                           id="patient_consent_given" {{ $teleconsultation->patient_consent_given ? 'checked' : '' }}>
                                    <label class="form-check-label" for="patient_consent_given">
                                        Patient Consent Given
                                    </label>
                                </div>
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
                                   id="video_enabled" {{ $teleconsultation->video_enabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="video_enabled">
                                <i class="bi bi-camera-video text-primary"></i> Enable Video
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="audio_enabled" value="1" 
                                   id="audio_enabled" {{ $teleconsultation->audio_enabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="audio_enabled">
                                <i class="bi bi-mic text-success"></i> Enable Audio
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="recording_enabled" value="1" 
                                   id="recording_enabled" {{ $teleconsultation->recording_enabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="recording_enabled">
                                <i class="bi bi-record-circle text-danger"></i> Enable Recording
                            </label>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="fw-bold text-dark d-block mb-2">Meeting Information</label>
                            <div class="small">
                                <div class="mb-1"><strong>Meeting ID:</strong> {{ $teleconsultation->meeting_id }}</div>
                                @if($teleconsultation->meeting_password)
                                <div class="mb-1"><strong>Password:</strong> {{ $teleconsultation->meeting_password }}</div>
                                @endif
                                @if($teleconsultation->meeting_url)
                                <div class="mb-1"><strong>URL:</strong> <a href="{{ $teleconsultation->meeting_url }}" target="_blank" class="text-primary">View</a></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Teleconsultation
                            </button>
                            <a href="{{ route('teleconsultations.show', $teleconsultation) }}" class="btn btn-secondary">
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
