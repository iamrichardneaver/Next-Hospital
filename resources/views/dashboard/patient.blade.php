@extends('layouts.app')

@section('title', 'Patient Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-person-heart"></i> Patient Portal
            </h1>
            <p class="text-secondary mb-0">Welcome back, {{ auth()->user()->name }}!</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success">{{ $userRole }}</span>
            <span class="badge bg-info">
                <i class="bi bi-clock"></i> {{ now()->format('M d, Y h:i A') }}
            </span>
            <span class="badge bg-primary">
                <i class="bi bi-person-badge"></i> {{ $patient->patient_number ?? 'N/A' }}
            </span>
        </div>
    </div>
    
    <!-- Patient Statistics Cards -->
    <div class="row mb-4">
        <!-- Total Appointments -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-label">Total Appointments</div>
                <div class="stat-value">{{ number_format($statistics['total_appointments']) }}</div>
                <div class="small opacity-75">
                    <i class="bi bi-info-circle"></i> All time appointments
                </div>
            </div>
        </div>
        
        <!-- Upcoming Appointments -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div class="stat-label">Upcoming Appointments</div>
                <div class="stat-value">{{ number_format($statistics['upcoming_appointments']) }}</div>
                <div class="small opacity-75">
                    <i class="bi bi-clock"></i> Scheduled visits
                </div>
            </div>
        </div>
        
        <!-- Completed Consultations -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div class="stat-label">Completed Consultations</div>
                <div class="stat-value">{{ number_format($statistics['completed_consultations']) }}</div>
                <div class="small opacity-75">
                    <i class="bi bi-check-circle"></i> Finished visits
                </div>
            </div>
        </div>
        
        <!-- Lab Results Available -->
        <div class="col-lg-3 col-md-6 mb-3">
            <a href="{{ route('lab.my-results') }}" class="text-decoration-none">
                <div class="stat-card info" style="cursor: pointer;">
                    <div class="stat-icon">
                        <i class="bi bi-clipboard-data"></i>
                    </div>
                    <div class="stat-label">Lab Results Available</div>
                    <div class="stat-value">{{ number_format($statistics['lab_results_available']) }}</div>
                    <div class="small opacity-75">
                        <i class="bi bi-file-medical"></i> Ready for review
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Secondary Stats Row -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="stat-card secondary">
                <div class="stat-icon">
                    <i class="bi bi-prescription"></i>
                </div>
                <div class="stat-label">Pending Prescriptions</div>
                <div class="stat-value">{{ number_format($statistics['pending_prescriptions']) }}</div>
                <div class="small opacity-75">
                    <i class="bi bi-hourglass-split"></i> Awaiting collection
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="stat-card dark">
                <div class="stat-icon">
                    <i class="bi bi-hospital"></i>
                </div>
                <div class="stat-label">Total Visits</div>
                <div class="stat-value">{{ number_format($statistics['total_visits']) }}</div>
                <div class="small opacity-75">
                    <i class="bi bi-graph-up"></i> All time visits
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Upcoming Appointments -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-calendar-event"></i> Upcoming Appointments
                    </h5>
                </div>
                <div class="card-body">
                    @if($upcomingAppointments->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($upcomingAppointments as $appointment)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="bi bi-person-badge"></i> Dr. {{ $appointment->doctor->name ?? 'TBD' }}
                                        </h6>
                                        <p class="mb-1 text-muted">{{ $appointment->reason ?? 'General consultation' }}</p>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> {{ $appointment->appointment_date->format('M d, Y') }}
                                            <i class="bi bi-clock ms-2"></i> {{ $appointment->appointment_time ?? 'TBD' }}
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary">{{ ucfirst($appointment->status) }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x text-secondary" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-muted">No Upcoming Appointments</h5>
                            <p class="text-muted">You don't have any scheduled appointments at the moment.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Lab Results -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-clipboard-data"></i> Recent Lab Results
                    </h5>
                    <a href="{{ route('lab.my-results') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-arrow-right"></i> View All
                    </a>
                </div>
                <div class="card-body">
                    @if($recentLabResults->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentLabResults as $labRequest)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="bi bi-clipboard-check"></i> {{ $labRequest->testType->template->template_name ?? $labRequest->testType->test_name ?? $labRequest->test_type_name ?? 'Lab Test' }}
                                        </h6>
                                        <p class="mb-1 text-muted">Request #{{ $labRequest->lab_request_number ?? $labRequest->request_number }}</p>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> {{ $labRequest->created_at->format('M d, Y') }}
                                            @if($labRequest->completed_at)
                                                <i class="bi bi-check-circle text-success ms-2"></i> Completed
                                            @else
                                                <i class="bi bi-hourglass-split text-warning ms-2"></i> Processing
                                            @endif
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        @if($labRequest->status === 'completed' && $labRequest->results && $labRequest->results->count() > 0)
                                            <span class="badge bg-success mb-2 d-block">Ready</span>
                                            <a href="{{ route('lab.my-result-details', $labRequest) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        @else
                                            <span class="badge bg-warning">Processing</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-clipboard-x text-secondary" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-muted">No Lab Results</h5>
                            <p class="text-muted">You don't have any lab results available at the moment.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-clock-history"></i> Recent Activities
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Recent Appointments -->
                        <div class="col-md-6 mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-calendar-check"></i> Recent Appointments
                            </h6>
                            @if($recentActivities['appointments']->count() > 0)
                                <div class="list-group list-group-flush">
                                    @foreach($recentActivities['appointments'] as $appointment)
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong>Dr. {{ $appointment->doctor->name ?? 'TBD' }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $appointment->appointment_date->format('M d, Y') }}</small>
                                            </div>
                                            <span class="badge bg-{{ $appointment->status === 'completed' ? 'success' : ($appointment->status === 'cancelled' ? 'danger' : 'primary') }}">
                                                {{ ucfirst($appointment->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">No recent appointments</p>
                            @endif
                        </div>

                        <!-- Recent Consultations -->
                        <div class="col-md-6 mb-4">
                            <h6 class="text-success mb-3">
                                <i class="bi bi-clipboard-check"></i> Recent Consultations
                            </h6>
                            @if($recentActivities['consultations']->count() > 0)
                                <div class="list-group list-group-flush">
                                    @foreach($recentActivities['consultations'] as $consultation)
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong>Dr. {{ $consultation->doctor->name ?? 'TBD' }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $consultation->consultation_date->format('M d, Y') }}</small>
                                            </div>
                                            <span class="badge bg-{{ $consultation->status === 'completed' ? 'success' : 'warning' }}">
                                                {{ ucfirst($consultation->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">No recent consultations</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 1.5rem;
    color: white;
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card.success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.stat-card.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card.info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card.secondary {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: #333;
}

.stat-card.dark {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
    margin-bottom: 1rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    border-radius: 15px 15px 0 0 !important;
}

.list-group-item {
    border: none;
    border-bottom: 1px solid #f8f9fa;
}

.list-group-item:last-child {
    border-bottom: none;
}
</style>
@endpush
