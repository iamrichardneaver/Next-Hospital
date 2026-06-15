@extends('layouts.app')

@section('title', 'Radiology Study Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Radiology Study Details</h1>
                <p class="page-subtitle">Study #{{ $study->id }}</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('radiology.studies') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Studies
                </a>
                <a href="{{ route('radiology.studies.pdf', $study) }}" class="btn btn-success" target="_blank">
                    <i class="bi bi-file-pdf"></i> Download PDF
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Study Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Study Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Study ID</label>
                                <p class="form-control-plaintext">#{{ $study->id }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <p>
                                    @php
                                        $statusClasses = [
                                            'pending' => 'bg-warning',
                                            'in_progress' => 'bg-primary',
                                            'completed' => 'bg-success',
                                            'needs_review' => 'bg-info'
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusClasses[$study->status] ?? 'bg-secondary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $study->status)) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Study UID</label>
                                <p class="form-control-plaintext small">{{ $study->study_uid }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Study Date</label>
                                <p class="form-control-plaintext">{{ $study->study_date->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                    @if($study->completed_date)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Completed Date</label>
                                <p class="form-control-plaintext">{{ $study->completed_date->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-bold">Study Description</label>
                        <p class="form-control-plaintext">{{ $study->study_description }}</p>
                    </div>
                    @if($study->study_notes)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Study Notes</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $study->study_notes !!}
                        </div>
                    </div>
                    @endif
                    @if($study->technique_notes)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Technique Notes</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $study->technique_notes !!}
                        </div>
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
                    @php
                        $patient = null;
                        if($study->request && $study->request->patient) {
                            $patient = $study->request->patient;
                        } elseif($study->patient) {
                            $patient = $study->patient;
                        }
                    @endphp
                    
                    @if($patient)
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Patient Name</label>
                                    <p class="form-control-plaintext">{{ $patient->first_name }} {{ $patient->last_name }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Patient Number</label>
                                    <p class="form-control-plaintext">{{ $patient->patient_number }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Date of Birth</label>
                                    <p class="form-control-plaintext">{{ $patient->date_of_birth ? $patient->date_of_birth->format('M d, Y') : 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Gender</label>
                                    <p class="form-control-plaintext">{{ $patient->gender }}</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="text-muted">No patient information available</p>
                    @endif
                </div>
            </div>

            <!-- Clinical Information -->
            @if($study->request)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Clinical Information</h5>
                </div>
                <div class="card-body">
                    @if($study->request->clinical_history)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Clinical History</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $study->request->clinical_history !!}
                        </div>
                    </div>
                    @endif
                    @if($study->request->clinical_question)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Clinical Question</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $study->request->clinical_question !!}
                        </div>
                    </div>
                    @endif
                    @if(!$study->request->clinical_history && !$study->request->clinical_question)
                        <p class="text-muted">No clinical information available</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Upload Images Section -->
            @if($study->status === 'in_progress')
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-cloud-upload"></i> Upload Images</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('radiology.studies.upload-images', $study) }}" method="POST" enctype="multipart/form-data" id="uploadImagesForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="series_description" class="form-label">Series Description <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="series_description" name="series_description" required placeholder="e.g., Chest X-Ray PA, Abdominal CT with Contrast">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="body_part_examined" class="form-label">Body Part Examined</label>
                                    <input type="text" class="form-control" id="body_part_examined" name="body_part_examined" placeholder="e.g., Chest, Abdomen, Head">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="view_position" class="form-label">View Position</label>
                                    <input type="text" class="form-control" id="view_position" name="view_position" placeholder="e.g., PA, AP, Lateral">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="images" class="form-label">Select Images <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*,.dcm,.dicom" required>
                                    <small class="form-text text-muted">You can select multiple images. Supported formats: JPG, PNG, DICOM (max 50MB each)</small>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Upload Images
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            <!-- Images Section -->
            @php
                $allImages = collect();
                if ($study->series) {
                    foreach ($study->series as $series) {
                        if ($series->images) {
                            $allImages = $allImages->merge($series->images);
                        }
                    }
                }
            @endphp
            @if($allImages->count() > 0)
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Study Images ({{ $allImages->count() }} total)</h5>
                    @if($study->status === 'in_progress')
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('uploadImagesForm').scrollIntoView({behavior: 'smooth'})">
                            <i class="bi bi-plus-circle"></i> Upload More
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    @foreach($study->series as $series)
                        @if($series->images && $series->images->count() > 0)
                            <div class="mb-4">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-collection"></i> Series: {{ $series->series_description }}
                                    @if($series->body_part_examined)
                                        <small class="text-muted">({{ $series->body_part_examined }})</small>
                                    @endif
                                </h6>
                                <div class="row">
                                    @foreach($series->images as $image)
                                    <div class="col-md-3 mb-3">
                                        <div class="card">
                                            <div class="card-body text-center">
                                                @php
                                                    $imageUrl = $image->getUrl();
                                                    $fileExists = $image->exists();
                                                @endphp
                                                @if($fileExists && $image->isDisplayableImage())
                                                    <img src="{{ $imageUrl }}" alt="{{ $image->file_name }}" class="img-fluid mb-2" style="max-height: 150px; object-fit: contain;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                    <i class="bi bi-file-earmark-medical fs-1 text-muted" style="display:none;"></i>
                                                @else
                                                    <i class="bi bi-file-earmark-medical fs-1 text-muted"></i>
                                                    @if(!$fileExists)
                                                        <p class="small text-danger mb-0">File missing on disk</p>
                                                    @endif
                                                @endif
                                                <h6 class="card-title mt-2 small">{{ $image->file_name }}</h6>
                                                <p class="card-text small text-muted">
                                                    {{ $image->getFileSizeFormatted() }}
                                                </p>
                                                @if($fileExists)
                                                    <a href="{{ $imageUrl }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @elseif($study->status === 'in_progress')
            <div class="card mb-4">
                <div class="card-body text-center py-5">
                    <i class="bi bi-image fs-1 text-muted mb-3"></i>
                    <p class="text-muted">No images uploaded yet. Use the upload form above to add scan images.</p>
                </div>
            </div>
            @endif

            <!-- Report Information -->
            @if($study->report)
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
                                    <span class="badge {{ $reportStatusClasses[$study->report->status] ?? 'bg-secondary' }}">
                                        {{ ucfirst($study->report->status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Radiologist</label>
                                <p class="form-control-plaintext">{{ $study->report->radiologist->full_name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Dictated Date</label>
                                <p class="form-control-plaintext">{{ $study->report->dictated_date->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                        @if($study->report->signed_date)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Signed Date</label>
                                <p class="form-control-plaintext">{{ $study->report->signed_date->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
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
                    @if($study->status === 'in_progress')
                        <a href="#" class="btn btn-success w-100 mb-2" onclick="completeStudy({{ $study->id }})">
                            <i class="bi bi-check-circle"></i> Complete Study
                        </a>
                    @endif
                    
                    @if($study->status === 'completed' && !$study->hasReport())
                        <a href="{{ route('radiology.reports.create', $study) }}" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-file-text"></i> Create Report
                        </a>
                    @endif
                    
                    @if($study->hasReport())
                        <a href="{{ route('radiology.reports.show', $study->report) }}" class="btn btn-info w-100 mb-2">
                            <i class="bi bi-file-text"></i> View Report
                        </a>
                        <a href="{{ route('radiology.reports.pdf', $study->report) }}" class="btn btn-outline-primary w-100 mb-2" target="_blank">
                            <i class="bi bi-file-pdf"></i> Download Report PDF
                        </a>
                    @endif
                    
                    <a href="{{ route('radiology.studies.pdf', $study) }}" class="btn btn-outline-secondary w-100 mb-2" target="_blank">
                        <i class="bi bi-file-pdf"></i> Download Study PDF
                    </a>

                    @if($allImages->count() > 0)
                        <a href="{{ route('radiology.viewer', $study) }}" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-eye"></i> Open DICOM Viewer
                        </a>
                    @endif
                    
                    @if($study->request)
                    <a href="{{ route('radiology.show', $study->request) }}" class="btn btn-outline-info w-100 mb-2">
                        <i class="bi bi-arrow-left"></i> View Request
                    </a>
                    @endif
                </div>
            </div>

            <!-- Study Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Study Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Imaging Modality</label>
                        <p class="form-control-plaintext">{{ $study->modality->name }}</p>
                    </div>
                    @if($study->equipment)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Equipment</label>
                        <p class="form-control-plaintext">{{ $study->equipment->name }}</p>
                    </div>
                    @endif
                    @if($study->technician)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Technician</label>
                        <p class="form-control-plaintext">{{ $study->technician->name }}</p>
                    </div>
                    @endif
                    @if($study->radiologist)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Radiologist</label>
                        <p class="form-control-plaintext">{{ $study->radiologist->first_name }} {{ $study->radiologist->last_name }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Request Details -->
            @if($study->request)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Request Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Request Number</label>
                        <p class="form-control-plaintext">{{ $study->request->request_number }}</p>
                    </div>
                    @if($study->request->doctor)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Requesting Doctor</label>
                        <p class="form-control-plaintext">{{ $study->request->doctor->first_name }} {{ $study->request->doctor->last_name }}</p>
                    </div>
                    @endif
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
                            <span class="badge {{ $priorityClasses[$study->request->priority] ?? 'bg-secondary' }}">
                                {{ ucfirst($study->request->priority) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Complete Study Modal -->
<div class="modal fade" id="completeStudyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Radiology Study</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="completeStudyForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Final Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="completed">Completed</option>
                            <option value="needs_review">Needs Review</option>
                        </select>
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
                    <button type="submit" class="btn btn-success">Complete Study</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function completeStudy(studyId) {
    document.getElementById('completeStudyForm').action = "{{ route('radiology.complete-study', ':studyId') }}".replace(':studyId', studyId);
    new bootstrap.Modal(document.getElementById('completeStudyModal')).show();
}
</script>
@endpush
