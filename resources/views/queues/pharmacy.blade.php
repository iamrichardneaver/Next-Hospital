@extends('layouts.app')

@section('title', 'Pharmacy Queue')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-capsule"></i> Pharmacy Queue Management</h1>
            <p class="text-secondary mb-0">Prescription Dispensing Queue</p>
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
            @can('manage_queues|manage_pharmacy_queue')
            <button class="btn btn-success" onclick="callNextPatient()">
                <i class="bi bi-bell"></i> Call Next Patient
            </button>
            @endcan
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
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
            <div class="card shadow-sm" style="border-left: 4px solid #28a745;">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-person-check-fill"></i> Currently Serving</h5>
                </div>
                <div class="card-body">
                    @if($servingQueue)
                        <div class="queue-patient-card serving">
                            @if($servingQueue->patient)
                                <h5 class="mb-2">{{ $servingQueue->patient->first_name }} {{ $servingQueue->patient->last_name }}</h5>
                                <p class="mb-1"><span class="badge bg-primary">{{ $servingQueue->patient->patient_number }}</span></p>
                                <p class="mb-1"><i class="bi bi-telephone"></i> {{ $servingQueue->patient->phone ?? 'N/A' }}</p>
                            @else
                                <h5 class="mb-2 text-danger">Patient Not Found</h5>
                                <p class="mb-1"><span class="badge bg-danger">ID: {{ $servingQueue->patient_id }}</span></p>
                                <p class="mb-1"><i class="bi bi-telephone"></i> N/A</p>
                            @endif
                            <p class="mb-2">
                                <span class="badge bg-{{ $servingQueue->priority === 'critical' ? 'danger' : ($servingQueue->priority === 'urgent' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($servingQueue->priority) }}
                                </span>
                            </p>
                            @can('manage_queues|manage_pharmacy_queue')
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

        <!-- Waiting Queue -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm" style="border-left: 4px solid #28a745;">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ol"></i> Waiting Queue ({{ count($waitingQueues) }})</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 50px;">Pos</th>
                                    <th>Patient</th>
                                    <th>Contact</th>
                                    <th>Priority</th>
                                    <th>Wait Time</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($waitingQueues as $queue)
                                <tr class="priority-{{ $queue->priority }}">
                                    <td><span class="badge bg-secondary">{{ $queue->position }}</span></td>
                                    <td>
                                        @if($queue->patient)
                                            <strong>{{ $queue->patient->first_name }} {{ $queue->patient->last_name }}</strong><br>
                                            <small class="text-muted">{{ $queue->patient->patient_number }}</small>
                                        @else
                                            <strong class="text-danger">Patient Not Found</strong><br>
                                            <small class="text-muted">ID: {{ $queue->patient_id }}</small>
                                        @endif
                                    </td>
                                    <td><small>{{ $queue->patient ? ($queue->patient->phone ?? 'N/A') : 'N/A' }}</small></td>
                                    <td>
                                        <span class="badge bg-{{ $queue->priority === 'critical' ? 'danger' : ($queue->priority === 'urgent' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($queue->priority) }}
                                        </span>
                                    </td>
                                    <td><small>{{ $queue->queued_at->diffForHumans() }}</small></td>
                                    <td>
                                        @can('manage_queues|manage_pharmacy_queue')
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-success" onclick="startServing({{ $queue->id }})">
                                                <i class="bi bi-play-fill"></i>
                                            </button>
                                            <button class="btn btn-warning" onclick="markNoShow({{ $queue->id }})">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </div>
                                        @endcan
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                                        <p class="text-muted mt-2">No patients in waiting queue</p>
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
    if (!confirm('Call the next patient in queue?')) return;
    
    // Get fresh CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    const callNextUrl = '{{ route("queues.call-next") }}';
    fetch(callNextUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken || window.csrfToken},
        body: JSON.stringify({queue_type: 'Pharmacy', branch_id: branchId})
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) { 
            showAlert('success', data.message);
            
            // Audio announcement
            if (data.data && data.data.patient) {
                const ticketNumber = data.data.ticket_number || data.data.short_ticket || data.data.position;
                const patientName = `${data.data.patient.first_name} ${data.data.patient.last_name}`;
                
                // Check if audio service is available
                if (typeof window.queueAudio !== 'undefined' && window.queueAudio.enabled) {
                    try {
                        window.callPatient(ticketNumber, patientName, 'Pharmacy', 'Pharmacy Counter');
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
    })
    .catch(error => {
        console.error('Error calling next patient:', error);
        showAlert('error', 'Error calling next patient: ' + error.message);
    });
}

function startServing(queueId) {
    if (!confirm('Start serving this patient?')) return;
    
    const startServingUrl = '{{ route("queues.start-serving", ":id") }}'.replace(':id', queueId);
    fetch(startServingUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken}
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) { 
            showAlert('success', data.message); 
            setTimeout(() => location.reload(), 1000); 
        }
        else { 
            showAlert('error', data.message); 
        }
    })
    .catch(error => {
        console.error('Error starting service:', error);
        showAlert('error', 'Error starting service: ' + error.message);
    });
}

function completeServing(queueId) {
    if (!confirm('Complete service for this patient?')) return;
    
    const completeServingUrl = '{{ route("queues.complete-serving", ":id") }}'.replace(':id', queueId);
    fetch(completeServingUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken}
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) { 
            showAlert('success', data.message); 
            setTimeout(() => location.reload(), 1000); 
        }
        else { 
            showAlert('error', data.message); 
        }
    })
    .catch(error => {
        console.error('Error completing service:', error);
        showAlert('error', 'Error completing service: ' + error.message);
    });
}

function markNoShow(queueId) {
    if (!confirm('Mark this patient as no-show?')) return;
    
    const noShowUrl = '{{ route("queues.no-show", ":id") }}'.replace(':id', queueId);
    fetch(noShowUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken}
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) { 
            showAlert('success', data.message); 
            setTimeout(() => location.reload(), 1000); 
        }
        else { 
            showAlert('error', data.message); 
        }
    })
    .catch(error => {
        console.error('Error marking no-show:', error);
        showAlert('error', 'Error marking no-show: ' + error.message);
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

// Auto-refresh removed - page updates on user actions
</script>

<style>
.queue-patient-card { padding: 15px; border-radius: 8px; background: #f8f9fa; }
.queue-patient-card.serving { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 2px solid #28a745; }
tr.priority-critical { background-color: #ffe6e6 !important; }
tr.priority-urgent { background-color: #fff3cd !important; }
.sticky-top { position: sticky; top: 0; z-index: 10; }
</style>
@endsection

