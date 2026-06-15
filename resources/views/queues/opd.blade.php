@extends('layouts.app')

@section('title', 'OPD Queue')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-hospital"></i> OPD Queue Management</h1>
            <p class="text-secondary mb-0">Outpatient Department Queue</p>
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
            @canany(['manage_queues', 'manage_opd_queue'])
            <button class="btn btn-primary" onclick="callNextPatient()">
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
                <div class="stat-value" id="stat-waiting">{{ $stats['total_waiting'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                <div class="stat-label">Being Served</div>
                <div class="stat-value" id="stat-serving">{{ $stats['total_serving'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stat-label">Completed Today</div>
                <div class="stat-value" id="stat-completed">{{ $stats['total_completed_today'] }}</div>
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
        <!-- Currently Serving -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm" style="border-left: 4px solid #28a745;">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-person-check-fill"></i> Currently Serving</h5>
                </div>
                <div class="card-body" id="serving-patient">
                    @if($servingQueue)
                        <div class="queue-patient-card serving">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    @if($servingQueue->patient)
                                        <h5 class="mb-1">{{ $servingQueue->patient->first_name }} {{ $servingQueue->patient->last_name }}</h5>
                                        <span class="badge bg-primary">{{ $servingQueue->patient->patient_number }}</span>
                                    @else
                                        <h5 class="mb-1 text-danger">Patient Not Found</h5>
                                        <span class="badge bg-danger">ID: {{ $servingQueue->patient_id }}</span>
                                    @endif
                                    @if($servingQueue->visit)
                                        <span class="badge bg-info">{{ $servingQueue->visit->visit_token }}</span>
                                    @endif
                                </div>
                                <span class="badge bg-{{ $servingQueue->priority === 'critical' ? 'danger' : ($servingQueue->priority === 'urgent' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($servingQueue->priority) }}
                                </span>
                            </div>
                            <p class="mb-1"><i class="bi bi-telephone"></i> {{ $servingQueue->patient->phone ?? 'N/A' }}</p>
                            <p class="mb-1"><i class="bi bi-gender-ambiguous"></i> {{ $servingQueue->patient->gender ?? 'N/A' }}</p>
                            <p class="mb-2"><i class="bi bi-person-badge"></i> {{ $servingQueue->servedBy->first_name ?? 'N/A' }} {{ $servingQueue->servedBy->last_name ?? '' }}</p>
                            @canany(['manage_queues', 'manage_opd_queue'])
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
            <div class="card shadow-sm" style="border-left: 4px solid #007bff;">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ol"></i> Waiting Queue ({{ count($waitingQueues) }})</h5>
                    <span class="badge bg-light text-dark">Updates every 10s</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 50px;">Pos</th>
                                    <th>Patient</th>
                                    <th>Contact</th>
                                    <th>Payment</th>
                                    <th>Priority</th>
                                    <th>Wait Time</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="waiting-queue-tbody">
                                @forelse($waitingQueues as $queue)
                                <tr class="queue-row priority-{{ $queue->priority }}" data-queue-id="{{ $queue->id }}">
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
                                    <td><small>{{ $queue->patient->phone ?? 'N/A' }}</small></td>
                                    <td>
                                        @php $ps = $queue->payment_summary ?? ['is_paid' => true, 'amount_due' => 0]; @endphp
                                        @if($ps['is_paid'])
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Paid</span>
                                        @else
                                            <span class="badge bg-danger" title="Full payment required before service">
                                                <i class="bi bi-exclamation-triangle"></i> Unpaid
                                            </span>
                                            <br><small class="text-danger fw-bold">GH₵{{ number_format($ps['amount_due'], 2) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $queue->priority === 'critical' ? 'danger' : ($queue->priority === 'urgent' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($queue->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $queue->queued_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @canany(['manage_queues', 'manage_opd_queue'])
                                        <div class="btn-group btn-group-sm">
                                            @if(!($ps['is_paid'] ?? true))
                                            <button class="btn btn-warning" onclick="redirectToCashier({{ $queue->patient_id }}, {{ $ps['amount_due'] }})" title="Pay full amount at cashier">
                                                <i class="bi bi-cash-coin"></i>
                                            </button>
                                            <button class="btn btn-success" disabled title="Full payment required before serving">
                                                <i class="bi bi-play-fill"></i>
                                            </button>
                                            @else
                                            <button class="btn btn-success" onclick="startServing({{ $queue->id }})" title="Start Serving">
                                                <i class="bi bi-play-fill"></i>
                                            </button>
                                            @endif
                                            <button class="btn btn-info text-white" onclick="printTicket({{ $queue->id }})" title="Print Ticket">
                                                <i class="bi bi-printer"></i>
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

<!-- Queue Actions JavaScript -->
<script>
const branchId = {{ $branchId }};
if (typeof window.csrfToken === 'undefined') {
    window.csrfToken = '{{ csrf_token() }}';
}

// Call next patient
function callNextPatient() {
    if (!confirm('Call the next patient in queue?')) return;

    // Get fresh CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch('/queues/call-next', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || window.csrfToken
        },
        body: JSON.stringify({
            queue_type: 'OPD',
            branch_id: branchId
        })
    })
    .then(response => response.json())
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
                        window.callPatient(ticketNumber, patientName, 'OPD', 'Consultation Room');
                    } catch (error) {
                        console.error('Audio announcement error:', error);
                        showAlert('warning', 'Patient called but audio announcement failed');
                    }
                } else {
                    console.log('Audio service not available or disabled');
                }
            }
            
            setTimeout(() => location.reload(), 2000);
        } else if (data.payment_required) {
            showPaymentRequiredAlert(data);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error calling patient');
    });
}

function redirectToCashier(patientId, amountDue) {
    if (confirm(`Full payment of GH₵${parseFloat(amountDue).toFixed(2)} is required before service. Go to Cashier now?`)) {
        window.open(`{{ url('/cashier') }}?patient_id=${patientId}`, '_blank');
    }
}

function showPaymentRequiredAlert(data) {
    const msg = data.message || 'Full payment required before service';
    const amount = data.amount_due ? ` Amount due: GH₵${parseFloat(data.amount_due).toFixed(2)}.` : '';
    showAlert('error', msg + amount);
    if (data.patient_id && confirm('Open Cashier to collect full payment?')) {
        window.open(data.cashier_url || '{{ url('/cashier') }}' + `?patient_id=${data.patient_id}`, '_blank');
    }
}

// Start serving a patient
function startServing(queueId) {
    if (!confirm('Start serving this patient?')) return;

    fetch(`/queues/${queueId}/start-serving`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => location.reload(), 1000);
        } else if (data.payment_required) {
            showPaymentRequiredAlert(data);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error starting service');
    });
}

// Complete serving
function completeServing(queueId) {
    if (!confirm('Complete service for this patient?')) return;

    fetch(`/queues/${queueId}/complete-serving`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
        }
    })
    .then(response => response.json())
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
        showAlert('error', 'Error completing service');
    });
}

// Mark no-show
function markNoShow(queueId) {
    if (!confirm('Mark this patient as no-show?')) return;

    fetch(`/queues/${queueId}/no-show`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
        }
    })
    .then(response => response.json())
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
        showAlert('error', 'Error marking no-show');
    });
}

// Print ticket
function printTicket(queueId) {
    const printUrl = `/queues/${queueId}/print-ticket`;
    window.open(printUrl, '_blank', 'width=350,height=600');
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

// Auto-refresh every 10 seconds
setInterval(() => {
    location.reload();
}, 10000);
</script>

<style>
.queue-patient-card {
    padding: 15px;
    border-radius: 8px;
    background: #f8f9fa;
}

.queue-patient-card.serving {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border: 2px solid #28a745;
}

.queue-row.priority-critical {
    background-color: #ffe6e6 !important;
}

.queue-row.priority-urgent {
    background-color: #fff3cd !important;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}
</style>
@endsection

