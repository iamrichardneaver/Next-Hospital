@extends('layouts.app')

@section('title', 'Schedule Appointment')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Schedule New Appointment</h1>
            <p class="text-secondary mb-0">Book an appointment for a patient</p>
        </div>
        <div>
            <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-10 col-xl-8 mx-auto">
            <form action="{{ route('appointments.store') }}" method="POST">
                @csrf
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark">Appointment Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                                <select class="form-select @error('patient_id') is-invalid @enderror" 
                                        id="patient_id" 
                                        name="patient_id" 
                                        required>
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                                        {{ $patient->patient_number }} - {{ $patient->first_name }} {{ $patient->last_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('patient_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="doctor_id" class="form-label">Doctor <span class="text-danger">*</span></label>
                                @if(auth()->user()->hasRole('doctor'))
                                    {{-- Doctors can only create for themselves --}}
                                    <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                                    <input type="hidden" name="doctor_id" value="{{ auth()->id() }}">
                                    <small class="form-text text-muted">You can only create appointments for yourself</small>
                                @else
                                    <select class="form-select @error('doctor_id') is-invalid @enderror" 
                                            id="doctor_id" 
                                            name="doctor_id" 
                                            required>
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
                                @endif
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="appointment_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control @error('appointment_date') is-invalid @enderror" 
                                       id="appointment_date" 
                                       name="appointment_date" 
                                       value="{{ old('appointment_date') }}" 
                                       min="{{ date('Y-m-d') }}"
                                       required>
                                @error('appointment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="appointment_time" class="form-label">Time <span class="text-danger">*</span></label>
                                <select class="form-select @error('appointment_time') is-invalid @enderror" 
                                        id="appointment_time" 
                                        name="appointment_time" 
                                        required 
                                        disabled>
                                    <option value="">Select doctor & date first</option>
                                </select>
                                <div id="time-loading" class="small text-info mt-1" style="display: none;">
                                    <i class="bi bi-clock-history"></i> Loading available slots...
                                </div>
                                @error('appointment_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="appointment_type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('appointment_type') is-invalid @enderror" 
                                        id="appointment_type" 
                                        name="appointment_type" 
                                        required>
                                    <option value="">Select Type</option>
                                    <option value="in-person" {{ old('appointment_type') === 'in-person' ? 'selected' : '' }}>In-Person</option>
                                    <option value="teleconsultation" {{ old('appointment_type') === 'teleconsultation' ? 'selected' : '' }}>Teleconsultation</option>
                                </select>
                                @error('appointment_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                <select class="form-select @error('branch_id') is-invalid @enderror" 
                                        id="branch_id" 
                                        name="branch_id" 
                                        required>
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
                            
                            <div class="col-md-6 mb-3">
                                <label for="reason" class="form-label">Reason for Visit</label>
                                <textarea class="form-control @error('reason') is-invalid @enderror" 
                                          id="reason" 
                                          name="reason" 
                                          rows="2" 
                                          placeholder="Describe reason for appointment">{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Schedule Appointment
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const doctorSelect = document.getElementById('doctor_id');
    const dateInput = document.getElementById('appointment_date');
    const timeSelect = document.getElementById('appointment_time');
    const typeSelect = document.getElementById('appointment_type');
    const branchSelect = document.getElementById('branch_id');
    const timeLoading = document.getElementById('time-loading');
    
    let availableDates = [];
    let availableSlots = [];
    
    // Listen for doctor selection
    doctorSelect.addEventListener('change', function() {
        const doctorId = this.value;
        
        if (!doctorId) {
            resetDateAndTime();
            return;
        }
        
        // Reset selections
        dateInput.value = '';
        timeSelect.innerHTML = '<option value="">Select doctor & date first</option>';
        timeSelect.disabled = true;
        
        // Fetch available dates for this doctor
        loadAvailableDates(doctorId);
    });
    
    // Listen for date selection
    dateInput.addEventListener('change', function() {
        const doctorId = doctorSelect.value;
        const date = this.value;
        const branchId = branchSelect.value;
        const appointmentType = typeSelect.value;
        
        if (!doctorId || !date || !branchId) {
            timeSelect.innerHTML = '<option value="">Select all required fields first</option>';
            timeSelect.disabled = true;
            return;
        }
        
        // Load available time slots
        loadAvailableTimeSlots(doctorId, branchId, date, appointmentType);
    });
    
    // Listen for appointment type change
    typeSelect.addEventListener('change', function() {
        // Reload time slots if doctor and date are already selected
        if (doctorSelect.value && dateInput.value && branchSelect.value) {
            loadAvailableTimeSlots(doctorSelect.value, branchSelect.value, dateInput.value, this.value);
        }
    });
    
    // Listen for branch change
    branchSelect.addEventListener('change', function() {
        // Reset and reload if doctor is selected
        if (doctorSelect.value) {
            loadAvailableDates(doctorSelect.value);
        }
    });
    
    function loadAvailableDates(doctorId) {
        const today = new Date();
        const endDate = new Date(today);
        endDate.setDate(endDate.getDate() + 60); // Look ahead 60 days
        
        const startDateStr = formatDate(today);
        const endDateStr = formatDate(endDate);
        
        // Use dynamic API endpoint (relative URL works in all environments)
        const datesUrl = window.appConfig ? window.appConfig.api(`appointments/available-dates?doctor_id=${doctorId}&start_date=${startDateStr}&end_date=${endDateStr}`) : `/api/appointments/available-dates?doctor_id=${doctorId}&start_date=${startDateStr}&end_date=${endDateStr}`;
        fetch(datesUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    availableDates = data.data;
                    
                    if (availableDates.length === 0) {
                        alert('No available appointment slots found for this doctor. Please select another doctor or contact admin to set up appointment slots.');
                        resetDateAndTime();
                    }
                } else {
                    console.error('Failed to load available dates:', data.message);
                    alert('Failed to load available dates. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error loading available dates:', error);
                alert('An error occurred while loading available dates. Please try again.');
            });
    }
    
    function loadAvailableTimeSlots(doctorId, branchId, date, appointmentType = 'in-person') {
        timeLoading.style.display = 'block';
        timeSelect.disabled = true;
        timeSelect.innerHTML = '<option value="">Loading...</option>';
        
        // Use dynamic API endpoint (relative URL works in all environments)
        const slotsUrl = window.appConfig ? window.appConfig.api(`appointments/available-time-slots?doctor_id=${doctorId}&branch_id=${branchId}&date=${date}&appointment_type=${appointmentType}`) : `/api/appointments/available-time-slots?doctor_id=${doctorId}&branch_id=${branchId}&date=${date}&appointment_type=${appointmentType}`;
        
        fetch(slotsUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                timeLoading.style.display = 'none';
                
                if (data.success && data.data && data.data.length > 0) {
                    availableSlots = data.data;
                    
                    // Populate time select
                    timeSelect.innerHTML = '<option value="">Select time slot</option>';
                    availableSlots.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot.time;
                        option.textContent = `${slot.time} - ${slot.end_time} (${slot.remaining_capacity} slots available)`;
                        
                        if (slot.fee) {
                            option.textContent += ` - ${slot.currency || 'GHS'} ${slot.fee}`;
                        }
                        
                        timeSelect.appendChild(option);
                    });
                    
                    timeSelect.disabled = false;
                } else {
                    timeSelect.innerHTML = '<option value="">No available slots for this date</option>';
                    alert('No available time slots for the selected date. Please choose a different date.');
                }
            })
            .catch(error => {
                timeLoading.style.display = 'none';
                console.error('Error loading time slots:', error);
                timeSelect.innerHTML = '<option value="">Error loading slots</option>';
                alert('Failed to load available time slots. Please try again.');
            });
    }
    
    function resetDateAndTime() {
        dateInput.value = '';
        timeSelect.innerHTML = '<option value="">Select doctor & date first</option>';
        timeSelect.disabled = true;
        availableDates = [];
        availableSlots = [];
    }
    
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padLeft(2, '0');
        const day = String(date.getDate()).padLeft(2, '0');
        return `${year}-${month}-${day}`;
    }
});

// Helper: Pad string with leading zeros
String.prototype.padLeft = function(length, char) {
    return char.repeat(Math.max(0, length - this.length)) + this;
};
</script>
@endpush
