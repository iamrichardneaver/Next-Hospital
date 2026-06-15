@extends('layouts.app')

@section('title', 'Create Appointment Slots')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Create Appointment Slots</h2>
                <a href="{{ route('appointments.slots.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Bulk Generate Appointment Slots</h5>
                    <small class="text-muted">Generate slots based on doctor's schedule for a date range</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('appointments.slots.store') }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="doctor_id" class="form-label">Doctor <span class="text-danger">*</span></label>
                                <select name="doctor_id" id="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror" required>
                                    <option value="">Select Doctor</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>
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
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
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
                                <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', now()->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', now()->addDays(30)->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}" required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Slots will be created for all working days in this range</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="appointment_type" class="form-label">Appointment Type <span class="text-danger">*</span></label>
                                <select name="appointment_type" id="appointment_type" class="form-select @error('appointment_type') is-invalid @enderror" required>
                                    <option value="both" {{ old('appointment_type') == 'both' ? 'selected' : '' }}>Both (In-person & Teleconsultation)</option>
                                    <option value="in-person" {{ old('appointment_type') == 'in-person' ? 'selected' : '' }}>In-person Only</option>
                                    <option value="teleconsultation" {{ old('appointment_type') == 'teleconsultation' ? 'selected' : '' }}>Teleconsultation Only</option>
                                </select>
                                @error('appointment_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="slot_duration" class="form-label">Slot Duration (minutes) <span class="text-danger">*</span></label>
                                <select name="slot_duration" id="slot_duration" class="form-select @error('slot_duration') is-invalid @enderror" required>
                                    <option value="15" {{ old('slot_duration') == '15' ? 'selected' : '' }}>15 minutes</option>
                                    <option value="30" {{ old('slot_duration', '30') == '30' ? 'selected' : '' }}>30 minutes</option>
                                    <option value="45" {{ old('slot_duration') == '45' ? 'selected' : '' }}>45 minutes</option>
                                    <option value="60" {{ old('slot_duration') == '60' ? 'selected' : '' }}>60 minutes</option>
                                </select>
                                @error('slot_duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="max_appointments" class="form-label">Max Appointments per Slot <span class="text-danger">*</span></label>
                                <input type="number" name="max_appointments" id="max_appointments" class="form-control @error('max_appointments') is-invalid @enderror" value="{{ old('max_appointments', '1') }}" min="1" max="10" required>
                                @error('max_appointments')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Number of appointments that can be booked in each slot</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="fee_in_person" class="form-label">Fee – In-person (GHS)</label>
                                <input type="number" name="fee_in_person" id="fee_in_person" class="form-control @error('fee_in_person') is-invalid @enderror" value="{{ old('fee_in_person') }}" min="0" step="0.01" placeholder="e.g. 50">
                                @error('fee_in_person')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Shown when patient selects in-person consultation</small>
                            </div>
                            <div class="col-md-6">
                                <label for="fee_teleconsultation" class="form-label">Fee – Teleconsultation (GHS)</label>
                                <input type="number" name="fee_teleconsultation" id="fee_teleconsultation" class="form-control @error('fee_teleconsultation') is-invalid @enderror" value="{{ old('fee_teleconsultation') }}" min="0" step="0.01" placeholder="e.g. 35">
                                @error('fee_teleconsultation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Usually lower for mobile/teleconsultation</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('appointments.slots.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Generate Slots
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Help & Instructions</h5>
                </div>
                <div class="card-body">
                    <h6><i class="fas fa-info-circle text-primary"></i> How it works</h6>
                    <p class="small">This form generates appointment slots based on the doctor's existing schedule. Make sure the doctor has a schedule configured first.</p>

                    <h6 class="mt-3"><i class="fas fa-calendar-alt text-success"></i> Date Range</h6>
                    <p class="small">Select the date range for which you want to generate slots. The system will create slots for all working days within this range.</p>

                    <h6 class="mt-3"><i class="fas fa-clock text-info"></i> Slot Configuration</h6>
                    <ul class="small">
                        <li>Slots are created based on doctor's schedule (working hours and break times)</li>
                        <li>Existing slots for a date will be skipped</li>
                        <li>You can create both in-person and teleconsultation slots</li>
                    </ul>

                    <h6 class="mt-3"><i class="fas fa-money-bill-wave text-warning"></i> Fees</h6>
                    <p class="small">Set <strong>Fee – In-person</strong> and <strong>Fee – Teleconsultation</strong> (e.g. lower for teleconsultation). These are shown on the mobile app when booking. Leave blank to use default fee structure.</p>

                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> <strong>Note:</strong> If the doctor doesn't have a schedule configured, the system will use default working hours (8 AM - 6 PM, Monday-Friday) to generate slots.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure end date is after start date
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');

    startDate.addEventListener('change', function() {
        endDate.min = this.value;
        if (endDate.value < this.value) {
            endDate.value = this.value;
        }
    });
});
</script>
@endsection

