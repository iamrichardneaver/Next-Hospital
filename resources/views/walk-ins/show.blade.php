@extends('layouts.app')

@section('title', 'Visit Details - Walk-ins Register')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <!-- Toolbar -->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                    Visit Details
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('walk-ins.index') }}" class="text-muted text-hover-primary">Walk-ins Register</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Visit Details</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('walk-ins.index') }}" class="btn btn-sm btn-light">
                    <i class="bi bi-arrow-left fs-4"></i> Back to Register
                </a>
                @can('view_patients')
                <a href="{{ route('patients.show', $visit->patient_id) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-person fs-4"></i> View Patient
                </a>
                @endcan
            </div>
        </div>
    </div>

    <!-- Content -->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            
            <!-- Visit Overview -->
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">Visit Overview</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Visit Token</label>
                                <span class="badge badge-light-primary fs-6">{{ $visit->visit_token }}</span>
                            </div>
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Visit Type</label>
                                @php
                                    $typeColors = [
                                        'OPD' => 'primary',
                                        'IPD' => 'success',
                                        'Emergency' => 'danger',
                                        'LabOnly' => 'info',
                                        'PharmacyOnly' => 'warning'
                                    ];
                                    $color = $typeColors[$visit->visit_type] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $color }} fs-6">{{ $visit->visit_type }}</span>
                            </div>
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Status</label>
                                @php
                                    $statusColors = [
                                        'active' => 'success',
                                        'completed' => 'secondary',
                                        'cancelled' => 'danger',
                                        'transferred' => 'warning'
                                    ];
                                    $color = $statusColors[$visit->status] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $color }} fs-6">{{ ucfirst($visit->status) }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Check-in Time</label>
                                <span class="text-dark fw-bold">{{ $visit->check_in_time->format('d M Y, h:i A') }}</span>
                            </div>
                            @if($visit->check_out_time)
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Check-out Time</label>
                                <span class="text-dark fw-bold">{{ $visit->check_out_time->format('d M Y, h:i A') }}</span>
                            </div>
                            @endif
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Priority</label>
                                @php
                                    $priorityColors = [
                                        'routine' => 'secondary',
                                        'urgent' => 'warning',
                                        'critical' => 'danger'
                                    ];
                                    $color = $priorityColors[$visit->priority] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $color }} fs-6">{{ ucfirst($visit->priority) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Patient Information -->
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">Patient Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Patient Name</label>
                                @if($visit->patient)
                                    <span class="text-dark fw-bold fs-5">{{ $visit->patient->first_name }} {{ $visit->patient->last_name }}</span>
                                @else
                                    <span class="text-danger fw-bold fs-5">Patient Not Found</span>
                                @endif
                            </div>
                            @if($visit->patient)
                                <div class="d-flex flex-column mb-5">
                                    <label class="fw-bold text-muted mb-2">Patient ID</label>
                                    <span class="text-dark fw-bold">{{ $visit->patient->patient_number }}</span>
                                </div>
                                <div class="d-flex flex-column mb-5">
                                    <label class="fw-bold text-muted mb-2">Gender</label>
                                    <span class="text-dark fw-bold">{{ $visit->patient->gender }}</span>
                                </div>
                            @else
                                <div class="d-flex flex-column mb-5">
                                    <label class="fw-bold text-muted mb-2">Patient ID</label>
                                    <span class="text-muted">{{ $visit->patient_id }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            @if($visit->patient)
                                <div class="d-flex flex-column mb-5">
                                    <label class="fw-bold text-muted mb-2">Date of Birth</label>
                                    <span class="text-dark fw-bold">{{ $visit->patient->date_of_birth ? $visit->patient->date_of_birth->format('d M Y') : 'N/A' }}</span>
                                </div>
                                <div class="d-flex flex-column mb-5">
                                    <label class="fw-bold text-muted mb-2">Phone</label>
                                    <span class="text-dark fw-bold">{{ $visit->patient->phone }}</span>
                                </div>
                                @if($visit->patient->nhis_number)
                                <div class="d-flex flex-column mb-5">
                                    <label class="fw-bold text-muted mb-2">NHIS Number</label>
                                    <span class="badge badge-light-info">{{ $visit->patient->nhis_number }}</span>
                                </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Assignment -->
            @if($visit->assignedDoctor || $visit->assignedNurse)
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">Staff Assignment</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if($visit->assignedDoctor)
                        <div class="col-md-6">
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Assigned Doctor</label>
                                <span class="text-dark fw-bold fs-5">Dr. {{ $visit->assignedDoctor->first_name }} {{ $visit->assignedDoctor->last_name }}</span>
                            </div>
                        </div>
                        @endif
                        @if($visit->assignedNurse)
                        <div class="col-md-6">
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Assigned Nurse</label>
                                <span class="text-dark fw-bold fs-5">{{ $visit->assignedNurse->first_name }} {{ $visit->assignedNurse->last_name }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Queue Statistics -->
            @if($visit->queues->count() > 0)
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">Queue Statistics</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <div class="d-flex flex-column align-items-center">
                                <div class="symbol symbol-50px mb-3">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="bi bi-list-ol fs-2x text-primary"></i>
                                    </div>
                                </div>
                                <span class="text-dark fw-bold fs-4">{{ $queueStats['total_queues'] }}</span>
                                <span class="text-muted fs-7">Total Queues</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column align-items-center">
                                <div class="symbol symbol-50px mb-3">
                                    <div class="symbol-label bg-light-warning">
                                        <i class="bi bi-clock fs-2x text-warning"></i>
                                    </div>
                                </div>
                                <span class="text-dark fw-bold fs-4">{{ $queueStats['waiting'] }}</span>
                                <span class="text-muted fs-7">Waiting</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column align-items-center">
                                <div class="symbol symbol-50px mb-3">
                                    <div class="symbol-label bg-light-info">
                                        <i class="bi bi-telephone fs-2x text-info"></i>
                                    </div>
                                </div>
                                <span class="text-dark fw-bold fs-4">{{ $queueStats['called'] }}</span>
                                <span class="text-muted fs-7">Called</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column align-items-center">
                                <div class="symbol symbol-50px mb-3">
                                    <div class="symbol-label bg-light-success">
                                        <i class="bi bi-check-circle fs-2x text-success"></i>
                                    </div>
                                </div>
                                <span class="text-dark fw-bold fs-4">{{ $queueStats['completed'] }}</span>
                                <span class="text-muted fs-7">Completed</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column align-items-center">
                                <div class="symbol symbol-50px mb-3">
                                    <div class="symbol-label bg-light-danger">
                                        <i class="bi bi-x-circle fs-2x text-danger"></i>
                                    </div>
                                </div>
                                <span class="text-dark fw-bold fs-4">{{ $queueStats['cancelled'] }}</span>
                                <span class="text-muted fs-7">Cancelled</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column align-items-center">
                                <div class="symbol symbol-50px mb-3">
                                    <div class="symbol-label bg-light-secondary">
                                        <i class="bi bi-graph-up fs-2x text-secondary"></i>
                                    </div>
                                </div>
                                <span class="text-dark fw-bold fs-4">{{ $queueStats['average_wait_time'] ? round($queueStats['average_wait_time']) : '-' }}</span>
                                <span class="text-muted fs-7">Avg Wait (min)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Queue History -->
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">Queue History</h3>
                    <div class="card-toolbar">
                        <span class="badge badge-light-primary">{{ $visit->queues->count() }} Queue{{ $visit->queues->count() !== 1 ? 's' : '' }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($visit->queues->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-row-bordered table-row-gray-300 align-middle gs-0 gy-3">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th class="min-w-100px">Queue Type</th>
                                    <th class="min-w-100px">Status</th>
                                    <th class="min-w-100px">Position</th>
                                    <th class="min-w-100px">Priority</th>
                                    <th class="min-w-150px">Queued At</th>
                                    <th class="min-w-150px">Called At</th>
                                    <th class="min-w-150px">Completed At</th>
                                    <th class="min-w-100px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($visit->queues as $queue)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-40px me-3">
                                                <div class="symbol-label bg-light-{{ $queue->queue_type === 'OPD' ? 'primary' : ($queue->queue_type === 'Lab' ? 'warning' : ($queue->queue_type === 'Pharmacy' ? 'success' : 'info')) }}">
                                                    <i class="bi bi-{{ $queue->queue_type === 'OPD' ? 'person' : ($queue->queue_type === 'Lab' ? 'droplet' : ($queue->queue_type === 'Pharmacy' ? 'capsule' : 'clock')) }}"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="text-dark fw-bold">{{ $queue->queue_type }}</span>
                                                @if($queue->ticket_number)
                                                <br><small class="text-muted">Ticket: {{ $queue->ticket_number }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'waiting' => 'warning',
                                                'called' => 'info',
                                                'serving' => 'success',
                                                'completed' => 'secondary',
                                                'cancelled' => 'danger',
                                                'no_show' => 'dark'
                                            ];
                                            $color = $statusColors[$queue->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $color }} fs-7">{{ ucfirst(str_replace('_', ' ', $queue->status)) }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="text-dark fw-bold fs-6">{{ $queue->position ?? '-' }}</span>
                                            @if($queue->position)
                                            <span class="badge badge-light-primary ms-2 fs-8">#{{ $queue->position }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $priorityColors = [
                                                'routine' => 'secondary',
                                                'urgent' => 'warning',
                                                'critical' => 'danger'
                                            ];
                                            $color = $priorityColors[$queue->priority] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $color }} fs-7">{{ ucfirst($queue->priority) }}</span>
                                    </td>
                                    <td>
                                        @if($queue->queued_at)
                                        <div class="d-flex flex-column">
                                            <span class="text-dark fw-bold fs-7">{{ $queue->queued_at->format('d M Y') }}</span>
                                            <span class="text-muted fs-8">{{ $queue->queued_at->format('h:i A') }}</span>
                                        </div>
                                        @else
                                        <span class="text-muted fs-7">Not queued</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($queue->called_at)
                                        <div class="d-flex flex-column">
                                            <span class="text-dark fw-bold fs-7">{{ $queue->called_at->format('d M Y') }}</span>
                                            <span class="text-muted fs-8">{{ $queue->called_at->format('h:i A') }}</span>
                                            @if($queue->calledBy)
                                            <small class="text-muted fs-8">by {{ $queue->calledBy->first_name }} {{ $queue->calledBy->last_name }}</small>
                                            @endif
                                        </div>
                                        @else
                                        <span class="text-muted fs-7">Not called</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($queue->completed_at)
                                        <div class="d-flex flex-column">
                                            <span class="text-dark fw-bold fs-7">{{ $queue->completed_at->format('d M Y') }}</span>
                                            <span class="text-muted fs-8">{{ $queue->completed_at->format('h:i A') }}</span>
                                            @if($queue->servedBy)
                                            <small class="text-muted fs-8">by {{ $queue->servedBy->first_name }} {{ $queue->servedBy->last_name }}</small>
                                            @endif
                                        </div>
                                        @else
                                        <span class="text-muted fs-7">Not completed</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            @if($queue->status === 'waiting')
                                            <span class="badge badge-light-warning fs-8">Waiting</span>
                                            @elseif($queue->status === 'called')
                                            <span class="badge badge-light-info fs-8">Called</span>
                                            @elseif($queue->status === 'serving')
                                            <span class="badge badge-light-success fs-8">In Service</span>
                                            @elseif($queue->status === 'completed')
                                            <span class="badge badge-light-success fs-8">Completed</span>
                                            @elseif($queue->status === 'cancelled')
                                            <span class="badge badge-light-danger fs-8">Cancelled</span>
                                            @endif
                                            
                                            @if($queue->estimated_wait_time)
                                            <small class="text-muted fs-8">{{ $queue->estimated_wait_time }}min wait</small>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-10">
                        <div class="symbol symbol-100px mx-auto mb-5">
                            <div class="symbol-label bg-light-primary">
                                <i class="bi bi-clock-history fs-2x text-primary"></i>
                            </div>
                        </div>
                        <h4 class="text-gray-600 mb-3">No Queue History</h4>
                        <p class="text-muted">This visit has not been queued for any services yet.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Consultation Details -->
            @if($visit->consultation)
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">Consultation Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Consultation Date</label>
                                <span class="text-dark fw-bold">{{ $visit->consultation->consultation_date ? $visit->consultation->consultation_date->format('d M Y, h:i A') : 'N/A' }}</span>
                            </div>
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Status</label>
                                <span class="badge badge-{{ $visit->consultation->consultation_status == 'completed' ? 'success' : 'warning' }}">{{ ucfirst($visit->consultation->consultation_status) }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Chief Complaint</label>
                                <span class="text-dark fw-bold">{{ $visit->consultation->chief_complaint ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Emergency Details -->
            @if($visit->emergencyVisit)
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">Emergency Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Triage Level</label>
                                <span class="badge badge-danger">{{ ucfirst($visit->emergencyVisit->triage_level) }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column mb-5">
                                <label class="fw-bold text-muted mb-2">Arrival Mode</label>
                                <span class="text-dark fw-bold">{{ ucfirst($visit->emergencyVisit->arrival_mode) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh queue status every 30 seconds
    setInterval(function() {
        refreshQueueStatus();
    }, 30000);
    
    // Add hover effects to queue rows
    const queueRows = document.querySelectorAll('table tbody tr');
    queueRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
    
    // Add click handlers for queue actions
    const statusBadges = document.querySelectorAll('.badge');
    statusBadges.forEach(badge => {
        if (badge.textContent.includes('Waiting')) {
            badge.style.cursor = 'pointer';
            badge.title = 'Click to view queue details';
        }
    });
});

function refreshQueueStatus() {
    // This would typically make an AJAX call to refresh queue data
    // For now, we'll just update the timestamp
    const now = new Date();
    const timeString = now.toLocaleTimeString();
    
    // Update any "last updated" indicators if they exist
    const lastUpdated = document.querySelector('.last-updated');
    if (lastUpdated) {
        lastUpdated.textContent = 'Last updated: ' + timeString;
    }
}

// Add tooltip functionality
$(document).ready(function() {
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Initialize any additional UI components
    if (typeof KTApp !== 'undefined') {
        KTApp.init();
    }
});
</script>
@endsection
