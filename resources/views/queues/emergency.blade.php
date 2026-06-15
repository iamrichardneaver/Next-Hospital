@extends('layouts.app')

@section('title', 'Emergency Queue')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-danger"><i class="bi bi-heart-pulse-fill"></i> Emergency Queue Management</h1>
            <p class="text-secondary mb-0">Critical & Urgent Patient Queue</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm queue-filter-select" id="branchFilter" onchange="window.location.href='?branch_id='+this.value">
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ $branch->id == $branchId ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            <button class="btn btn-secondary" onclick="toggleAudio()" title="Toggle Audio Announcements">
                <i class="bi bi-volume-up"></i>
            </button>
            <button class="btn btn-outline-secondary" onclick="testAudio()" title="Test Audio">
                <i class="bi bi-soundwave"></i>
            </button>
            @canany(['manage_queues', 'manage_emergency_queue'])
            <button class="btn btn-danger" onclick="callNextPatient()">
                <i class="bi bi-bell"></i> Call Next Patient
            </button>
            @endcan
        </div>
    </div>

    <!-- Emergency Alert Banner -->
    @if($stats['total_waiting'] > 0)
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1.5rem;"></i>
        <div>
            <strong>{{ $stats['total_waiting'] }} patient(s)</strong> waiting in emergency queue. Priority-based serving is active.
        </div>
    </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-label">Waiting</div>
                <div class="stat-value">{{ $stats['total_waiting'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                <div class="stat-label">Being Served</div>
                <div class="stat-value">{{ $stats['total_serving'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stat-label">Completed Today</div>
                <div class="stat-value">{{ $stats['total_completed_today'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-clock"></i></div>
                <div class="stat-label">Avg Wait Time</div>
                <div class="stat-value">{{ $stats['avg_wait_time'] }} min</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Currently Serving -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm" style="border-left: 4px solid #dc3545;">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-person-check-fill"></i> Currently Serving</h5>
                </div>
                <div class="card-body">
                    @if($servingQueue)
                        <div class="queue-patient-card serving-emergency">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                @if($servingQueue->patient)
                                    <h5 class="mb-1">{{ $servingQueue->patient->first_name }} {{ $servingQueue->patient->last_name }}</h5>
                                @else
                                    <h5 class="mb-1 text-danger">Patient Not Found</h5>
                                @endif
                                <span class="badge bg-{{ $servingQueue->priority === 'critical' ? 'danger' : 'warning' }} badge-pulse">
                                    {{ strtoupper($servingQueue->priority) }}
                                </span>
                            </div>
                            @if($servingQueue->patient)
                                <p class="mb-1"><span class="badge bg-primary">{{ $servingQueue->patient->patient_number }}</span></p>
                            @else
                                <p class="mb-1"><span class="badge bg-danger">ID: {{ $servingQueue->patient_id }}</span></p>
                            @endif
                            <p class="mb-1"><i class="bi bi-telephone-fill"></i> {{ $servingQueue->patient->phone ?? 'N/A' }}</p>
                            <p class="mb-1"><i class="bi bi-gender-ambiguous"></i> {{ $servingQueue->patient->gender ?? 'N/A' }}</p>
                            @if($servingQueue->servedBy)
                            <p class="mb-2"><i class="bi bi-person-badge"></i> {{ $servingQueue->servedBy->first_name }} {{ $servingQueue->servedBy->last_name }}</p>
                            @endif
                            @canany(['manage_queues', 'manage_emergency_queue'])
                            <button class="btn btn-success btn-sm w-100" onclick="completeServing({{ $servingQueue->id }})">
                                <i class="bi bi-check-circle"></i> Complete Service
                            </button>
                            @endcan
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted mt-2">No patient currently being served</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Waiting Queue (Priority Sorted) -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm" style="border-left: 4px solid #dc3545;">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ol"></i> Waiting Queue - Priority Sorted ({{ count($waitingQueues) }})</h5>
                    <span class="badge bg-light text-dark">Critical patients first</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 50px;">Pos</th>
                                    <th>Patient</th>
                                    <th>Contact</th>
                                    <th>Age</th>
                                    <th>Priority</th>
                                    <th>Wait Time</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($waitingQueues as $queue)
                                <tr class="emergency-row priority-{{ $queue->priority }}">
                                    <td><span class="badge {{ $queue->priority === 'critical' ? 'bg-danger' : 'bg-warning' }}">{{ $queue->position }}</span></td>
                                    <td>
                                        @if($queue->patient)
                                            <strong>{{ $queue->patient->first_name }} {{ $queue->patient->last_name }}</strong><br>
                                            <small class="text-muted">{{ $queue->patient->patient_number }}</small>
                                        @else
                                            <strong class="text-danger">Patient Not Found</strong><br>
                                            <small class="text-muted">ID: {{ $queue->patient_id }}</small>
                                        @endif
                                    </td>
                                    <td><small>{{ $queue->patient->phone ?? 'N/A' }}</small></td>
                                    <td>
                                        @if($queue->patient && $queue->patient->date_of_birth)
                                            {{ \Carbon\Carbon::parse($queue->patient->date_of_birth)->age }} yrs
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $queue->priority === 'critical' ? 'danger' : ($queue->priority === 'urgent' ? 'warning' : 'secondary') }} badge-pulse">
                                            <i class="bi bi-{{ $queue->priority === 'critical' ? 'exclamation-triangle-fill' : 'exclamation-circle' }}"></i>
                                            {{ strtoupper($queue->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-danger"><strong>{{ $queue->queued_at->diffForHumans() }}</strong></small>
                                    </td>
                                    <td>
                                        @canany(['manage_queues', 'manage_emergency_queue'])
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-danger" onclick="startServing({{ $queue->id }})" title="Start Immediate Service">
                                                <i class="bi bi-play-fill"></i>
                                            </button>
                                            <button class="btn btn-warning" onclick="markNoShow({{ $queue->id }})" title="No Show">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </div>
                                        @endcan
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="bi bi-check-circle" style="font-size: 2rem; color: #28a745;"></i>
                                        <p class="text-success mt-2">No emergency patients waiting</p>
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

<!-- Queue Audio Service is loaded in layouts/app.blade.php, no need to load again -->

<script>
const branchId = {{ $branchId }};
if (typeof window.csrfToken === 'undefined') {
    window.csrfToken = '{{ csrf_token() }}';
}

function callNextPatient() {
    if (!confirm('Call the next EMERGENCY patient in queue?')) return;
    
    // Get fresh CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch('/queues/call-next', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken || window.csrfToken},
        body: JSON.stringify({queue_type: 'Emergency', branch_id: branchId})
    }).then(response => response.json()).then(data => {
        if (data.success) { 
            showAlert('success', data.message);
            
            // Audio announcement
            if (data.data && data.data.patient) {
                const ticketNumber = data.data.ticket_number || data.data.short_ticket || data.data.position;
                const patientName = `${data.data.patient.first_name} ${data.data.patient.last_name}`;
                
                // Check if audio service is available
                if (typeof window.queueAudio !== 'undefined' && window.queueAudio.enabled) {
                    try {
                        window.callPatient(ticketNumber, patientName, 'Emergency', 'Emergency Room');
                    } catch (error) {
                        console.error('Audio announcement error:', error);
                        showAlert('warning', 'Patient called but audio announcement failed');
                    }
                } else {
                    console.log('Audio service not available or disabled');
                }
            }
            
            setTimeout(() => location.reload(), 2000); 
        }
        else { showAlert('error', data.message); }
    });
}

function startServing(queueId) {
    if (!confirm('Start serving this EMERGENCY patient immediately?')) return;
    fetch(`/queues/${queueId}/start-serving`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken}
    }).then(response => response.json()).then(data => {
        if (data.success) { showAlert('success', data.message); setTimeout(() => location.reload(), 1000); }
        else { showAlert('error', data.message); }
    });
}

function completeServing(queueId) {
    if (!confirm('Complete emergency service for this patient?')) return;
    fetch(`/queues/${queueId}/complete-serving`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken}
    }).then(response => response.json()).then(data => {
        if (data.success) { showAlert('success', data.message); setTimeout(() => location.reload(), 1000); }
        else { showAlert('error', data.message); }
    });
}

function markNoShow(queueId) {
    if (!confirm('Mark this emergency patient as no-show?')) return;
    fetch(`/queues/${queueId}/no-show`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken}
    }).then(response => response.json()).then(data => {
        if (data.success) { showAlert('success', data.message); setTimeout(() => location.reload(), 1000); }
        else { showAlert('error', data.message); }
    });
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 3000);
}

// Audio settings toggle
function toggleAudio() {
    if (typeof window.queueAudio === 'undefined') {
        showAlert('error', 'Audio service not loaded. Please refresh the page.');
        return;
    }
    
    if (window.queueAudio.enabled) {
        window.queueAudio.disable();
        showAlert('info', 'Audio announcements disabled');
    } else {
        window.queueAudio.enable();
        showAlert('success', 'Audio announcements enabled');
    }
}

// Test audio
function testAudio() {
    if (typeof window.queueAudio === 'undefined') {
        showAlert('error', 'Audio service not loaded. Please refresh the page.');
        return;
    }
    
    try {
        window.queueAudio.test();
        showAlert('success', 'Test announcement played');
    } catch (error) {
        console.error('Audio test error:', error);
        showAlert('error', 'Audio test failed: ' + error.message);
    }
}

// Initialize audio service when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Wait for audio service to load
    setTimeout(() => {
        if (typeof window.queueAudio === 'undefined') {
            console.error('Queue audio service failed to load');
            showAlert('warning', 'Audio service not available. Some features may not work properly.');
        } else {
            console.log('Queue audio service loaded successfully');
            // Test if speech synthesis is supported
            if (!('speechSynthesis' in window)) {
                showAlert('warning', 'Speech synthesis not supported in this browser. Audio announcements will not work.');
            }
        }
    }, 1000);
});

// Faster refresh for emergency (5 seconds)
setInterval(() => location.reload(), 5000);
</script>

<style>
.queue-patient-card { padding: 15px; border-radius: 8px; background: #f8f9fa; }
.queue-patient-card.serving-emergency { 
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); 
    border: 3px solid #dc3545; 
    animation: pulse-border 2s infinite;
}

@keyframes pulse-border {
    0%, 100% { border-color: #dc3545; }
    50% { border-color: #ff6b6b; }
}

.badge-pulse {
    animation: pulse-badge 1.5s infinite;
}

@keyframes pulse-badge {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

tr.emergency-row.priority-critical { 
    background-color: #ffe6e6 !important; 
    font-weight: 600;
}

tr.emergency-row.priority-urgent { 
    background-color: #fff3cd !important; 
}

.sticky-top { position: sticky; top: 0; z-index: 10; }
</style>
@endsection

