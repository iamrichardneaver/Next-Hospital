@extends('layouts.app')

@section('title', 'Edit Schedule')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">Edit Schedule</h2>
                    <p class="text-muted mb-0">Update your weekly availability</p>
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
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Schedule Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('doctor-schedules.update', $schedule->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Doctor</label>
                            <input type="text" class="form-control" value="{{ $schedule->doctor->name }}" disabled>
                            <small class="form-text text-muted">Doctor cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Branch</label>
                            <input type="text" class="form-control" value="{{ $schedule->branch->name }}" disabled>
                            <small class="form-text text-muted">Branch cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Day of Week</label>
                            <input type="text" class="form-control" value="{{ ucfirst($schedule->day_of_week) }}" disabled>
                            <small class="form-text text-muted">Day cannot be changed. Delete and create a new schedule for a different day.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control @error('start_time') is-invalid @enderror" 
                                       id="start_time" name="start_time" 
                                       value="{{ old('start_time', \Carbon\Carbon::parse($schedule->start_time)->format('H:i')) }}" required>
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control @error('end_time') is-invalid @enderror" 
                                       id="end_time" name="end_time" 
                                       value="{{ old('end_time', \Carbon\Carbon::parse($schedule->end_time)->format('H:i')) }}" required>
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
                                       value="{{ old('break_start_time', $schedule->break_start_time ? \Carbon\Carbon::parse($schedule->break_start_time)->format('H:i') : '') }}">
                                @error('break_start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="break_end_time" class="form-label">Break End Time</label>
                                <input type="time" class="form-control @error('break_end_time') is-invalid @enderror" 
                                       id="break_end_time" name="break_end_time" 
                                       value="{{ old('break_end_time', $schedule->break_end_time ? \Carbon\Carbon::parse($schedule->break_end_time)->format('H:i') : '') }}">
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
                                       value="{{ old('slot_duration', $schedule->slot_duration) }}" min="15" max="120" step="15" required>
                                @error('slot_duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="max_appointments_per_slot" class="form-label">Max Appointments per Slot <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('max_appointments_per_slot') is-invalid @enderror" 
                                       id="max_appointments_per_slot" name="max_appointments_per_slot" 
                                       value="{{ old('max_appointments_per_slot', $schedule->max_appointments_per_slot) }}" min="1" max="10" required>
                                @error('max_appointments_per_slot')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="effective_from" class="form-label">Effective From</label>
                                <input type="date" class="form-control @error('effective_from') is-invalid @enderror" 
                                       id="effective_from" name="effective_from" 
                                       value="{{ old('effective_from', $schedule->effective_from ? $schedule->effective_from->format('Y-m-d') : '') }}">
                                @error('effective_from')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="effective_until" class="form-label">Effective Until</label>
                                <input type="date" class="form-control @error('effective_until') is-invalid @enderror" 
                                       id="effective_until" name="effective_until" 
                                       value="{{ old('effective_until', $schedule->effective_until ? $schedule->effective_until->format('Y-m-d') : '') }}">
                                @error('effective_until')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_available" name="is_available" 
                                       value="1" {{ old('is_available', $schedule->is_available) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_available">
                                    Available for appointments
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3">{{ old('notes', $schedule->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('doctor-schedules.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-2"></i>Update Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
