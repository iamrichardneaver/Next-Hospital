@extends('layouts.app')

@section('title', 'Appointments')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Appointments</h1>
            <p class="text-secondary mb-0">Manage patient appointments and schedules</p>
        </div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('appointments.export'),
                'permission' => 'view_appointments',
            ])
            @can('create_appointments')
            <a href="{{ route('appointments.create') }}" class="btn btn-primary">
                <i class="bi bi-calendar-plus"></i> Schedule Appointment
            </a>
            @endcan
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-label">Total Appointments</div>
                <div class="stat-value">{{ number_format($statistics['total']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-label">Today's Appointments</div>
                <div class="stat-value">{{ number_format($statistics['today']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-label">Scheduled</div>
                <div class="stat-value">{{ number_format($statistics['scheduled']) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Completed</div>
                <div class="stat-value">{{ number_format($statistics['completed']) }}</div>
            </div>
        </div>
    </div>
    
    <!-- Appointments List -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 text-dark">All Appointments</h5>
                </div>
                <div class="col-md-6">
                    <input type="text" 
                           class="form-control" 
                           id="appointment-search" 
                           placeholder="Search appointments...">
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Appointment #</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $appointment)
                        <tr>
                            <td><strong class="text-primary">{{ $appointment->appointment_number }}</strong></td>
                            <td>
                                @if($appointment->patient)
                                <div>
                                    <div class="fw-bold">{{ $appointment->patient->first_name }} {{ $appointment->patient->last_name }}</div>
                                    <small class="text-muted">{{ $appointment->patient->patient_number }}</small>
                                </div>
                                @else
                                <div>
                                    <div class="fw-bold text-danger">Patient Not Found</div>
                                    <small class="text-muted">Deleted or Invalid</small>
                                </div>
                                @endif
                            </td>
                            <td>{{ $appointment->doctor->name ?? 'N/A' }}</td>
                            <td>
                                <div>
                                    <div>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') }}</div>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A') }}</small>
                                </div>
                            </td>
                            <td>
                                @if($appointment->appointment_type === 'teleconsultation')
                                    <span class="badge bg-info">
                                        <i class="bi bi-camera-video"></i> Virtual
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-person"></i> In-Person
                                    </span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'scheduled' => 'warning',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        'no-show' => 'secondary'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$appointment->status] ?? 'secondary' }}">
                                    {{ ucfirst($appointment->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @can('view_appointments')
                                    <a href="{{ route('appointments.show', $appointment) }}" 
                                       class="btn btn-sm btn-info" 
                                       title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @endcan
                                    
                                    @can('edit_appointments')
                                    <a href="{{ route('appointments.edit', $appointment) }}" 
                                       class="btn btn-sm btn-warning" 
                                       title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    
                                    @can('delete_appointments')
                                    <form action="{{ route('appointments.destroy', $appointment) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this appointment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-calendar-x text-secondary" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="text-secondary mt-2 mb-0">No appointments found</p>
                                @can('create_appointments')
                                <a href="{{ route('appointments.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-calendar-plus"></i> Schedule First Appointment
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($appointments->hasPages())
        <div class="card-footer">
            {{ $appointments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
