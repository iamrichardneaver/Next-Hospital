@extends('layouts.app')

@section('title', 'Schedule Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">Schedule Details</h2>
                    <p class="text-muted mb-0">View schedule information</p>
                </div>
                <div>
                    <a href="{{ route('doctor-schedules.edit', $schedule->id) }}" class="btn btn-warning">
                        <i class="bi bi-pencil me-2"></i>Edit
                    </a>
                    <a href="{{ route('doctor-schedules.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Schedule Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Doctor</label>
                            <p class="mb-0">{{ $schedule->doctor->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Branch</label>
                            <p class="mb-0">{{ $schedule->branch->name }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Day of Week</label>
                            <p class="mb-0">
                                <span class="badge bg-primary">{{ ucfirst($schedule->day_of_week) }}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <p class="mb-0">
                                @if($schedule->is_available)
                                    <span class="badge bg-success">Available</span>
                                @else
                                    <span class="badge bg-danger">Unavailable</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Working Hours</label>
                            <p class="mb-0">
                                <strong>{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}</strong> - 
                                <strong>{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</strong>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Break Time</label>
                            <p class="mb-0">
                                @if($schedule->break_start_time && $schedule->break_end_time)
                                    {{ \Carbon\Carbon::parse($schedule->break_start_time)->format('H:i') }} - 
                                    {{ \Carbon\Carbon::parse($schedule->break_end_time)->format('H:i') }}
                                @else
                                    <span class="text-muted">No break scheduled</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Slot Duration</label>
                            <p class="mb-0">{{ $schedule->slot_duration }} minutes</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Max Appointments per Slot</label>
                            <p class="mb-0">{{ $schedule->max_appointments_per_slot }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Effective From</label>
                            <p class="mb-0">
                                {{ $schedule->effective_from ? $schedule->effective_from->format('M d, Y') : 'Immediate' }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Effective Until</label>
                            <p class="mb-0">
                                {{ $schedule->effective_until ? $schedule->effective_until->format('M d, Y') : 'Ongoing' }}
                            </p>
                        </div>
                    </div>

                    @if($schedule->notes)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <p class="mb-0">{{ $schedule->notes }}</p>
                    </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Created By</label>
                            <p class="mb-0">{{ $schedule->creator->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Last Updated</label>
                            <p class="mb-0">{{ $schedule->updated_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('doctor-schedules.edit', $schedule->id) }}" class="btn btn-warning">
                            <i class="bi bi-pencil me-2"></i>Edit Schedule
                        </a>
                        <a href="{{ route('appointments.slots.create', ['doctor_id' => $schedule->doctor_id, 'branch_id' => $schedule->branch_id]) }}" class="btn btn-primary">
                            <i class="bi bi-calendar-plus me-2"></i>Generate Appointment Slots
                        </a>
                        <form action="{{ route('doctor-schedules.destroy', $schedule->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-trash me-2"></i>Delete Schedule
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
