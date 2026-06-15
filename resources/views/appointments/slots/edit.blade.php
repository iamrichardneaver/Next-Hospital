@extends('layouts.app')

@section('title', 'Edit Appointment Slot')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Edit Appointment Slot</h2>
                <a href="{{ route('appointments.slots.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Slot Details</h5>
                    <small class="text-muted">Edit appointment slot information</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('appointments.slots.update', $slot->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="doctor_id" class="form-label">Doctor <span class="text-danger">*</span></label>
                                <select name="doctor_id" id="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror" required>
                                    <option value="">Select Doctor</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" {{ old('doctor_id', $slot->doctor_id) == $doctor->id ? 'selected' : '' }}>
                                            {{ $doctor->name }}
                                            @if($doctor->staffProfile)
                                                - {{ $doctor->staffProfile->specialization ?? $doctor->staffProfile->department }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('doctor_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $slot->branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="slot_date" class="form-label">Slot Date <span class="text-danger">*</span></label>
                                <input type="date" name="slot_date" id="slot_date" class="form-control @error('slot_date') is-invalid @enderror" value="{{ old('slot_date', $slot->slot_date ? (\Carbon\Carbon::parse($slot->slot_date)->format('Y-m-d')) : '') }}" required>
                                @error('slot_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="appointment_type" class="form-label">Appointment Type <span class="text-danger">*</span></label>
                                <select name="appointment_type" id="appointment_type" class="form-select @error('appointment_type') is-invalid @enderror" required>
                                    <option value="in-person" {{ old('appointment_type', $slot->appointment_type) == 'in-person' ? 'selected' : '' }}>In-person</option>
                                    <option value="teleconsultation" {{ old('appointment_type', $slot->appointment_type) == 'teleconsultation' ? 'selected' : '' }}>Teleconsultation</option>
                                </select>
                                @error('appointment_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" id="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', $slot->start_time ? \Carbon\Carbon::parse($slot->start_time)->format('H:i') : '') }}" required>
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" id="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time', $slot->end_time ? \Carbon\Carbon::parse($slot->end_time)->format('H:i') : '') }}" required>
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="duration" class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                                <input type="number" name="duration" id="duration" class="form-control @error('duration') is-invalid @enderror" value="{{ old('duration', $slot->duration) }}" min="15" max="120" required>
                                @error('duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="max_appointments" class="form-label">Max Appointments <span class="text-danger">*</span></label>
                                <input type="number" name="max_appointments" id="max_appointments" class="form-control @error('max_appointments') is-invalid @enderror" value="{{ old('max_appointments', $slot->max_appointments) }}" min="1" max="10" required>
                                @error('max_appointments')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Number of appointments that can be booked in this slot</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="fee" class="form-label">Consultation Fee (GHS) – {{ $slot->appointment_type === 'teleconsultation' ? 'Teleconsultation' : 'In-person' }}</label>
                                <input type="number" name="fee" id="fee" class="form-control @error('fee') is-invalid @enderror" value="{{ old('fee', $slot->fee) }}" min="0" step="0.01">
                                @error('fee')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Fee for this slot type</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="available" {{ old('status', $slot->status) == 'available' ? 'selected' : '' }}>Available</option>
                                    <option value="blocked" {{ old('status', $slot->status) == 'blocked' ? 'selected' : '' }}>Blocked</option>
                                    <option value="maintenance" {{ old('status', $slot->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $slot->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($slot->booked_appointments > 0)
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> <strong>Warning:</strong> This slot has {{ $slot->booked_appointments }} booked appointment(s). Changes may affect existing bookings.
                        </div>
                        @endif

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('appointments.slots.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Slot
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Slot Information</h5>
                </div>
                <div class="card-body">
                    <h6><i class="bi bi-info-circle text-primary"></i> Current Status</h6>
                    <ul class="list-unstyled small">
                        <li><strong>Status:</strong> 
                            @if($slot->status == 'available')
                                <span class="badge bg-success">Available</span>
                            @elseif($slot->status == 'blocked')
                                <span class="badge bg-warning">Blocked</span>
                            @elseif($slot->status == 'booked')
                                <span class="badge bg-info">Booked</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($slot->status) }}</span>
                            @endif
                        </li>
                        <li><strong>Booked:</strong> {{ $slot->booked_appointments }} / {{ $slot->max_appointments }}</li>
                        <li><strong>Remaining:</strong> {{ $slot->max_appointments - $slot->booked_appointments }}</li>
                        <li><strong>Duration:</strong> {{ $slot->duration }} minutes</li>
                    </ul>

                    <h6 class="mt-3"><i class="bi bi-lightbulb text-warning"></i> Tips</h6>
                    <ul class="small">
                        <li>Cannot delete slots with bookings - cancel bookings first</li>
                        <li>Changing time affects future bookings only</li>
                        <li>Block slots during doctor's leave or holidays</li>
                    </ul>

                    <div class="alert alert-info mt-3">
                        <strong>Created:</strong> {{ $slot->created_at->format('M d, Y H:i A') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure end time is after start time
    const startTime = document.getElementById('start_time');
    const endTime = document.getElementById('end_time');

    startTime.addEventListener('change', function() {
        if (endTime.value && endTime.value <= this.value) {
            // Auto-calculate end time (30 minutes after start)
            const start = new Date('2000-01-01 ' + this.value);
            start.setMinutes(start.getMinutes() + 30);
            endTime.value = start.toTimeString().slice(0, 5);
        }
    });
});
</script>
@endsection

