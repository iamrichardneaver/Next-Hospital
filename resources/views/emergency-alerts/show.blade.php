@extends('layouts.app')

@section('title', 'Emergency Alert Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Emergency Alert Details</h1>
            <p class="text-secondary mb-0">Alert #{{ $emergencyAlert->id }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('emergency-alerts.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Alerts
            </a>
            @if($emergencyAlert->status === 'active')
                <form method="POST" action="{{ route('emergency-alerts.acknowledge', $emergencyAlert) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Acknowledge this alert?')">
                        <i class="bi bi-check-circle"></i> Acknowledge
                    </button>
                </form>
            @endif
            @if($emergencyAlert->status === 'acknowledged')
                <button class="btn btn-success" onclick="resolveAlert()">
                    <i class="bi bi-check2-all"></i> Resolve
                </button>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Alert Details -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Alert Information</h5>
                    <span class="badge bg-{{ $emergencyAlert->status === 'active' ? 'danger' : ($emergencyAlert->status === 'acknowledged' ? 'warning' : 'success') }} fs-6">
                        {{ ucfirst($emergencyAlert->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Alert ID:</strong></td>
                                    <td><span class="badge bg-primary">#{{ $emergencyAlert->id }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Alert Type:</strong></td>
                                    <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $emergencyAlert->alert_type)) }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Priority:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $emergencyAlert->priority === 'critical' ? 'danger' : ($emergencyAlert->priority === 'high' ? 'warning' : ($emergencyAlert->priority === 'medium' ? 'info' : 'secondary')) }}">
                                            {{ strtoupper($emergencyAlert->priority) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td>{{ $emergencyAlert->created_at->format('M d, Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Created By:</strong></td>
                                    <td>{{ $emergencyAlert->creator->first_name ?? 'Unknown' }} {{ $emergencyAlert->creator->last_name ?? '' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                @if($emergencyAlert->acknowledged_at)
                                <tr>
                                    <td><strong>Acknowledged:</strong></td>
                                    <td>{{ $emergencyAlert->acknowledged_at->format('M d, Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Acknowledged By:</strong></td>
                                    <td>{{ $emergencyAlert->acknowledgedBy->first_name ?? 'Unknown' }} {{ $emergencyAlert->acknowledgedBy->last_name ?? '' }}</td>
                                </tr>
                                @endif
                                @if($emergencyAlert->resolved_at)
                                <tr>
                                    <td><strong>Resolved:</strong></td>
                                    <td>{{ $emergencyAlert->resolved_at->format('M d, Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Resolved By:</strong></td>
                                    <td>{{ $emergencyAlert->resolvedBy->first_name ?? 'Unknown' }} {{ $emergencyAlert->resolvedBy->last_name ?? '' }}</td>
                                </tr>
                                @endif
                                @if($emergencyAlert->location)
                                <tr>
                                    <td><strong>Location:</strong></td>
                                    <td>{{ $emergencyAlert->location }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6><strong>Alert Message:</strong></h6>
                        <div class="alert alert-{{ $emergencyAlert->priority === 'critical' ? 'danger' : ($emergencyAlert->priority === 'high' ? 'warning' : 'info') }}">
                            {{ $emergencyAlert->message }}
                        </div>
                    </div>
                    
                    @if($emergencyAlert->acknowledgment_notes)
                    <div class="mt-3">
                        <h6><strong>Acknowledgment Notes:</strong></h6>
                        <div class="alert alert-info">
                            {{ $emergencyAlert->acknowledgment_notes }}
                        </div>
                    </div>
                    @endif
                    
                    @if($emergencyAlert->resolution_notes)
                    <div class="mt-3">
                        <h6><strong>Resolution Notes:</strong></h6>
                        <div class="alert alert-success">
                            {{ $emergencyAlert->resolution_notes }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Patient Information -->
            @if($emergencyAlert->emergencyVisit && $emergencyAlert->emergencyVisit->patient)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Patient Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Patient Name:</strong></td>
                                    <td>{{ $emergencyAlert->emergencyVisit->patient->first_name }} {{ $emergencyAlert->emergencyVisit->patient->last_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Patient Number:</strong></td>
                                    <td><span class="badge bg-primary">{{ $emergencyAlert->emergencyVisit->patient->patient_number }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Gender:</strong></td>
                                    <td>{{ $emergencyAlert->emergencyVisit->patient->gender }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Date of Birth:</strong></td>
                                    <td>{{ $emergencyAlert->emergencyVisit->patient->date_of_birth ? \Carbon\Carbon::parse($emergencyAlert->emergencyVisit->patient->date_of_birth)->format('M d, Y') : 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>{{ $emergencyAlert->emergencyVisit->patient->phone ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $emergencyAlert->emergencyVisit->patient->email ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Address:</strong></td>
                                    <td>{{ $emergencyAlert->emergencyVisit->patient->address ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Emergency Contact:</strong></td>
                                    <td>{{ $emergencyAlert->emergencyVisit->patient->emergency_contact ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="{{ route('patients.show', $emergencyAlert->emergencyVisit->patient) }}" class="btn btn-outline-primary">
                            <i class="bi bi-eye"></i> View Full Patient Profile
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>
        
        <div class="col-md-4">
            <!-- Alert Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> Actions</h5>
                </div>
                <div class="card-body">
                    @if($emergencyAlert->status === 'active')
                    @can('edit_emergency_alerts')
                        <form method="POST" action="{{ route('emergency-alerts.acknowledge', $emergencyAlert) }}">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100 mb-2" onclick="return confirm('Acknowledge this alert?')">
                                <i class="bi bi-check-circle"></i> Acknowledge Alert
                            </button>
                        </form>
                    @endcan
                    @endif
                    
                    @if($emergencyAlert->status === 'acknowledged')
                    @can('edit_emergency_alerts')
                        <button class="btn btn-success w-100 mb-2" onclick="resolveAlert()">
                            <i class="bi bi-check2-all"></i> Resolve Alert
                        </button>
                    @endcan
                    @endif
                    
                    @can('delete_emergency_alerts')
                    <form method="POST" action="{{ route('emergency-alerts.destroy', $emergencyAlert) }}" onsubmit="return confirm('Delete this alert?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash"></i> Delete Alert
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
            
            <!-- Alert Timeline -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Alert Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Alert Created</h6>
                                <p class="mb-1 text-muted">{{ $emergencyAlert->created_at->format('M d, Y H:i') }}</p>
                                <small>Created by {{ $emergencyAlert->creator->first_name ?? 'Unknown' }}</small>
                            </div>
                        </div>
                        
                        @if($emergencyAlert->acknowledged_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Alert Acknowledged</h6>
                                <p class="mb-1 text-muted">{{ $emergencyAlert->acknowledged_at->format('M d, Y H:i') }}</p>
                                <small>Acknowledged by {{ $emergencyAlert->acknowledgedBy->first_name ?? 'Unknown' }}</small>
                            </div>
                        </div>
                        @endif
                        
                        @if($emergencyAlert->resolved_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Alert Resolved</h6>
                                <p class="mb-1 text-muted">{{ $emergencyAlert->resolved_at->format('M d, Y H:i') }}</p>
                                <small>Resolved by {{ $emergencyAlert->resolvedBy->first_name ?? 'Unknown' }}</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resolve Alert Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resolve Emergency Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('emergency-alerts.resolve', $emergencyAlert) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Resolution Notes</label>
                        <textarea class="form-control" name="resolution_notes" rows="3" placeholder="Enter resolution details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Resolve Alert</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content h6 {
    font-size: 0.9rem;
    font-weight: 600;
}

.timeline-content p {
    font-size: 0.8rem;
}

.timeline-content small {
    font-size: 0.75rem;
}
</style>

<script>
function resolveAlert() {
    const modal = new bootstrap.Modal(document.getElementById('resolveModal'));
    modal.show();
}
</script>
@endsection
