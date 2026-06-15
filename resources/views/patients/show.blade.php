@extends('layouts.app')

@section('title', 'Patient Profile - ' . $patient->full_name)

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <!-- Enhanced Page Header -->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex align-items-center text-dark fw-bold fs-2 my-0">
                    <i class="bi bi-person-circle text-primary me-3 fs-1"></i>
                    <span>Patient Profile</span>
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('patients.index') }}" class="text-muted text-hover-primary">Patients</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-dark">{{ $patient->full_name }}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3 ms-auto">
                <a href="{{ route('patients.index') }}" class="btn btn-sm btn-light d-flex align-items-center">
                    <i class="bi bi-arrow-left me-2"></i> Back to Patients
                </a>
                @can('edit_patients')
                <a href="{{ route('patients.edit', $patient) }}" class="btn btn-sm btn-warning d-flex align-items-center">
                    <i class="bi bi-pencil me-2"></i> Edit Patient
                </a>
                @endcan
                @can('create_visits')
                <a href="{{ route('visits.create', ['patient_id' => $patient->id]) }}" class="btn btn-sm btn-success d-flex align-items-center">
                    <i class="bi bi-plus-circle me-2"></i> New Visit
                </a>
                @endcan
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
    
            <div class="row g-5">
                <!-- Enhanced Patient Info Sidebar -->
                <div class="col-lg-4">
                    <!-- Patient Profile Card -->
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <!-- Profile Header -->
                            <div class="profile-header bg-gradient-primary text-white p-4 text-center position-relative">
                                <div class="profile-avatar-container position-relative d-inline-block">
                    @if($patient->photo)
                        <img src="{{ asset('storage/' . $patient->photo) }}" alt="{{ $patient->full_name }}" 
                                             class="profile-avatar rounded-circle border-4 border-white shadow-lg">
                    @else
                                        <div class="profile-avatar bg-white text-primary rounded-circle border-4 border-white shadow-lg d-flex align-items-center justify-content-center">
                                            <span class="fw-bold fs-2">{{ strtoupper(substr($patient->first_name, 0, 1)) }}{{ strtoupper(substr($patient->last_name, 0, 1)) }}</span>
                        </div>
                    @endif
                                    <div class="status-indicator bg-success rounded-circle position-absolute bottom-0 end-0 border-3 border-white"></div>
                                </div>
                                <h3 class="text-white fw-bold mt-3 mb-1">{{ $patient->full_name }}</h3>
                                <p class="text-white-50 mb-2">{{ $patient->patient_number }}</p>
                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                    <span class="badge bg-white text-primary px-3 py-2">
                                        <i class="bi bi-gender-{{ strtolower($patient->gender) }} me-1"></i> {{ $patient->gender }}
                                    </span>
                                    <span class="badge bg-white text-primary px-3 py-2">
                                        <i class="bi bi-calendar me-1"></i> {{ $patient->age }} years
                    </span>
                                    @if($patient->registration_source)
                                    <span class="badge bg-{{ $patient->registration_source === 'mobile_app' ? 'info' : 'secondary' }} text-white px-3 py-2" title="Registration Source">
                                        <i class="bi bi-{{ $patient->registration_source === 'mobile_app' ? 'phone' : 'globe' }} me-1"></i> 
                                        {{ $patient->registration_source === 'mobile_app' ? 'Mobile App' : 'Web' }}
                                    </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Profile Details -->
                            <div class="p-4">
                                <div class="profile-details">
                                    <div class="detail-item d-flex align-items-center mb-3">
                                        <div class="detail-icon bg-light-primary text-primary rounded-circle me-3">
                                            <i class="bi bi-cake fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-gray-800">Date of Birth</div>
                                            <div class="text-muted">{{ $patient->date_of_birth ? $patient->date_of_birth->format('M d, Y') : 'Not provided' }}</div>
                                        </div>
                                    </div>

                                    <div class="detail-item d-flex align-items-center mb-3">
                                        <div class="detail-icon bg-light-success text-success rounded-circle me-3">
                                            <i class="bi bi-telephone fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-gray-800">Phone</div>
                                            <div class="text-muted">{{ $patient->phone ?? 'Not provided' }}</div>
                                        </div>
                                    </div>

                                    <div class="detail-item d-flex align-items-center mb-3">
                                        <div class="detail-icon bg-light-warning text-warning rounded-circle me-3">
                                            <i class="bi bi-hospital fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-gray-800">Branch</div>
                                            <div class="text-muted">{{ $patient->branch->name ?? 'Not assigned' }}</div>
                                        </div>
                                    </div>

                                    <div class="detail-item d-flex align-items-center mb-3">
                                        <div class="detail-icon bg-light-info text-info rounded-circle me-3">
                                            <i class="bi bi-shield-check fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-gray-800">Insurance</div>
                                            <div class="text-muted">
                                @if($patient->nhis_number)
                                    <span class="badge bg-success">{{ $patient->nhis_number }}</span>
                                @else
                                                    <span class="badge bg-secondary">No insurance</span>
                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                    </div>
                </div>
            </div>

                    @can('view_patients')
                    <!-- Portal Login Card -->
                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-header bg-light d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-semibold text-gray-800">
                                <i class="bi bi-key me-2"></i>Portal Login
                            </h6>
                            @if($patient->user_id && $patient->user)
                                <span class="badge bg-success">Linked</span>
                            @else
                                <span class="badge bg-secondary">Not Linked</span>
                            @endif
                        </div>
                        <div class="card-body p-4">
                            @if(session('portal_password'))
                            <div class="alert alert-info py-2 mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Temporary password shown below. Copy it now — it will not be shown again after you leave this page.
                            </div>
                            @endif

                            @if($patient->user_id && $patient->user)
                                <div class="mb-3">
                                    <div class="fw-semibold text-gray-800 small">Email / Username</div>
                                    <div class="text-muted">{{ $patient->user->email }}</div>
                                </div>
                                @if($patient->user->phone)
                                <div class="mb-3">
                                    <div class="fw-semibold text-gray-800 small">Phone</div>
                                    <div class="text-muted">{{ $patient->user->phone }}</div>
                                </div>
                                @endif
                                <div class="mb-3">
                                    <div class="fw-semibold text-gray-800 small">Password</div>
                                    <div class="d-flex align-items-center gap-2">
                                        <code id="portal-password-masked" class="bg-light px-2 py-1 rounded">••••••••</code>
                                        @if(session('portal_password'))
                                        <code id="portal-password-plain" class="bg-light px-2 py-1 rounded d-none">{{ session('portal_password') }}</code>
                                        <button type="button" class="btn btn-sm btn-light border" id="toggle-portal-password" title="Show/hide password">
                                            <i class="bi bi-eye" id="toggle-portal-password-icon"></i>
                                        </button>
                                        @else
                                        <span class="text-muted small">Use Reset Password to generate new credentials</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <div class="fw-semibold text-gray-800 small">Account Status</div>
                                    <span class="badge bg-{{ $patient->account_status === 'active' ? 'success' : ($patient->account_status === 'pending' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($patient->account_status ?? 'unknown') }}
                                    </span>
                                </div>
                            @else
                                <p class="text-muted small mb-3">This patient does not have portal login credentials. Staff can generate access below.</p>
                            @endif

                            @can('edit_patients')
                            <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                                @if(!$patient->user_id)
                                <form action="{{ route('patients.portal-access', $patient) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-person-plus me-1"></i> Generate Portal Access
                                    </button>
                                </form>
                                @else
                                <form action="{{ route('patients.portal-reset-password', $patient) }}" method="POST" class="d-inline" onsubmit="return confirm('Generate a new portal password for this patient?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning">
                                        <i class="bi bi-arrow-clockwise me-1"></i> Reset Password
                                    </button>
                                </form>
                                @endif
                            </div>
                            @endcan
                        </div>
                    </div>
                    @endcan

                    <!-- Allergies Alert Card -->
            @if($patient->allergies && $patient->allergies->count() > 0)
                    <div class="card shadow-sm border-danger mt-4">
                        <div class="card-header bg-danger text-white d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Medical Allergies</strong>
                        </div>
                        <div class="card-body p-3">
                    @foreach($patient->allergies as $allergy)
                            <div class="alert alert-danger border-0 mb-2 py-2 d-flex align-items-start">
                                <i class="bi bi-exclamation-circle-fill me-2 mt-1"></i>
                                <div>
                                    <strong class="d-block">{{ $allergy->allergen }}</strong>
                                    <small class="text-muted">{{ $allergy->reaction }} ({{ ucfirst($allergy->severity) }} severity)</small>
                                </div>
                            </div>
                    @endforeach
                </div>
            </div>
            @endif

                    <!-- Quick Stats Card -->
                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-semibold text-gray-800">
                                <i class="bi bi-graph-up me-2"></i>Quick Stats
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3 text-center">
                                <div class="col-6">
                                    <div class="stat-item">
                                        <div class="stat-number text-primary fw-bold fs-3">{{ $patient->visits->count() }}</div>
                                        <div class="stat-label text-muted small">Total Visits</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-item">
                                        <div class="stat-number text-success fw-bold fs-3">{{ $patient->consultations->count() }}</div>
                                        <div class="stat-label text-muted small">Consultations</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
        </div>
        
                <!-- Enhanced Details Tabs -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-0 pt-6">
                            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-transparent fs-4 fw-bold" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#overview">
                                        <i class="bi bi-speedometer2 me-2"></i>Overview
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#appointments">
                                        <i class="bi bi-calendar-check me-2"></i>Appointments
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#consultations">
                                        <i class="bi bi-clipboard2-pulse me-2"></i>Consultations
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#vitals">
                                        <i class="bi bi-heart-pulse me-2"></i>Vital Signs
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#diagnosis">
                                        <i class="bi bi-file-medical me-2"></i>Diagnosis
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#prescriptions">
                                        <i class="bi bi-capsule me-2"></i>Prescriptions
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#lab">
                                        <i class="bi bi-clipboard-data me-2"></i>Lab Results
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#radiology">
                                        <i class="bi bi-radioactive me-2"></i>Radiology
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#billing">
                                        <i class="bi bi-receipt me-2"></i>Billing
                                    </a>
                                </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Enhanced Overview Tab -->
                        <div class="tab-pane fade show active" id="overview">
                            <!-- Statistics Cards -->
                            <div class="row g-4 mb-6">
                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card primary">
                                        <div class="stat-icon">
                                            <i class="bi bi-hospital"></i>
                                        </div>
                                        <div class="stat-label">Total Visits</div>
                                        <div class="stat-value">{{ $patient->visits->count() }}</div>
                                        <div class="small opacity-75 mt-2">
                                            Last: {{ $patient->visits->sortByDesc('created_at')->first()?->created_at->diffForHumans() ?? 'Never' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card success">
                                        <div class="stat-icon">
                                            <i class="bi bi-clipboard2-pulse"></i>
                                        </div>
                                        <div class="stat-label">Consultations</div>
                                        <div class="stat-value">{{ $patient->consultations->count() }}</div>
                                        <div class="small opacity-75 mt-2">
                                            Active: {{ $patient->consultations->where('consultation_status', 'ongoing')->count() }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card info">
                                        <div class="stat-icon">
                                            <i class="bi bi-clipboard-data"></i>
                                        </div>
                                        <div class="stat-label">Lab Tests</div>
                                        <div class="stat-value">{{ $patient->labRequests->count() }}</div>
                                        <div class="small opacity-75 mt-2">
                                            Pending: {{ $patient->labRequests->where('status', 'pending')->count() }}
                                        </div>
                                    </div>
                            </div>

                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card warning">
                                        <div class="stat-icon">
                                            <i class="bi bi-calendar-check"></i>
                                        </div>
                                        <div class="stat-label">Appointments</div>
                                        <div class="stat-value">{{ $patient->appointments->count() }}</div>
                                        <div class="small opacity-75 mt-2">
                                            Upcoming: {{ $patient->appointments->where('status', 'scheduled')->where('appointment_date', '>=', now()->toDateString())->count() }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card info">
                                        <div class="stat-icon">
                                            <i class="bi bi-capsule"></i>
                                        </div>
                                        <div class="stat-label">Prescriptions</div>
                                        <div class="stat-value">{{ $patient->prescriptions->count() }}</div>
                                        <div class="small opacity-75 mt-2">
                                            Active: {{ $patient->prescriptions->where('status', 'active')->count() }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activity Timeline -->
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light border-0">
                                    <h5 class="card-title mb-0 fw-semibold text-gray-800">
                                        <i class="bi bi-clock-history me-2"></i>Recent Activity
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    @if($patient->visits->count() > 0)
                                        <div class="timeline timeline-border-dashed">
                                            @foreach($patient->visits->sortByDesc('created_at')->take(5) as $index => $visit)
                                            <div class="timeline-item">
                                                <div class="timeline-line"></div>
                                                <div class="timeline-icon">
                                                    <i class="bi bi-hospital text-{{ $visit->visit_type === 'Emergency' ? 'danger' : ($visit->visit_type === 'OPD' ? 'primary' : 'success') }} fs-2"></i>
                                                </div>
                                                <div class="timeline-content mb-10">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <div class="fw-bold text-gray-800 mb-2">{{ ucfirst($visit->visit_type) }} Visit</div>
                                                            <div class="text-muted fs-7">
                                                                @if($visit->assignedDoctor)
                                                                    <i class="bi bi-person-badge me-1"></i>Dr. {{ $visit->assignedDoctor->first_name }} {{ $visit->assignedDoctor->last_name }}
                                                                @endif
                                                                @if($visit->chief_complaint)
                                                                    <br><i class="bi bi-chat-square-text me-1"></i>{{ Str::limit(strip_tags($visit->chief_complaint), 50) }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="badge badge-{{ $visit->status === 'completed' ? 'success' : ($visit->status === 'active' ? 'warning' : 'danger') }} fs-8">
                                                                {{ ucfirst($visit->status) }}
                                                            </span>
                                                            <div class="text-muted fs-8 mt-1">{{ $visit->created_at->diffForHumans() }}</div>
                                                        </div>
                                                    </div>
                                </div>
                            </div>
                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <i class="bi bi-inbox fs-3x text-gray-400 mb-3"></i>
                                            <div class="text-muted">No recent activity</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Enhanced Appointments Tab -->
                        <div class="tab-pane fade" id="appointments">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="text-dark mb-0 fw-semibold">
                                    <i class="bi bi-calendar-check me-2"></i>Appointment History & Schedule
                                </h5>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-primary fs-7">{{ $patient->appointments->count() }} total</span>
                                    <span class="badge bg-success fs-7">{{ $patient->appointments->where('status', 'scheduled')->count() }} upcoming</span>
                                </div>
                            </div>
                            
                            @if($patient->appointments && $patient->appointments->count() > 0)
                                <!-- Upcoming Appointments -->
                                @php $upcomingAppointments = $patient->appointments->where('status', 'scheduled')->where('appointment_date', '>=', now()->toDateString())->sortBy('appointment_date'); @endphp
                                @if($upcomingAppointments->count() > 0)
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0 fw-semibold">
                                            <i class="bi bi-calendar-plus me-2"></i>Upcoming Appointments
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date & Time</th>
                                                        <th>Doctor</th>
                                                        <th>Type</th>
                                                        <th>Reason</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($upcomingAppointments as $appointment)
                                                    <tr>
                                                        <td>
                                                            <div class="fw-bold text-gray-800">{{ $appointment->appointment_date->format('M d, Y') }}</div>
                                                            <div class="text-muted fs-8">{{ $appointment->appointment_time->format('h:i A') }}</div>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="symbol symbol-30px me-3">
                                                                    <div class="symbol-label bg-light-primary">
                                                                        <i class="bi bi-person-badge text-primary fs-6"></i>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <div class="fw-semibold text-gray-800 fs-7">Dr. {{ $appointment->doctor->first_name ?? 'N/A' }} {{ $appointment->doctor->last_name ?? '' }}</div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-{{ $appointment->is_teleconsultation ? 'info' : 'primary' }} fs-8">
                                                                {{ $appointment->is_teleconsultation ? 'Teleconsultation' : 'In-Person' }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="text-truncate" style="max-width: 200px;" title="{{ $appointment->reason }}">
                                                                {{ Str::limit($appointment->reason, 50) }}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-{{ $appointment->status === 'scheduled' ? 'success' : ($appointment->status === 'completed' ? 'primary' : 'warning') }} fs-8">
                                                                {{ ucfirst($appointment->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex gap-1">
                                                                <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-sm btn-light-primary" title="View Details">
                                                                    <i class="bi bi-eye fs-7"></i>
                                                                </a>
                                                                @if($appointment->is_teleconsultation && $appointment->teleconsultation)
                                                                <a href="{{ route('teleconsultations.show', $appointment->teleconsultation) }}" class="btn btn-sm btn-light-info" title="Join Teleconsultation">
                                                                    <i class="bi bi-camera-video fs-7"></i>
                                                                </a>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                
                                <!-- Appointment History -->
                                @php $pastAppointments = $patient->appointments->where('appointment_date', '<', now()->toDateString())->sortByDesc('appointment_date'); @endphp
                                @if($pastAppointments->count() > 0)
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 fw-semibold text-gray-800">
                                            <i class="bi bi-clock-history me-2"></i>Appointment History
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date & Time</th>
                                                        <th>Doctor</th>
                                                        <th>Type</th>
                                                        <th>Reason</th>
                                                        <th>Status</th>
                                                        <th>Consultation</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($pastAppointments as $appointment)
                                                    <tr>
                                                        <td>
                                                            <div class="fw-bold text-gray-800">{{ $appointment->appointment_date->format('M d, Y') }}</div>
                                                            <div class="text-muted fs-8">{{ $appointment->appointment_time->format('h:i A') }}</div>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="symbol symbol-30px me-3">
                                                                    <div class="symbol-label bg-light-primary">
                                                                        <i class="bi bi-person-badge text-primary fs-6"></i>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <div class="fw-semibold text-gray-800 fs-7">Dr. {{ $appointment->doctor->first_name ?? 'N/A' }} {{ $appointment->doctor->last_name ?? '' }}</div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-{{ $appointment->is_teleconsultation ? 'info' : 'primary' }} fs-8">
                                                                {{ $appointment->is_teleconsultation ? 'Teleconsultation' : 'In-Person' }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="text-truncate" style="max-width: 200px;" title="{{ $appointment->reason }}">
                                                                {{ Str::limit($appointment->reason, 50) }}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-{{ $appointment->status === 'completed' ? 'success' : ($appointment->status === 'cancelled' ? 'danger' : 'warning') }} fs-8">
                                                                {{ ucfirst($appointment->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @if($appointment->teleconsultation && $appointment->teleconsultation->consultation)
                                                                <a href="{{ route('consultations.show', $appointment->teleconsultation->consultation) }}" class="btn btn-sm btn-light-success" title="View Consultation">
                                                                    <i class="bi bi-clipboard2-pulse fs-7"></i>
                                                                </a>
                                                            @else
                                                                <span class="text-muted fs-8">No consultation</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="d-flex gap-1">
                                                                <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-sm btn-light-primary" title="View Details">
                                                                    <i class="bi bi-eye fs-7"></i>
                                                                </a>
                                                                @if($appointment->is_teleconsultation && $appointment->teleconsultation)
                                                                <a href="{{ route('teleconsultations.show', $appointment->teleconsultation) }}" class="btn btn-sm btn-light-info" title="View Teleconsultation">
                                                                    <i class="bi bi-camera-video fs-7"></i>
                                                                </a>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @else
                                <div class="text-center py-10">
                                    <i class="bi bi-calendar-check fs-3x text-gray-400 mb-3"></i>
                                    <div class="text-muted fs-5">No appointments scheduled</div>
                                    <div class="text-muted fs-7">Appointments will appear here once they are created</div>
                                    <div class="mt-4">
                                        <a href="{{ route('appointments.create', ['patient_id' => $patient->id]) }}" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-2"></i>Schedule Appointment
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Enhanced Consultations Tab -->
                        <div class="tab-pane fade" id="consultations">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="text-dark mb-0 fw-semibold">
                                    <i class="bi bi-clipboard2-pulse me-2"></i>Consultation History
                                </h5>
                                <span class="badge bg-primary fs-7">{{ $patient->consultations->count() }} consultations</span>
                            </div>
                            
                            @if($patient->consultations && $patient->consultations->count() > 0)
                                <div class="timeline timeline-border-dashed">
                                @foreach($patient->consultations->sortByDesc('consultation_date') as $index => $consultation)
                                    <div class="timeline-item">
                                        <div class="timeline-line"></div>
                                        <div class="timeline-icon">
                                            <i class="bi bi-clipboard2-pulse text-{{ $consultation->consultation_status === 'completed' ? 'success' : 'warning' }} fs-2"></i>
                                        </div>
                                        <div class="timeline-content mb-10">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <div class="fw-bold text-gray-800">{{ $consultation->consultation_date->format('M d, Y') }}</div>
                                                        <div class="text-muted fs-7">{{ ucfirst($consultation->consultation_type) }} consultation</div>
                                                    </div>
                                                    <span class="badge badge-{{ $consultation->consultation_status === 'completed' ? 'success' : 'warning' }} fs-8">
                                                        {{ ucfirst($consultation->consultation_status) }}
                                                    </span>
                                    </div>
                                    <div class="card-body">
                                                    <div class="row g-4">
                                            <div class="col-md-6">
                                                            <div class="d-flex align-items-center mb-3">
                                                                <div class="symbol symbol-40px me-3">
                                                                    <div class="symbol-label bg-light-primary">
                                                                        <i class="bi bi-person-badge text-primary fs-5"></i>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <div class="fw-semibold text-gray-800">Doctor</div>
                                                                    <div class="text-muted fs-7">{{ $consultation->doctor->first_name ?? 'N/A' }} {{ $consultation->doctor->last_name ?? '' }}</div>
                                                                </div>
                                                            </div>
                                                            @if($consultation->chief_complaint)
                                                            <div class="d-flex align-items-start">
                                                                <div class="symbol symbol-40px me-3">
                                                                    <div class="symbol-label bg-light-info">
                                                                        <i class="bi bi-chat-square-text text-info fs-5"></i>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <div class="fw-semibold text-gray-800">Chief Complaint</div>
                                                                    <div class="text-muted fs-7 consultation-content">{!! $consultation->chief_complaint !!}</div>
                                                                </div>
                                                            </div>
                                                            @endif
                                            </div>
                                            <div class="col-md-6">
                                                @if($consultation->vitals && $consultation->vitals->count() > 0)
                                                @php $vital = $consultation->vitals->first(); @endphp
                                                            <div class="vitals-card bg-light rounded p-3">
                                                                <h6 class="fw-semibold text-gray-800 mb-3">
                                                                    <i class="bi bi-heart-pulse me-2"></i>Vital Signs
                                                                </h6>
                                                                <div class="row g-2">
                                                                    <div class="col-6">
                                                                        <div class="vital-item">
                                                                            <div class="text-muted fs-8">Blood Pressure</div>
                                                                            <div class="fw-bold text-gray-800">{{ $vital->blood_pressure_systolic }}/{{ $vital->blood_pressure_diastolic }}</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="vital-item">
                                                                            <div class="text-muted fs-8">Pulse Rate</div>
                                                                            <div class="fw-bold text-gray-800">{{ $vital->pulse_rate }} bpm</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="vital-item">
                                                                            <div class="text-muted fs-8">Temperature</div>
                                                                            <div class="fw-bold text-gray-800">{{ $vital->temperature }}°C</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="vital-item">
                                                                            <div class="text-muted fs-8">Weight</div>
                                                                            <div class="fw-bold text-gray-800">{{ $vital->weight ?? 'N/A' }} kg</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    
                                                    @if($consultation->consultationDiagnoses && $consultation->consultationDiagnoses->count() > 0)
                                                    <div class="separator my-4"></div>
                                                    <h6 class="fw-semibold text-gray-800 mb-3">
                                                        <i class="bi bi-file-medical me-2"></i>Diagnoses
                                                    </h6>
                                                    <div class="row g-2">
                                                        @foreach($consultation->consultationDiagnoses as $diagnosis)
                                                        <div class="col-md-6">
                                                            <div class="diagnosis-item bg-light rounded p-3">
                                                                <div class="fw-bold text-gray-800 mb-1">{{ $diagnosis->diagnosis_description }}</div>
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <span class="text-muted fs-8">{{ $diagnosis->icd_code }}</span>
                                                                    <span class="badge badge-{{ $diagnosis->diagnosis_type === 'primary' ? 'danger' : 'info' }} fs-8">
                                                                        {{ ucfirst($diagnosis->diagnosis_type) }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                    @php
                                                        $rxCount = $consultation->prescriptions ? $consultation->prescriptions->count() : 0;
                                                        $labCount = $consultation->labRequests ? $consultation->labRequests->count() : 0;
                                                    @endphp
                                                    @if($rxCount > 0 || $labCount > 0 || $consultation->follow_up_instructions || $consultation->doctors_impression)
                                                    <div class="separator my-4"></div>
                                                    <div class="row g-3">
                                                        @if($consultation->doctors_impression)
                                                        <div class="col-md-6">
                                                            <h6 class="fw-semibold text-gray-800 mb-2"><i class="bi bi-clipboard-check me-2"></i>Impression</h6>
                                                            <div class="text-muted fs-7 consultation-content">{!! Str::limit(strip_tags($consultation->doctors_impression), 200) !!}</div>
                                                        </div>
                                                        @endif
                                                        @if($rxCount > 0)
                                                        <div class="col-md-6">
                                                            <h6 class="fw-semibold text-gray-800 mb-2"><i class="bi bi-capsule me-2"></i>Prescriptions</h6>
                                                            <span class="badge bg-primary">{{ $rxCount }} prescription(s)</span>
                                                        </div>
                                                        @endif
                                                        @if($labCount > 0)
                                                        <div class="col-md-6">
                                                            <h6 class="fw-semibold text-gray-800 mb-2"><i class="bi bi-flask me-2"></i>Lab Orders</h6>
                                                            <span class="badge bg-info">{{ $labCount }} test(s)</span>
                                                        </div>
                                                        @endif
                                                        @if($consultation->follow_up_instructions)
                                                        <div class="col-md-6">
                                                            <h6 class="fw-semibold text-gray-800 mb-2"><i class="bi bi-arrow-repeat me-2"></i>Follow-up</h6>
                                                            <div class="text-muted fs-7 consultation-content">{!! Str::limit(strip_tags($consultation->follow_up_instructions), 150) !!}</div>
                                                        </div>
                                                        @endif
                                                    </div>
                                                    @endif

                                                    <div class="separator my-4"></div>
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        @can('view_consultations')
                                                        <a href="{{ route('consultations.show', $consultation) }}" class="btn btn-sm btn-light-primary">
                                                            <i class="bi bi-eye"></i> View Full Details
                                                        </a>
                                                        @endcan
                                                        @can('edit_consultations')
                                                        @if(!auth()->user()->hasRole('doctor') || $consultation->doctor_id == auth()->id())
                                                        <a href="{{ route('consultations.edit', $consultation) }}" class="btn btn-sm btn-light-warning">
                                                            <i class="bi bi-pencil"></i> {{ $consultation->consultation_status === 'completed' ? 'Amend' : 'Edit' }}
                                                        </a>
                                                        @endif
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-10">
                                    <i class="bi bi-clipboard2-pulse fs-3x text-gray-400 mb-3"></i>
                                    <div class="text-muted fs-5">No consultation records available</div>
                                    <div class="text-muted fs-7">Consultations will appear here once they are created</div>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Vital Signs Tab -->
                        <div class="tab-pane fade" id="vitals">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="text-dark mb-0 fw-semibold">
                                    <i class="bi bi-heart-pulse me-2"></i>Vital Signs History
                                </h5>
                                @php
                                    // Use vitals directly queried from controller (more reliable)
                                    // Fallback to collecting from consultations if not available
                                    if (isset($patient->allVitals) && $patient->allVitals->isNotEmpty()) {
                                        $allVitals = $patient->allVitals;
                                    } else {
                                        // Fallback: collect from consultations relationship
                                        $allVitals = collect();
                                        foreach($patient->consultations as $consultation) {
                                            if ($consultation->vitals && $consultation->vitals->isNotEmpty()) {
                                                $allVitals = $allVitals->merge($consultation->vitals);
                                            }
                                        }
                                        $allVitals = $allVitals->sortByDesc('recorded_at');
                                    }
                                @endphp
                                <span class="badge bg-success fs-7">{{ $allVitals->count() }} records</span>
                            </div>
                            
                            @if($allVitals->count() > 0)
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date & Time</th>
                                                        <th>Blood Pressure</th>
                                                        <th>Pulse</th>
                                                        <th>Temperature</th>
                                                        <th>Respiratory Rate</th>
                                                        <th>O2 Saturation</th>
                                                        <th>Height</th>
                                                        <th>Weight</th>
                                                        <th>BMI</th>
                                                        <th>Recorded By</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($allVitals as $vital)
                                                    <tr>
                                                        <td>
                                                            <div class="fw-bold text-gray-800">{{ $vital->recorded_at ? $vital->recorded_at->format('M d, Y') : 'N/A' }}</div>
                                                            <div class="text-muted fs-8">{{ $vital->recorded_at ? $vital->recorded_at->format('h:i A') : '' }}</div>
                                                        </td>
                                                        <td>
                                                            @if($vital->blood_pressure_systolic && $vital->blood_pressure_diastolic)
                                                                <span class="fw-bold">{{ $vital->blood_pressure_systolic }}/{{ $vital->blood_pressure_diastolic }}</span>
                                                                <div class="text-muted fs-8">mmHg</div>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            {{ $vital->pulse_rate ? $vital->pulse_rate . ' bpm' : '-' }}
                                                        </td>
                                                        <td>
                                                            {{ $vital->temperature ? number_format($vital->temperature, 1) . '°C' : '-' }}
                                                        </td>
                                                        <td>
                                                            {{ $vital->respiratory_rate ? $vital->respiratory_rate . ' bpm' : '-' }}
                                                        </td>
                                                        <td>
                                                            {{ $vital->oxygen_saturation ? $vital->oxygen_saturation . '%' : '-' }}
                                                        </td>
                                                        <td>
                                                            {{ $vital->height ? number_format($vital->height, 1) . ' cm' : '-' }}
                                                        </td>
                                                        <td>
                                                            {{ $vital->weight ? number_format($vital->weight, 1) . ' kg' : '-' }}
                                                        </td>
                                                        <td>
                                                            {{ $vital->bmi ? number_format($vital->bmi, 1) : '-' }}
                                                        </td>
                                                        <td>
                                                            {{ $vital->recordedBy ? $vital->recordedBy->name : 'N/A' }}
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('vitals.show', $vital) }}" class="btn btn-sm btn-light-primary" title="View Details">
                                                                <i class="bi bi-eye fs-7"></i>
                                                            </a>
                                                            @can('record_vitals')
                                                            <a href="{{ route('vitals.edit', $vital) }}" class="btn btn-sm btn-light-warning" title="Edit">
                                                                <i class="bi bi-pencil fs-7"></i>
                                                            </a>
                                                            @endcan
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-10">
                                    <i class="bi bi-heart-pulse fs-3x text-gray-400 mb-3"></i>
                                    <div class="text-muted fs-5">No vital signs recorded</div>
                                    <div class="text-muted fs-7">Vital signs will appear here once they are recorded</div>
                                    <div class="mt-4">
                                        @can('record_vitals')
                                        <a href="{{ route('vitals.create', ['patient_id' => $patient->id]) }}" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-2"></i>Record Vital Signs
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Enhanced Diagnosis Tab -->
                        <div class="tab-pane fade" id="diagnosis">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="text-dark mb-0 fw-semibold">
                                    <i class="bi bi-file-medical me-2"></i>Diagnosis & Treatment History
                                </h5>
                                @php $allDiagnoses = $patient->consultations->flatMap->consultationDiagnoses->sortByDesc('diagnosis_date'); @endphp
                                <span class="badge bg-primary fs-7">{{ $allDiagnoses->count() }} diagnoses</span>
                            </div>
                            
                            @if($allDiagnoses->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-enhanced">
                                        <thead>
                                            <tr>
                                                <th class="min-w-100px">Date</th>
                                                <th class="min-w-200px">Diagnosis</th>
                                                <th class="min-w-100px">ICD Code</th>
                                                <th class="min-w-100px">Type</th>
                                                <th class="min-w-150px">Doctor</th>
                                                <th class="min-w-100px">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($allDiagnoses as $diagnosis)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-gray-800">{{ \Carbon\Carbon::parse($diagnosis->diagnosis_date)->format('M d, Y') }}</div>
                                                    <div class="text-muted fs-8">{{ \Carbon\Carbon::parse($diagnosis->diagnosis_date)->diffForHumans() }}</div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-gray-800 mb-1">{{ $diagnosis->diagnosis_description }}</div>
                                                    @if($diagnosis->confidence_level)
                                                    <div class="text-muted fs-8">Confidence: {{ ucfirst($diagnosis->confidence_level) }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info fs-8">{{ $diagnosis->icd_code }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $diagnosis->diagnosis_type === 'primary' ? 'danger' : ($diagnosis->diagnosis_type === 'secondary' ? 'info' : 'warning') }} fs-8">
                                                        {{ ucfirst($diagnosis->diagnosis_type) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-30px me-3">
                                                            <div class="symbol-label bg-light-primary">
                                                                <i class="bi bi-person-badge text-primary fs-6"></i>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold text-gray-800 fs-7">{{ $diagnosis->consultation->doctor->first_name ?? 'N/A' }} {{ $diagnosis->consultation->doctor->last_name ?? '' }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-success fs-8">Active</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-10">
                                    <i class="bi bi-file-medical fs-3x text-gray-400 mb-3"></i>
                                    <div class="text-muted fs-5">No diagnosis records</div>
                                    <div class="text-muted fs-7">Diagnoses will appear here once consultations are completed</div>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Enhanced Prescriptions Tab -->
                        <div class="tab-pane fade" id="prescriptions">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="text-dark mb-0 fw-semibold">
                                    <i class="bi bi-capsule me-2"></i>Prescription History
                                </h5>
                                <span class="badge bg-primary fs-7">{{ $patient->prescriptions->count() }} prescriptions</span>
                            </div>
                            
                            @if($patient->prescriptions && $patient->prescriptions->count() > 0)
                                <div class="row g-4">
                                    @foreach($patient->prescriptions->sortByDesc('created_at') as $prescription)
                                    <div class="col-lg-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="fw-bold text-gray-800">{{ $prescription->created_at->format('M d, Y') }}</div>
                                                    <div class="text-muted fs-7">{{ $prescription->created_at->diffForHumans() }}</div>
                                                </div>
                                                <span class="badge badge-{{ $prescription->status === 'dispensed' ? 'success' : ($prescription->status === 'active' ? 'warning' : 'danger') }} fs-8">
                                                    {{ ucfirst($prescription->status) }}
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex align-items-start mb-3">
                                                    <div class="symbol symbol-40px me-3">
                                                        <div class="symbol-label bg-light-warning">
                                                            <i class="bi bi-capsule text-warning fs-5"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="fw-bold text-gray-800 mb-1">Medications</div>
                                                        <div class="text-muted fs-7">
                                                            @if($prescription->orders && $prescription->orders->count() > 0)
                                                                @foreach($prescription->orders as $order)
                                                                    <span class="badge badge-light-primary me-1 mb-1">{{ $order->drug->name ?? $order->drug_name ?? 'N/A' }}</span>
                                                                @endforeach
                                                            @else
                                                                <span class="text-muted">No medications specified</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row g-3">
                                                    <div class="col-6">
                                                        <div class="prescription-detail">
                                                            <div class="text-muted fs-8">Dosage</div>
                                                            <div class="fw-semibold text-gray-800 fs-7">{{ $prescription->dosage ?? 'Not specified' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="prescription-detail">
                                                            <div class="text-muted fs-8">Frequency</div>
                                                            <div class="fw-semibold text-gray-800 fs-7">{{ $prescription->frequency ?? 'Not specified' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="prescription-detail">
                                                            <div class="text-muted fs-8">Duration</div>
                                                            <div class="fw-semibold text-gray-800 fs-7">{{ $prescription->duration ?? 'Not specified' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="prescription-detail">
                                                            <div class="text-muted fs-8">Quantity</div>
                                                            <div class="fw-semibold text-gray-800 fs-7">{{ $prescription->quantity ?? 'Not specified' }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                @if($prescription->instructions)
                                                <div class="separator my-3"></div>
                                                <div class="prescription-instructions">
                                                    <div class="text-muted fs-8 mb-1">Instructions</div>
                                                    <div class="text-gray-800 fs-7">{{ $prescription->instructions }}</div>
                                                </div>
                                                @endif
                                                
                                                <!-- Prescription Actions -->
                                                <div class="separator my-3"></div>
                                                <div class="d-flex justify-content-end gap-2">
                                                    <a href="{{ route('pharmacy.prescriptions.show', $prescription->id) }}" class="btn btn-sm btn-light-primary" title="View Prescription Details">
                                                        <i class="bi bi-eye fs-7 me-1"></i>View
                                                    </a>
                                                    @if($prescription->status === 'active')
                                                    <a href="{{ route('pharmacy.prescriptions.dispense', $prescription->id) }}" class="btn btn-sm btn-light-warning" title="Dispense Prescription">
                                                        <i class="bi bi-capsule fs-7 me-1"></i>Dispense
                                                    </a>
                                                    @endif
                                                    @if($prescription->status === 'dispensed')
                                                    <a href="{{ route('pharmacy.prescriptions.show', $prescription->id) }}" class="btn btn-sm btn-light-success" title="View Dispensed Prescription">
                                                        <i class="bi bi-check-circle fs-7 me-1"></i>Dispensed
                                                    </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-10">
                                    <i class="bi bi-capsule fs-3x text-gray-400 mb-3"></i>
                                    <div class="text-muted fs-5">No prescriptions</div>
                                    <div class="text-muted fs-7">Prescriptions will appear here once they are created</div>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Enhanced Lab Tab -->
                        <div class="tab-pane fade" id="lab">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="text-dark mb-0 fw-semibold">
                                    <i class="bi bi-clipboard-data me-2"></i>Laboratory Results
                                </h5>
                                <span class="badge bg-primary fs-7">{{ $patient->labRequests->count() }} tests</span>
                            </div>
                            
                            @if($patient->labRequests && $patient->labRequests->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-enhanced">
                                        <thead>
                                            <tr>
                                                <th class="min-w-100px">Date</th>
                                                <th class="min-w-200px">Test Type</th>
                                                <th class="min-w-100px">Status</th>
                                                <th class="min-w-100px">Results</th>
                                                <th class="min-w-100px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($patient->labRequests->sortByDesc('created_at') as $labRequest)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-gray-800">{{ $labRequest->created_at->format('M d, Y') }}</div>
                                                    <div class="text-muted fs-8">{{ $labRequest->created_at->diffForHumans() }}</div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-30px me-3">
                                                            <div class="symbol-label bg-light-info">
                                                                <i class="bi bi-clipboard-data text-info fs-6"></i>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-gray-800">{{ $labRequest->test_type ?? 'N/A' }}</div>
                                                            @if($labRequest->test_category)
                                                            <div class="text-muted fs-8">{{ $labRequest->test_category }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $labRequest->status === 'completed' ? 'success' : ($labRequest->status === 'pending' ? 'warning' : 'danger') }} fs-8">
                                                        {{ ucfirst($labRequest->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($labRequest->results && $labRequest->results->count() > 0)
                                                        <span class="badge badge-success fs-8">Results available</span>
                                                        <div class="text-muted fs-8 mt-1">
                                                            @foreach($labRequest->results->take(2) as $result)
                                                                <div>{{ $result->parameter_name }}: {{ $result->formatted_value ?? $result->result_value }}</div>
                                                            @endforeach
                                                            @if($labRequest->results->count() > 2)
                                                                <div>+{{ $labRequest->results->count() - 2 }} more</div>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="badge bg-secondary fs-8">Results pending</span>
                                                    @endif
                                                    <div class="text-muted fs-8 mt-1">Updated {{ $labRequest->updated_at->diffForHumans() }}</div>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        @can('view_lab_results')
                                                        <a href="{{ route('lab.show', $labRequest->id) }}" class="btn btn-sm btn-light-primary" title="View Lab Results">
                                                            <i class="bi bi-eye fs-7"></i>
                                                        </a>
                                                        @endcan
                                                        @if($labRequest->results && $labRequest->results->count() > 0)
                                                        @can('print_lab_results')
                                                        <a href="{{ route('lab.generate-pdf', $labRequest->id) }}" class="btn btn-sm btn-light-success" title="Download PDF Results" target="_blank">
                                                            <i class="bi bi-download fs-7"></i>
                                                        </a>
                                                        @endcan
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
                                    <i class="bi bi-clipboard-data fs-3x text-gray-400 mb-3"></i>
                                    <div class="text-muted fs-5">No lab tests</div>
                                    <div class="text-muted fs-7">Lab test results will appear here once tests are completed</div>
                                </div>
                            @endif
                        </div>

                        <!-- Radiology Tab -->
                        <div class="tab-pane fade" id="radiology">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="text-dark mb-0 fw-semibold">
                                    <i class="bi bi-radioactive me-2"></i>Radiology History
                                </h5>
                                <span class="badge bg-primary fs-7">{{ $patient->radiologyRequests->count() }} requests</span>
                            </div>

                            @if($patient->radiologyRequests && $patient->radiologyRequests->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-enhanced">
                                        <thead>
                                            <tr>
                                                <th class="min-w-100px">Date</th>
                                                <th class="min-w-150px">Request #</th>
                                                <th class="min-w-150px">Modality</th>
                                                <th class="min-w-100px">Priority</th>
                                                <th class="min-w-100px">Status</th>
                                                <th class="min-w-100px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($patient->radiologyRequests->sortByDesc('created_at') as $radiologyRequest)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-gray-800">{{ $radiologyRequest->requested_date?->format('M d, Y') ?? $radiologyRequest->created_at->format('M d, Y') }}</div>
                                                    <div class="text-muted fs-8">{{ $radiologyRequest->created_at->diffForHumans() }}</div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">{{ $radiologyRequest->request_number }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-30px me-3">
                                                            <div class="symbol-label bg-light-info">
                                                                <i class="bi bi-radioactive text-info fs-6"></i>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-gray-800">{{ $radiologyRequest->modality?->name ?? 'N/A' }}</div>
                                                            @if($radiologyRequest->indication)
                                                            <div class="text-muted fs-8">{{ Str::limit($radiologyRequest->indication, 40) }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @php
                                                        $priorityClasses = [
                                                            'routine' => 'secondary',
                                                            'urgent' => 'warning',
                                                            'stat' => 'danger',
                                                            'emergency' => 'dark',
                                                        ];
                                                    @endphp
                                                    <span class="badge badge-{{ $priorityClasses[$radiologyRequest->priority] ?? 'secondary' }} fs-8">
                                                        {{ ucfirst($radiologyRequest->priority) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @php
                                                        $statusClasses = [
                                                            'requested' => 'warning',
                                                            'scheduled' => 'info',
                                                            'in_progress' => 'primary',
                                                            'completed' => 'success',
                                                            'cancelled' => 'danger',
                                                            'rejected' => 'secondary',
                                                        ];
                                                    @endphp
                                                    <span class="badge badge-{{ $statusClasses[$radiologyRequest->status] ?? 'secondary' }} fs-8">
                                                        {{ ucfirst(str_replace('_', ' ', $radiologyRequest->status)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @can('view_radiology_requests')
                                                    <a href="{{ route('radiology.show', $radiologyRequest) }}" class="btn btn-sm btn-light-primary" title="View Radiology Request">
                                                        <i class="bi bi-eye fs-7"></i>
                                                    </a>
                                                    @else
                                                    <span class="text-muted fs-8">—</span>
                                                    @endcan
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-10">
                                    <i class="bi bi-radioactive fs-3x text-gray-400 mb-3"></i>
                                    <div class="text-muted fs-5">No radiology requests</div>
                                    <div class="text-muted fs-7">Radiology imaging requests will appear here once they are created</div>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Enhanced Billing Tab -->
                        <div class="tab-pane fade" id="billing">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="text-dark mb-0 fw-semibold">
                                    <i class="bi bi-receipt me-2"></i>Billing History
                                </h5>
                                <span class="badge bg-primary fs-7">{{ $patient->invoices->count() }} invoices</span>
                            </div>
                            
                            @if($patient->invoices && $patient->invoices->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-enhanced">
                                        <thead>
                                            <tr>
                                                <th class="min-w-120px">Invoice #</th>
                                                <th class="min-w-100px">Date</th>
                                                <th class="min-w-100px">Amount</th>
                                                <th class="min-w-100px">Paid</th>
                                                <th class="min-w-100px">Balance</th>
                                                <th class="min-w-100px">Status</th>
                                                <th class="min-w-100px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($patient->invoices->sortByDesc('created_at') as $invoice)
                                            @php
                                                $totalPaid = $invoice->payments->sum('amount');
                                                $balance = $invoice->total_amount - $totalPaid;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-gray-800">{{ $invoice->invoice_number ?? 'N/A' }}</div>
                                                    @if($invoice->visit)
                                                    <div class="text-muted fs-8">{{ $invoice->visit->visit_type }} visit</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-gray-800">{{ $invoice->created_at->format('M d, Y') }}</div>
                                                    <div class="text-muted fs-8">{{ $invoice->created_at->diffForHumans() }}</div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-gray-800">GH₵ {{ number_format($invoice->total_amount, 2) }}</div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-success">GH₵ {{ number_format($totalPaid, 2) }}</div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-{{ $balance > 0 ? 'danger' : 'success' }}">
                                                        GH₵ {{ number_format($balance, 2) }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($balance > 0 ? 'danger' : 'warning') }} fs-8">
                                                        {{ ucfirst($invoice->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="{{ route('billing.show', $invoice->id) }}" class="btn btn-sm btn-light-primary" title="View Invoice">
                                                            <i class="bi bi-eye fs-7"></i>
                                                        </a>
                                                        <a href="{{ route('billing.download', $invoice->id) }}" class="btn btn-sm btn-light-success" title="Download Invoice" target="_blank">
                                                            <i class="bi bi-download fs-7"></i>
                                                        </a>
                                                        @if($balance > 0)
                                                        <a href="{{ route('billing.edit', $invoice->id) }}" class="btn btn-sm btn-light-warning" title="Make Payment">
                                                            <i class="bi bi-credit-card fs-7"></i>
                                                        </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Billing Summary -->
                                <div class="row g-4 mt-4">
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body text-center">
                                                <div class="text-muted fs-7 mb-1">Total Invoices</div>
                                                <div class="fw-bold text-gray-800 fs-3">{{ $patient->invoices->count() }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body text-center">
                                                <div class="text-muted fs-7 mb-1">Total Amount</div>
                                                <div class="fw-bold text-gray-800 fs-3">GH₵ {{ number_format($patient->invoices->sum('total_amount'), 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body text-center">
                                                <div class="text-muted fs-7 mb-1">Outstanding Balance</div>
                                                <div class="fw-bold text-danger fs-3">
                                                    GH₵ {{ number_format($patient->invoices->sum('total_amount') - $patient->invoices->sum(function($invoice) { return $invoice->payments->sum('amount'); }), 2) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-10">
                                    <i class="bi bi-receipt fs-3x text-gray-400 mb-3"></i>
                                    <div class="text-muted fs-5">No billing records</div>
                                    <div class="text-muted fs-7">Billing records will appear here once invoices are created</div>
                                </div>
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
.prescription-detail {
    text-align: center;
    padding: 0.5rem;
}

.prescription-instructions {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 1rem;
    border-left: 4px solid var(--primary);
}

.symbol-30px {
    width: 30px;
    height: 30px;
}

.badge-light-primary {
    background-color: rgba(30, 58, 95, 0.1) !important;
    color: var(--primary) !important;
}

.badge-light-info {
    background-color: rgba(52, 152, 219, 0.1) !important;
    color: #17a2b8 !important;
}

.btn-light-primary {
    background-color: rgba(30, 58, 95, 0.1) !important;
    color: var(--primary) !important;
    border: 1px solid rgba(30, 58, 95, 0.2) !important;
}

.btn-light-success {
    background-color: rgba(39, 174, 96, 0.1) !important;
    color: #28a745 !important;
    border: 1px solid rgba(39, 174, 96, 0.2) !important;
}

.btn-light-warning {
    background-color: rgba(243, 156, 18, 0.1) !important;
    color: #ffc107 !important;
    border: 1px solid rgba(243, 156, 18, 0.2) !important;
}

/* Consultation Content Styling */
.consultation-content {
    line-height: 1.6;
    background: transparent !important;
    color: #6c757d !important;
    font-size: 13px;
}

/* Override any background colors from HTML content */
.consultation-content * {
    background: transparent !important;
    background-color: transparent !important;
    color: inherit !important;
    max-width: 100%;
}

.consultation-content h1,
.consultation-content h2,
.consultation-content h3,
.consultation-content h4,
.consultation-content h5,
.consultation-content h6 {
    font-size: 14px;
    font-weight: 600;
    color: #495057 !important;
    margin: 0.5rem 0;
    line-height: 1.4;
    background: transparent !important;
}

.consultation-content p {
    margin: 0.5rem 0;
    font-size: 13px;
    line-height: 1.5;
    color: #6c757d !important;
    background: transparent !important;
}

.consultation-content ul,
.consultation-content ol {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
    background: transparent !important;
}

.consultation-content li {
    margin: 0.25rem 0;
    font-size: 13px;
    line-height: 1.4;
    color: #6c757d !important;
    background: transparent !important;
}

.consultation-content strong {
    font-weight: 600;
    color: #495057 !important;
    background: transparent !important;
}

.consultation-content em {
    font-style: italic;
    color: #6c757d !important;
    background: transparent !important;
}

.consultation-content br {
    line-height: 1.5;
}

/* Ensure consultation content doesn't break layout */
.consultation-content {
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 100%;
}

/* Remove any unwanted margins/padding from HTML elements */
.consultation-content h1,
.consultation-content h2,
.consultation-content h3,
.consultation-content h4,
.consultation-content h5,
.consultation-content h6 {
    margin-top: 0;
    margin-bottom: 0.5rem;
}

.consultation-content p:first-child {
    margin-top: 0;
}

.consultation-content p:last-child {
    margin-bottom: 0;
}

/* Override any div or container backgrounds */
.consultation-content div {
    background: transparent !important;
    background-color: transparent !important;
}

/* Override any span backgrounds */
.consultation-content span {
    background: transparent !important;
    background-color: transparent !important;
}

/* Patient Profile Header Alignment */
.page-heading {
    align-items: center !important;
}

.page-heading i {
    font-size: 1.5rem !important;
    margin-right: 0.75rem !important;
}

.page-heading span {
    line-height: 1.2;
}

/* Action Buttons Alignment */
.ms-auto {
    margin-left: auto !important;
}

.btn.d-flex {
    align-items: center;
    justify-content: center;
    min-height: 36px;
    padding: 8px 16px;
}

.btn i {
    font-size: 14px;
    line-height: 1;
}

/* Responsive Header */
@media (max-width: 768px) {
    .page-heading {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .page-heading i {
        margin-right: 0 !important;
        margin-bottom: 0.5rem;
    }
    
    .ms-auto {
        margin-left: 0 !important;
        margin-top: 1rem;
        width: 100%;
    }
    
    .ms-auto .d-flex {
        flex-direction: column;
        width: 100%;
    }
    
    .ms-auto .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggle-portal-password');
    const masked = document.getElementById('portal-password-masked');
    const plain = document.getElementById('portal-password-plain');
    const icon = document.getElementById('toggle-portal-password-icon');

    if (!toggleBtn || !masked || !plain) {
        return;
    }

    toggleBtn.addEventListener('click', function () {
        const showing = !plain.classList.contains('d-none');
        if (showing) {
            plain.classList.add('d-none');
            masked.classList.remove('d-none');
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        } else {
            plain.classList.remove('d-none');
            masked.classList.add('d-none');
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    });
});
</script>
@endpush
