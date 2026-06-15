@extends('layouts.app')

@section('title', 'Visit Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1" style="color: #1e3a5f;">Visit Details</h1><p class="text-secondary mb-0">{{ $visit->visit_token }}</p></div>
        <div>
            <a href="{{ route('visits.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            @if($visit->status === 'active')
            <form action="{{ route('visits.update', $visit) }}" method="POST" class="d-inline">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="completed">
                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Complete Visit</button>
            </form>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @php
        $paymentBlocked = ($paymentSummary['payment_required'] ?? false) && !($paymentSummary['can_proceed'] ?? true);
        $amountDue = $paymentSummary['amount_due'] ?? 0;
        $cashierUrl = $paymentSummary['cashier_url'] ?? route('cashier.index', ['patient_id' => $visit->patient_id]);
    @endphp

    @if($paymentBlocked)
    <div class="alert alert-warning border-warning shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-cash-coin fs-3 text-warning"></i>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">Payment Required Before Service</h5>
                <p class="mb-2">
                    {{ $paymentSummary['policy']['policy_message'] ?? 'Full payment is required before service can proceed.' }}
                    <strong class="text-danger">Amount due: GH₵{{ number_format($amountDue, 2) }}</strong>
                </p>
                @if(!empty($chargeBreakdown))
                <div class="mb-3">
                    <small class="text-muted d-block mb-1"><strong>Pending charges breakdown:</strong></small>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($chargeBreakdown as $group)
                        <span class="badge bg-light text-dark border">
                            {{ $group['label'] }}: {{ $group['count'] }} &middot; GH₵{{ number_format($group['amount'], 2) }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @can('process_payments')
                    <a href="{{ $cashierUrl }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-cash-coin"></i> Receive Payment
                    </a>
                    @else
                    <span class="text-dark">
                        <i class="bi bi-arrow-right-circle me-1"></i>
                        Please proceed to the cashier to make payment before service can begin.
                    </span>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><h6 class="mb-0">Visit Information</h6></div>
                <div class="card-body">
                    <p class="mb-2"><strong>Visit Token:</strong><br><span class="badge bg-primary">{{ $visit->visit_token }}</span></p>
                    <p class="mb-2"><strong>Type:</strong><br><span class="badge bg-info">{{ strtoupper($visit->visit_type) }}</span></p>
                    <p class="mb-2"><strong>Status:</strong><br><span class="badge bg-{{ $visit->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($visit->status) }}</span></p>
                    <p class="mb-2"><strong>Check-In:</strong><br>{{ $visit->check_in_time->format('M d, Y h:i A') }}</p>
                    @if($visit->check_out_time)
                    <p class="mb-0"><strong>Check-Out:</strong><br>{{ $visit->check_out_time->format('M d, Y h:i A') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white"><h6 class="mb-0">Patient</h6></div>
                <div class="card-body">
                    @if($visit->patient)
                        <h6>{{ $visit->patient->full_name }}</h6>
                        <p class="mb-1 small"><strong>ID:</strong> {{ $visit->patient->patient_number }}</p>
                        <p class="mb-1 small"><strong>Age:</strong> {{ $visit->patient->age }} years</p>
                        <p class="mb-0 small"><strong>Gender:</strong> {{ $visit->patient->gender }}</p>
                        <a href="{{ route('patients.show', $visit->patient) }}" class="btn btn-sm btn-outline-success w-100 mt-3"><i class="bi bi-eye"></i> View Profile</a>
                    @else
                        <h6 class="text-danger">Patient Not Found</h6>
                        <p class="mb-1 small text-muted">Patient ID: {{ $visit->patient_id }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white"><h6 class="mb-0">Staff Assigned</h6></div>
                <div class="card-body">
                    <p class="mb-2"><strong>Doctor:</strong><br>{{ $visit->assignedDoctor ? 'Dr. ' . $visit->assignedDoctor->first_name . ' ' . $visit->assignedDoctor->last_name : 'Not assigned' }}</p>
                    <p class="mb-0"><strong>Nurse:</strong><br>{{ $visit->assignedNurse ? $visit->assignedNurse->first_name . ' ' . $visit->assignedNurse->last_name : 'Not assigned' }}</p>
                </div>
            </div>
        </div>
    </div>

    @if($visit->chief_complaint)
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light"><h6 class="mb-0">Chief Complaint</h6></div>
        <div class="card-body"><p class="mb-0">{{ $visit->chief_complaint }}</p></div>
    </div>
    @endif

    <!-- Vital Signs Section -->
    @if($visit->consultation && $visit->consultation->vitals && $visit->consultation->vitals->count() > 0)
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Vital Signs Recorded</h6>
        </div>
        <div class="card-body">
            @foreach($visit->consultation->vitals as $vital)
            <div class="vital-record mb-4 {{ !$loop->last ? 'border-bottom pb-4' : '' }}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 text-primary">
                        <i class="bi bi-clipboard-pulse me-2"></i>Vital Signs Record #{{ $loop->iteration }}
                    </h6>
                    <div class="text-muted small">
                        <i class="bi bi-clock me-1"></i>
                        Recorded: {{ $vital->recorded_at ? $vital->recorded_at->format('M d, Y h:i A') : 'N/A' }}
                        @if($vital->recordedBy)
                            by {{ $vital->recordedBy->name }}
                        @endif
                    </div>
                </div>
                
                <div class="row g-3">
                    @if($vital->blood_pressure_systolic && $vital->blood_pressure_diastolic)
                    <div class="col-md-3 col-sm-6">
                        <div class="vital-item bg-light rounded p-3">
                            <div class="text-muted small mb-1">Blood Pressure</div>
                            <div class="fw-bold text-dark fs-5">{{ $vital->blood_pressure_systolic }}/{{ $vital->blood_pressure_diastolic }}</div>
                            <div class="text-muted small">mmHg</div>
                        </div>
                    </div>
                    @endif
                    
                    @if($vital->pulse_rate)
                    <div class="col-md-3 col-sm-6">
                        <div class="vital-item bg-light rounded p-3">
                            <div class="text-muted small mb-1">Pulse Rate</div>
                            <div class="fw-bold text-dark fs-5">{{ $vital->pulse_rate }}</div>
                            <div class="text-muted small">bpm</div>
                        </div>
                    </div>
                    @endif
                    
                    @if($vital->temperature)
                    <div class="col-md-3 col-sm-6">
                        <div class="vital-item bg-light rounded p-3">
                            <div class="text-muted small mb-1">Temperature</div>
                            <div class="fw-bold text-dark fs-5">{{ number_format($vital->temperature, 1) }}</div>
                            <div class="text-muted small">°C</div>
                        </div>
                    </div>
                    @endif
                    
                    @if($vital->respiratory_rate)
                    <div class="col-md-3 col-sm-6">
                        <div class="vital-item bg-light rounded p-3">
                            <div class="text-muted small mb-1">Respiratory Rate</div>
                            <div class="fw-bold text-dark fs-5">{{ $vital->respiratory_rate }}</div>
                            <div class="text-muted small">breaths/min</div>
                        </div>
                    </div>
                    @endif
                    
                    @if($vital->oxygen_saturation)
                    <div class="col-md-3 col-sm-6">
                        <div class="vital-item bg-light rounded p-3">
                            <div class="text-muted small mb-1">Oxygen Saturation</div>
                            <div class="fw-bold text-dark fs-5">{{ $vital->oxygen_saturation }}</div>
                            <div class="text-muted small">%</div>
                        </div>
                    </div>
                    @endif
                    
                    @if($vital->height)
                    <div class="col-md-3 col-sm-6">
                        <div class="vital-item bg-light rounded p-3">
                            <div class="text-muted small mb-1">Height</div>
                            <div class="fw-bold text-dark fs-5">{{ number_format($vital->height, 1) }}</div>
                            <div class="text-muted small">cm</div>
                        </div>
                    </div>
                    @endif
                    
                    @if($vital->weight)
                    <div class="col-md-3 col-sm-6">
                        <div class="vital-item bg-light rounded p-3">
                            <div class="text-muted small mb-1">Weight</div>
                            <div class="fw-bold text-dark fs-5">{{ number_format($vital->weight, 1) }}</div>
                            <div class="text-muted small">kg</div>
                        </div>
                    </div>
                    @endif
                    
                    @if($vital->bmi)
                    <div class="col-md-3 col-sm-6">
                        <div class="vital-item bg-light rounded p-3">
                            <div class="text-muted small mb-1">BMI</div>
                            <div class="fw-bold text-dark fs-5">{{ number_format($vital->bmi, 1) }}</div>
                            <div class="text-muted small">kg/m²</div>
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="mt-3">
                    <a href="{{ route('vitals.show', $vital) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>View Full Details
                    </a>
                    @can('record_vitals')
                    <a href="{{ route('vitals.edit', $vital) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                    @endcan
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-warning text-dark">
            <h6 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Vital Signs</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle me-2"></i>
                No vital signs have been recorded for this visit yet.
                @can('record_vitals')
                <a href="{{ route('vitals.create', ['patient_id' => $visit->patient_id, 'visit_id' => $visit->id]) }}" class="btn btn-sm btn-success ms-2">
                    <i class="bi bi-plus-circle me-1"></i>Record Vital Signs
                </a>
                @endcan
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
