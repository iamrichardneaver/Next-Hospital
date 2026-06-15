@extends('layouts.app')

@section('title', 'Radiology Request Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Radiology Request Details</h1>
                <p class="page-subtitle">Request #{{ $radiology->request_number }}</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('radiology.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Requests
                </a>
                @can('edit_radiology_requests')
                <a href="{{ route('radiology.edit', $radiology) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Request Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Request Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Request Number</label>
                                <p class="form-control-plaintext">{{ $radiology->request_number }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <p>
                                    @php
                                        $statusClasses = [
                                            'pending' => 'bg-warning',
                                            'scheduled' => 'bg-info',
                                            'in_progress' => 'bg-primary',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger'
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusClasses[$radiology->status] ?? 'bg-secondary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $radiology->status)) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Priority</label>
                                <p>
                                    @php
                                        $priorityClasses = [
                                            'routine' => 'bg-secondary',
                                            'urgent' => 'bg-warning',
                                            'stat' => 'bg-danger',
                                            'emergency' => 'bg-dark'
                                        ];
                                    @endphp
                                    <span class="badge {{ $priorityClasses[$radiology->priority] ?? 'bg-secondary' }}">
                                        {{ ucfirst($radiology->priority) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Requested Date</label>
                                <p class="form-control-plaintext">{{ $radiology->requested_date->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                    @if($radiology->scheduled_date)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Scheduled Date</label>
                                <p class="form-control-plaintext">{{ $radiology->scheduled_date->format('M d, Y') }}</p>
                            </div>
                        </div>
                        @if($radiology->scheduled_time)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Scheduled Time</label>
                                <p class="form-control-plaintext">{{ $radiology->scheduled_time->format('H:i') }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Patient Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Patient Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Patient Name</label>
                                <p class="form-control-plaintext">{{ $radiology->patient->full_name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Patient Number</label>
                                <p class="form-control-plaintext">{{ $radiology->patient->patient_number }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Date of Birth</label>
                                <p class="form-control-plaintext">{{ $radiology->patient->date_of_birth ? $radiology->patient->date_of_birth->format('M d, Y') : 'Not provided' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Gender</label>
                                <p class="form-control-plaintext">{{ $radiology->patient->gender }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clinical Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Clinical Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Clinical History</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $radiology->clinical_history !!}
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Clinical Question</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $radiology->clinical_question !!}
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Indication</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $radiology->indication !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Study Information -->
            @if($radiology->study)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Study Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Study Status</label>
                                <p>
                                    @php
                                        $studyStatusClasses = [
                                            'pending' => 'bg-warning',
                                            'in_progress' => 'bg-primary',
                                            'completed' => 'bg-success',
                                            'needs_review' => 'bg-info'
                                        ];
                                    @endphp
                                    <span class="badge {{ $studyStatusClasses[$radiology->study->status] ?? 'bg-secondary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $radiology->study->status)) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Study Date</label>
                                <p class="form-control-plaintext">{{ $radiology->study->study_date->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                    @if($radiology->study->completed_date)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Completed Date</label>
                                <p class="form-control-plaintext">{{ $radiology->study->completed_date->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-bold">Study Description</label>
                        <div class="border rounded p-3 bg-light">
                            {{ $radiology->study->study_description }}
                        </div>
                    </div>
                    @if($radiology->study->study_notes)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Study Notes</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $radiology->study->study_notes !!}
                        </div>
                    </div>
                    @endif
                    @if($radiology->study->technique_notes)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Technique Notes</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $radiology->study->technique_notes !!}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Report Information -->
            @if($radiology->study && $radiology->study->report)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Report Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Report Status</label>
                                <p>
                                    @php
                                        $reportStatusClasses = [
                                            'draft' => 'bg-secondary',
                                            'preliminary' => 'bg-warning',
                                            'final' => 'bg-success',
                                            'amended' => 'bg-info',
                                            'cancelled' => 'bg-danger'
                                        ];
                                    @endphp
                                    <span class="badge {{ $reportStatusClasses[$radiology->study->report->status] ?? 'bg-secondary' }}">
                                        {{ ucfirst($radiology->study->report->status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Radiologist</label>
                                <p class="form-control-plaintext">{{ $radiology->study->report->radiologist->full_name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Findings</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $radiology->study->report->findings !!}
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Impression</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $radiology->study->report->impression !!}
                        </div>
                    </div>
                    @if($radiology->study->report->recommendations)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Recommendations</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $radiology->study->report->recommendations !!}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    @if($radiology->status === 'requested' || $radiology->status === 'scheduled')
                        <a href="#" class="btn btn-primary w-100 mb-2" onclick="startStudy({{ $radiology->id }})">
                            <i class="bi bi-play-circle"></i> Start Study
                        </a>
                    @endif
                    
                    @if($radiology->study && $radiology->study->status === 'completed' && !$radiology->study->hasReport())
                        <a href="{{ route('radiology.reports.create', $radiology->study) }}" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-file-text"></i> Create Report
                        </a>
                    @endif
                    
                    @if($radiology->study && $radiology->study->hasReport())
                        <a href="{{ route('radiology.reports.show', $radiology->study->report) }}" class="btn btn-info w-100 mb-2">
                            <i class="bi bi-eye"></i> View Report
                        </a>
                        <a href="{{ route('radiology.reports.pdf', $radiology->study->report) }}" class="btn btn-outline-primary w-100 mb-2" target="_blank">
                            <i class="bi bi-file-pdf"></i> Download PDF
                        </a>
                    @endif
                    
                    @if($radiology->study)
                        <a href="{{ route('radiology.studies.pdf', $radiology->study) }}" class="btn btn-outline-secondary w-100 mb-2" target="_blank">
                            <i class="bi bi-file-pdf"></i> Study Summary PDF
                        </a>
                    @endif
                </div>
            </div>

            <!-- Request Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Request Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Requesting Doctor</label>
                        <p class="form-control-plaintext">{{ $radiology->doctor->full_name }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Imaging Modality</label>
                        <p class="form-control-plaintext">{{ $radiology->modality->name }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Department</label>
                        <p class="form-control-plaintext">{{ $radiology->department->name }}</p>
                    </div>
                    @if($radiology->technician)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Technician</label>
                        <p class="form-control-plaintext">{{ $radiology->technician->name }}</p>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-bold">Radiologist</label>
                        @if($radiology->radiologist)
                            <p class="form-control-plaintext">{{ $radiology->radiologist->full_name }}</p>
                        @else
                            <p class="form-control-plaintext text-muted">
                                <i>Not assigned</i>
                                @if(auth()->user()->hasRole('radiologist') && ($radiology->status === 'requested' || $radiology->status === 'scheduled'))
                                    <br><small class="text-info">You can assign yourself when starting the study</small>
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Study Details -->
            @if($radiology->study)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Study Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Study UID</label>
                        <p class="form-control-plaintext small">{{ $radiology->study->study_uid }}</p>
                    </div>
                    @if($radiology->study->equipment)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Equipment</label>
                        <p class="form-control-plaintext">{{ $radiology->study->equipment->name }}</p>
                    </div>
                    @endif
                    @if($radiology->study->technician)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Technician</label>
                        <p class="form-control-plaintext">{{ $radiology->study->technician->name }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Start Study Modal -->
<div class="modal fade" id="startStudyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start Radiology Study</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="startStudyForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="equipment_id" class="form-label">Equipment (Optional)</label>
                                <select class="form-select" id="equipment_id" name="equipment_id">
                                    <option value="">Select Equipment (Optional)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="study_description" class="form-label">Study Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="study_description" name="study_description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="study_notes" class="form-label">Study Notes</label>
                        <textarea class="form-control" id="study_notes" name="study_notes" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="technique_notes" class="form-label">Technique Notes</label>
                        <textarea class="form-control" id="technique_notes" name="technique_notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Start Study</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function startStudy(requestId) {
    // Load equipment (optional)
    // Use dynamic API endpoint (relative URL works in all environments)
    const equipmentUrl = window.appConfig ? window.appConfig.api('radiology/equipment/available') : '/api/radiology/equipment/available';
    fetch(equipmentUrl, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'Accept': 'application/json'
        }
    }).then(response => {
        if (!response.ok) {
            console.log('Equipment API not available, using empty equipment list');
            return { success: true, data: [] };
        }
        return response.json();
    }).then(equipmentData => {
        console.log('Equipment data:', equipmentData);
        
        // Populate equipment
        const equipmentSelect = document.getElementById('equipment_id');
        equipmentSelect.innerHTML = '<option value="">Select Equipment (Optional)</option>';
        if (equipmentData.success && equipmentData.data) {
            equipmentData.data.forEach(equipment => {
                const option = document.createElement('option');
                option.value = equipment.id;
                option.textContent = equipment.name;
                equipmentSelect.appendChild(option);
            });
        }
    }).catch(error => {
        console.log('Equipment loading failed, continuing without equipment:', error);
    });

    // Set form action
    document.getElementById('startStudyForm').action = "{{ route('radiology.start-study', ':requestId') }}".replace(':requestId', requestId);
    
    // Show modal
    new bootstrap.Modal(document.getElementById('startStudyModal')).show();
}
</script>
@endpush
