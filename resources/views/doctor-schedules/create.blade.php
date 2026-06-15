@extends('layouts.app')

@section('title', 'Add Schedule')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">Add Schedule</h2>
                    <p class="text-muted mb-0">Set your weekly availability for appointments</p>
                </div>
                <a href="{{ route('doctor-schedules.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Schedules
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>Schedule Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('doctor-schedules.store') }}" method="POST">
                        @csrf

                        @if(!auth()->user()->hasRole('doctor'))
                        <div class="mb-3">
                            <label for="doctor_id" class="form-label">Doctor <span class="text-danger">*</span></label>
                            <select class="form-select @error('doctor_id') is-invalid @enderror" id="doctor_id" name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                        {{ $doctor->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('doctor_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @else
                        <input type="hidden" name="doctor_id" value="{{ auth()->id() }}">
                        @endif

                        <div class="mb-3">
                            <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                            <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="day_of_week" class="form-label">Day of Week <span class="text-danger">*</span></label>
                            <select class="form-select @error('day_of_week') is-invalid @enderror" id="day_of_week" name="day_of_week" required>
                                <option value="">Select Day</option>
                                <option value="monday" {{ old('day_of_week') == 'monday' ? 'selected' : '' }}>Monday</option>
                                <option value="tuesday" {{ old('day_of_week') == 'tuesday' ? 'selected' : '' }}>Tuesday</option>
                                <option value="wednesday" {{ old('day_of_week') == 'wednesday' ? 'selected' : '' }}>Wednesday</option>
                                <option value="thursday" {{ old('day_of_week') == 'thursday' ? 'selected' : '' }}>Thursday</option>
                                <option value="friday" {{ old('day_of_week') == 'friday' ? 'selected' : '' }}>Friday</option>
                                <option value="saturday" {{ old('day_of_week') == 'saturday' ? 'selected' : '' }}>Saturday</option>
                                <option value="sunday" {{ old('day_of_week') == 'sunday' ? 'selected' : '' }}>Sunday</option>
                            </select>
                            @error('day_of_week')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control @error('start_time') is-invalid @enderror" 
                                       id="start_time" name="start_time" 
                                       value="{{ old('start_time', '09:00') }}" required>
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control @error('end_time') is-invalid @enderror" 
                                       id="end_time" name="end_time" 
                                       value="{{ old('end_time', '17:00') }}" required>
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="break_start_time" class="form-label">Break Start Time</label>
                                <input type="time" class="form-control @error('break_start_time') is-invalid @enderror" 
                                       id="break_start_time" name="break_start_time" 
                                       value="{{ old('break_start_time') }}">
                                @error('break_start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="break_end_time" class="form-label">Break End Time</label>
                                <input type="time" class="form-control @error('break_end_time') is-invalid @enderror" 
                                       id="break_end_time" name="break_end_time" 
                                       value="{{ old('break_end_time') }}">
                                @error('break_end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="slot_duration" class="form-label">Slot Duration (minutes) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('slot_duration') is-invalid @enderror" 
                                       id="slot_duration" name="slot_duration" 
                                       value="{{ old('slot_duration', 30) }}" min="15" max="120" step="15" required>
                                @error('slot_duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Duration of each appointment slot (15-120 minutes)</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="max_appointments_per_slot" class="form-label">Max Appointments per Slot <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('max_appointments_per_slot') is-invalid @enderror" 
                                       id="max_appointments_per_slot" name="max_appointments_per_slot" 
                                       value="{{ old('max_appointments_per_slot', 1) }}" min="1" max="10" required>
                                @error('max_appointments_per_slot')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Maximum number of appointments per time slot</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="effective_from" class="form-label">Effective From</label>
                                <input type="date" class="form-control @error('effective_from') is-invalid @enderror" 
                                       id="effective_from" name="effective_from" 
                                       value="{{ old('effective_from') }}">
                                @error('effective_from')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Leave empty for immediate effect</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="effective_until" class="form-label">Effective Until</label>
                                <input type="date" class="form-control @error('effective_until') is-invalid @enderror" 
                                       id="effective_until" name="effective_until" 
                                       value="{{ old('effective_until') }}">
                                @error('effective_until')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Leave empty for ongoing schedule</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_available" name="is_available" 
                                       value="1" {{ old('is_available', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_available">
                                    Available for appointments
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('doctor-schedules.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-2"></i>Create Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Information</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        <strong>How it works:</strong>
                    </p>
                    <ul class="text-muted">
                        <li>Set your weekly schedule for each day you're available</li>
                        <li>Define your working hours and break times</li>
                        <li>Set slot duration (e.g., 30 minutes per appointment)</li>
                        <li>Specify how many appointments can be booked per slot</li>
                        <li>Set effective dates if this is a temporary schedule</li>
                    </ul>
                    <p class="text-muted mt-3">
                        <strong>Note:</strong> After creating your schedule, you'll need to generate appointment slots 
                        for specific dates using the Appointment Slots management page.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
