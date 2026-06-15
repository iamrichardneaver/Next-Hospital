@extends('layouts.app')

@section('title', 'Edit Emergency Alert')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-danger"><i class="bi bi-pencil"></i> Edit Emergency Alert</h1>
            <p class="text-secondary mb-0">Update emergency alert #{{ $emergencyAlert->id }}</p>
        </div>
        <div>
            <a href="{{ route('emergency-alerts.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Alerts
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Alert Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('emergency-alerts.update', $emergencyAlert) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Patient <span class="text-danger">*</span></label>
                                <select class="form-select @error('patient_id') is-invalid @enderror" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}" {{ old('patient_id', $emergencyAlert->patient_id) == $patient->id ? 'selected' : '' }}>
                                            {{ $patient->first_name }} {{ $patient->last_name }} ({{ $patient->patient_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('patient_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alert Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('alert_type') is-invalid @enderror" name="alert_type" required>
                                    <option value="">Select Alert Type</option>
                                    <option value="critical_triage" {{ old('alert_type', $emergencyAlert->alert_type) == 'critical_triage' ? 'selected' : '' }}>Critical Triage</option>
                                    <option value="patient_arrival" {{ old('alert_type', $emergencyAlert->alert_type) == 'patient_arrival' ? 'selected' : '' }}>Patient Arrival</option>
                                    <option value="intervention_required" {{ old('alert_type', $emergencyAlert->alert_type) == 'intervention_required' ? 'selected' : '' }}>Intervention Required</option>
                                    <option value="equipment_needed" {{ old('alert_type', $emergencyAlert->alert_type) == 'equipment_needed' ? 'selected' : '' }}>Equipment Needed</option>
                                    <option value="staff_required" {{ old('alert_type', $emergencyAlert->alert_type) == 'staff_required' ? 'selected' : '' }}>Staff Required</option>
                                </select>
                                @error('alert_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Priority Level <span class="text-danger">*</span></label>
                                <select class="form-select @error('priority') is-invalid @enderror" name="priority" required>
                                    <option value="">Select Priority</option>
                                    <option value="critical" {{ old('priority', $emergencyAlert->priority) == 'critical' ? 'selected' : '' }}>Critical - Immediate Action Required</option>
                                    <option value="high" {{ old('priority', $emergencyAlert->priority) == 'high' ? 'selected' : '' }}>High - Urgent Attention</option>
                                    <option value="medium" {{ old('priority', $emergencyAlert->priority) == 'medium' ? 'selected' : '' }}>Medium - Standard Priority</option>
                                    <option value="low" {{ old('priority', $emergencyAlert->priority) == 'low' ? 'selected' : '' }}>Low - Routine Attention</option>
                                </select>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                                    <option value="active" {{ old('status', $emergencyAlert->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="acknowledged" {{ old('status', $emergencyAlert->status) == 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                                    <option value="resolved" {{ old('status', $emergencyAlert->status) == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                   name="location" value="{{ old('location', $emergencyAlert->location) }}" 
                                   placeholder="e.g., Emergency Room, Ward 1, ICU">
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alert Message <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('message') is-invalid @enderror" 
                                      name="message" rows="4" required 
                                      placeholder="Describe the emergency situation...">{{ old('message', $emergencyAlert->message) }}</textarea>
                            <div class="form-text">Provide clear details about the emergency situation</div>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-check-circle"></i> Update Emergency Alert
                            </button>
                            <a href="{{ route('emergency-alerts.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Alert Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td><strong>Alert ID:</strong></td>
                            <td>#{{ $emergencyAlert->id }}</td>
                        </tr>
                        <tr>
                            <td><strong>Created:</strong></td>
                            <td>{{ $emergencyAlert->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Created By:</strong></td>
                            <td>{{ $emergencyAlert->creator->name ?? 'Unknown' }}</td>
                        </tr>
                        @if($emergencyAlert->acknowledged_at)
                        <tr>
                            <td><strong>Acknowledged:</strong></td>
                            <td>{{ $emergencyAlert->acknowledged_at->format('M d, Y H:i') }}</td>
                        </tr>
                        @endif
                        @if($emergencyAlert->resolved_at)
                        <tr>
                            <td><strong>Resolved:</strong></td>
                            <td>{{ $emergencyAlert->resolved_at->format('M d, Y H:i') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Alert Guidelines</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="bi bi-lightbulb"></i> Priority Guidelines:</h6>
                        <ul class="mb-0">
                            <li><strong>Critical:</strong> Life-threatening situations</li>
                            <li><strong>High:</strong> Urgent medical attention needed</li>
                            <li><strong>Medium:</strong> Standard medical priority</li>
                            <li><strong>Low:</strong> Routine medical attention</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
