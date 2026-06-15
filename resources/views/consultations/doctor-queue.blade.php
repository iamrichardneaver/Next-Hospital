@extends('layouts.app')

@section('title', 'Doctor Consultation Queue')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-stethoscope"></i> Doctor Consultation Queue</h1>
            <p class="text-secondary mb-0">Consultation requests awaiting doctor review</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm queue-filter-select" id="branchFilter" onchange="window.location.href='?branch_id='+this.value">
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ $branch->id == $branchId ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            <a href="{{ route('consultations.completed') }}" class="btn btn-info" title="View Completed Consultations">
                <i class="bi bi-clipboard-check"></i> Completed
            </a>
            <button class="btn btn-success" onclick="callNextPatient()" title="Call Next Patient" id="callNextBtn">
                <i class="bi bi-megaphone"></i> Call Next Patient
            </button>
            <button class="btn btn-outline-secondary" onclick="refreshQueue()" title="Refresh Queue">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-label">Pending Consultations</div>
                <div class="stat-value" id="stat-pending">{{ $stats['pending_consultations'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                <div class="stat-label">In Progress</div>
                <div class="stat-value" id="stat-in-progress">{{ $stats['in_progress'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stat-label">Completed Today</div>
                <div class="stat-value" id="stat-completed">{{ $stats['completed_today'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-clock"></i></div>
                <div class="stat-label">Avg Wait Time</div>
                <div class="stat-value" id="stat-wait-time">{{ $stats['avg_wait_time'] }} min</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Currently Being Consulted -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm" style="border-left: 4px solid #28a745;">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-person-check-fill"></i> Currently Consulting</h5>
                </div>
                <div class="card-body" id="current-consultation">
                    @if($currentConsultation)
                        <div class="consultation-card current">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="mb-1">{{ $currentConsultation->patient->first_name }} {{ $currentConsultation->patient->last_name }}</h5>
                                    <span class="badge bg-primary">{{ $currentConsultation->patient->patient_number }}</span>
                                    <span class="badge bg-info">{{ $currentConsultation->visit->visit_token ?? 'N/A' }}</span>
                                </div>
                                <span class="badge bg-success">In Progress</span>
                            </div>
                            <p class="mb-1"><i class="bi bi-telephone"></i> {{ $currentConsultation->patient->phone }}</p>
                            <p class="mb-1"><i class="bi bi-gender-ambiguous"></i> {{ $currentConsultation->patient->gender }}</p>
                            <p class="mb-1"><i class="bi bi-calendar"></i> {{ $currentConsultation->patient->date_of_birth ? \Carbon\Carbon::parse($currentConsultation->patient->date_of_birth)->age . ' years' : 'Age not specified' }}</p>
                            @if($currentConsultation->chief_complaint)
                                <p class="mb-1"><i class="bi bi-chat-text"></i> <strong>Chief Complaint:</strong> {{ Str::limit($currentConsultation->chief_complaint, 50) }}</p>
                            @endif
                            @if($currentConsultation->doctor_remarks)
                                <div class="doctor-remarks mt-2 p-2 bg-light rounded">
                                    <small class="text-muted"><i class="bi bi-sticky"></i> <strong>Notes for Doctor:</strong></small>
                                    <div class="mt-1">{!! Str::limit(strip_tags($currentConsultation->doctor_remarks), 100) !!}</div>
                                </div>
                            @endif
                            @if($currentConsultation->vitals && $currentConsultation->vitals->isNotEmpty())
                                @php $latestVitals = $currentConsultation->vitals->last(); @endphp
                                <div class="vitals-summary mt-2">
                                    <small class="text-muted"><i class="bi bi-clipboard-pulse"></i> <strong>Latest Vitals:</strong></small>
                                    <div class="row mt-1">
                                        @if($latestVitals->blood_pressure_systolic && $latestVitals->blood_pressure_diastolic)
                                            <div class="col-6"><small>BP: {{ $latestVitals->blood_pressure_systolic }}/{{ $latestVitals->blood_pressure_diastolic }} mmHg</small></div>
                                        @endif
                                        @if($latestVitals->pulse_rate)
                                            <div class="col-6"><small>Pulse: {{ $latestVitals->pulse_rate }} bpm</small></div>
                                        @endif
                                        @if($latestVitals->temperature)
                                            <div class="col-6"><small>Temp: {{ $latestVitals->temperature }}°C</small></div>
                                        @endif
                                        @if($latestVitals->oxygen_saturation)
                                            <div class="col-6"><small>O2: {{ $latestVitals->oxygen_saturation }}%</small></div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="d-flex gap-2">
                                <a href="{{ route('consultations.show', $currentConsultation) }}" class="btn btn-info btn-sm">
                                    <i class="bi bi-eye"></i> View Details
                                </a>
                                <a href="{{ route('consultations.edit', $currentConsultation) }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-pencil"></i> Continue Consultation
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-stethoscope" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted mt-2">No consultation in progress</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Consultation Queue -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm" style="border-left: 4px solid #007bff;">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ol"></i> Consultation Queue ({{ count($consultationQueue) }})</h5>
                    <span class="badge bg-light text-dark">Updates every 30s</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 50px;">Pos</th>
                                    <th>Patient</th>
                                    <th>Chief Complaint</th>
                                    <th>Priority</th>
                                    <th>Wait Time</th>
                                    <th style="width: 200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="consultation-queue-tbody">
                                @forelse($consultationQueue as $index => $consultation)
                                <tr class="consultation-row priority-{{ $consultation->urgency ?? 'routine' }}" data-consultation-id="{{ $consultation->id }}">
                                    <td><span class="badge bg-secondary">{{ $index + 1 }}</span></td>
                                    <td>
                                        @if($consultation->patient)
                                            <strong>{{ $consultation->patient->first_name }} {{ $consultation->patient->last_name }}</strong><br>
                                            <small class="text-muted">{{ $consultation->patient->patient_number }}</small><br>
                                            <small class="text-muted">{{ $consultation->patient->phone }}</small>
                                        @else
                                            <strong class="text-danger">Patient Not Found</strong>
                                        @endif
                                        @if($consultation->vitals && $consultation->vitals->isNotEmpty())
                                            @php $latestVitals = $consultation->vitals->last(); @endphp
                                            <div class="mt-1">
                                                @if($latestVitals->blood_pressure_systolic && $latestVitals->blood_pressure_diastolic)
                                                    <span class="badge bg-light text-dark me-1">BP: {{ $latestVitals->blood_pressure_systolic }}/{{ $latestVitals->blood_pressure_diastolic }}</span>
                                                @endif
                                                @if($latestVitals->temperature)
                                                    <span class="badge bg-light text-dark me-1">T: {{ $latestVitals->temperature }}°C</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $consultation->chief_complaint }}">
                                            {{ Str::limit($consultation->chief_complaint, 50) }}
                                        </span>
                                        @if($consultation->doctor_remarks)
                                            <div class="mt-1">
                                                <small class="text-info">
                                                    <i class="bi bi-sticky"></i> {{ Str::limit(strip_tags($consultation->doctor_remarks), 30) }}
                                                </small>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ ($consultation->urgency ?? 'routine') === 'critical' ? 'danger' : (($consultation->urgency ?? 'routine') === 'urgent' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($consultation->urgency ?? 'routine') }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $consultation->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('consultations.show', $consultation) }}" class="btn btn-info" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('consultations.create-from-queue', $consultation) }}" class="btn btn-primary" title="Start Consultation">
                                                <i class="bi bi-play-fill"></i>
                                            </a>
                                            <button class="btn btn-warning" onclick="markNoShow({{ $consultation->id }})" title="No Show">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                                        <p class="text-muted mt-2">No consultations in queue</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Consultation Actions JavaScript -->
<script>
const branchId = {{ $branchId }};
if (typeof window.csrfToken === 'undefined') {
    window.csrfToken = '{{ csrf_token() }}';
}

// Debug CSRF token
console.log('CSRF Token from meta:', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
console.log('CSRF Token from window:', window.csrfToken);

// Call next patient
function callNextPatient() {
    if (!confirm('Call the next patient in consultation queue?')) return;

    // Disable button to prevent multiple calls
    const callBtn = document.getElementById('callNextBtn');
    callBtn.disabled = true;
    callBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Calling...';

    // Get fresh CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.csrfToken;
    
    if (!csrfToken) {
        showAlert('error', 'CSRF token not found. Please refresh the page.');
        return;
    }
    
    fetch('/consultations/call-next', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            branch_id: branchId
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Handle authentication errors
        if (response.status === 401 || response.status === 419) {
            showAlert('error', 'Session expired. Please log in again.');
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
            return;
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                if (text.includes('Page Expired') || text.includes('419')) {
                    showAlert('error', 'Session expired. Please log in again.');
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 2000);
                    return;
                }
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showAlert('success', data.message);
            
            // Audio announcement
            if (data.data && data.data.patient) {
                const patientName = `${data.data.patient.first_name} ${data.data.patient.last_name}`;
                const visitToken = data.data.visit_token || 'N/A';
                
                // Check if audio service is available
                if (typeof window.queueAudio !== 'undefined' && window.queueAudio.enabled) {
                    try {
                        window.callPatient(visitToken, patientName, 'Consultation', 'Doctor Room');
                    } catch (error) {
                        console.error('Audio announcement error:', error);
                        showAlert('warning', 'Patient called but audio announcement failed');
                    }
                } else {
                    console.log('Audio service not available or disabled');
                }
            }
            
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error calling next patient');
    })
    .finally(() => {
        // Re-enable button
        callBtn.disabled = false;
        callBtn.innerHTML = '<i class="bi bi-megaphone"></i> Call Next Patient';
    });
}

// Refresh queue
function refreshQueue() {
    location.reload();
}

// Mark no-show
function markNoShow(consultationId) {
    if (!confirm('Mark this consultation as no-show?')) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.csrfToken;
    
    fetch(`/consultations/${consultationId}/no-show`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        // Handle authentication errors
        if (response.status === 401 || response.status === 419) {
            showAlert('error', 'Session expired. Please log in again.');
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
            return;
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                if (text.includes('Page Expired') || text.includes('419')) {
                    showAlert('error', 'Session expired. Please log in again.');
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 2000);
                    return;
                }
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
            });
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error marking no-show: ' + error.message);
    });
}

// Show alert
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 3000);
}

// Auto-refresh every 30 seconds
setInterval(() => {
    location.reload();
}, 30000);
</script>

<style>
.consultation-card {
    padding: 15px;
    border-radius: 8px;
    background: #f8f9fa;
}

.consultation-card.current {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border: 2px solid #28a745;
}

.consultation-row.priority-critical {
    background-color: #ffe6e6 !important;
}

.consultation-row.priority-urgent {
    background-color: #fff3cd !important;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}
</style>
@endsection
