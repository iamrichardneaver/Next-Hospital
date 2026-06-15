@extends('layouts.app')

@section('title', 'Teleconsultations')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Teleconsultations</h1>
            <p class="text-secondary mb-0">Manage virtual consultations and video calls</p>
        </div>
        <div class="d-flex gap-2">
            @can('teleconsultation.create')
            <a href="{{ route('teleconsultations.create') }}" class="btn btn-primary">
                <i class="bi bi-camera-video"></i> New Teleconsultation
            </a>
            @endcan
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-camera-video"></i>
                </div>
                <div class="stat-label">Total Teleconsultations</div>
                <div class="stat-value" id="stat-total">{{ number_format($teleconsultations->total()) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-label">Scheduled</div>
                <div class="stat-value" id="stat-scheduled">
                    {{ number_format($teleconsultations->where('status', 'scheduled')->count()) }}
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-play-circle"></i>
                </div>
                <div class="stat-label">In Progress</div>
                <div class="stat-value" id="stat-in-progress">
                    {{ number_format($teleconsultations->where('status', 'in_progress')->count()) }}
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Completed</div>
                <div class="stat-value" id="stat-completed">
                    {{ number_format($teleconsultations->where('status', 'completed')->count()) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark">Filter Teleconsultations</h5>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="bi bi-funnel"></i> Toggle Filters
            </button>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body">
                <form method="GET" action="{{ route('teleconsultations.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="waiting" {{ request('status') == 'waiting' ? 'selected' : '' }}>Waiting</option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="consultation_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="video" {{ request('consultation_type') == 'video' ? 'selected' : '' }}>Video</option>
                            <option value="audio" {{ request('consultation_type') == 'audio' ? 'selected' : '' }}>Audio</option>
                            <option value="chat" {{ request('consultation_type') == 'chat' ? 'selected' : '' }}>Chat</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Doctor</label>
                        <select name="doctor_id" class="form-select">
                            <option value="">All Doctors</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}" {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                    Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Patient</label>
                        <select name="patient_id" class="form-select">
                            <option value="">All Patients</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                                    {{ $patient->first_name }} {{ $patient->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search by patient name or phone..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2 w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-12 text-end">
                        <a href="{{ route('teleconsultations.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Teleconsultations Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark">All Teleconsultations</h5>
            <div class="btn-group" role="group">
                <a href="{{ route('teleconsultations.index') }}" class="btn btn-outline-primary btn-sm {{ !request('status') ? 'active' : '' }}">All</a>
                <a href="{{ route('teleconsultations.index', ['status' => 'scheduled']) }}" class="btn btn-outline-warning btn-sm {{ request('status') === 'scheduled' ? 'active' : '' }}">Scheduled</a>
                <a href="{{ route('teleconsultations.index', ['status' => 'in_progress']) }}" class="btn btn-outline-info btn-sm {{ request('status') === 'in_progress' ? 'active' : '' }}">Active</a>
                <a href="{{ route('teleconsultations.index', ['status' => 'completed']) }}" class="btn btn-outline-success btn-sm {{ request('status') === 'completed' ? 'active' : '' }}">Completed</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Scheduled</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teleconsultations as $teleconsultation)
                        <tr>
                            <td><strong class="text-primary">{{ $teleconsultation->teleconsultation_number ?? 'TC-' . $teleconsultation->id }}</strong></td>
                            <td>
                                @if($teleconsultation->patient)
                                    <div>
                                        <div class="fw-bold">{{ $teleconsultation->patient->first_name }} {{ $teleconsultation->patient->last_name }}</div>
                                        <small class="text-muted">{{ $teleconsultation->patient->phone }}</small>
                                    </div>
                                @else
                                    <span class="text-danger">Patient Not Found</span>
                                @endif
                            </td>
                            <td>
                                @if($teleconsultation->doctor)
                                    <div>
                                        <div>Dr. {{ $teleconsultation->doctor->first_name }} {{ $teleconsultation->doctor->last_name }}</div>
                                        <small class="text-muted">{{ $teleconsultation->doctor->email }}</small>
                                    </div>
                                @else
                                    <span class="text-muted">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <div>{{ $teleconsultation->scheduled_at->format('M d, Y') }}</div>
                                    <small class="text-muted">{{ $teleconsultation->scheduled_at->format('h:i A') }}</small>
                                </div>
                            </td>
                            <td>
                                @php
                                    $typeColors = [
                                        'video' => 'primary',
                                        'audio' => 'success',
                                        'chat' => 'info'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $typeColors[$teleconsultation->consultation_type] ?? 'secondary' }}">
                                    <i class="bi bi-{{ $teleconsultation->consultation_type === 'video' ? 'camera-video' : ($teleconsultation->consultation_type === 'audio' ? 'mic' : 'chat') }}"></i>
                                    {{ ucfirst($teleconsultation->consultation_type) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'scheduled' => 'warning',
                                        'waiting' => 'info',
                                        'in_progress' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        'failed' => 'dark'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$teleconsultation->status] ?? 'secondary' }}">
                                    {{ ucfirst(str_replace('_', ' ', $teleconsultation->status)) }}
                                </span>
                            </td>
                            <td>
                                @if($teleconsultation->duration_minutes)
                                    <span class="fw-bold">{{ $teleconsultation->duration_minutes }} min</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('teleconsultations.show', $teleconsultation) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('teleconsultation.edit')
                                    <a href="{{ route('teleconsultations.edit', $teleconsultation) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    @can('teleconsultation.delete')
                                    <form action="{{ route('teleconsultations.destroy', $teleconsultation) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this teleconsultation?')">
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
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-camera-video-off text-secondary" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="text-secondary mt-2 mb-0">No teleconsultations found</p>
                                @can('teleconsultation.create')
                                <a href="{{ route('teleconsultations.create') }}" class="btn btn-sm btn-primary mt-2">
                                    <i class="bi bi-plus-circle"></i> Create Teleconsultation
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($teleconsultations->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Showing {{ $teleconsultations->firstItem() ?? 0 }} to {{ $teleconsultations->lastItem() ?? 0 }} of {{ $teleconsultations->total() }} entries
                </div>
                <div>
                    {{ $teleconsultations->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
