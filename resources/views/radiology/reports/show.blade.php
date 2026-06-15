@extends('layouts.app')

@section('title', 'Radiology Report')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Radiology Report</h1>
                <p class="page-subtitle">Report #{{ $report->id }}</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('radiology.reports') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Reports
                </a>
                @can('edit_radiology_reports')
                <a href="{{ route('radiology.reports.edit', $report) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                @endcan
                <a href="{{ route('radiology.reports.pdf', $report) }}" class="btn btn-success" target="_blank">
                    <i class="bi bi-file-pdf"></i> Download PDF
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Report Header -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Report Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Report ID</label>
                                <p class="form-control-plaintext">#{{ $report->id }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <p>
                                    @php
                                        $statusClasses = [
                                            'draft' => 'bg-secondary',
                                            'preliminary' => 'bg-warning',
                                            'final' => 'bg-success',
                                            'amended' => 'bg-info',
                                            'cancelled' => 'bg-danger'
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusClasses[$report->status] ?? 'bg-secondary' }}">
                                        {{ ucfirst($report->status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Dictated Date</label>
                                <p class="form-control-plaintext">{{ $report->dictated_date->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Radiologist</label>
                                <p class="form-control-plaintext">{{ $report->radiologist->full_name }}</p>
                            </div>
                        </div>
                    </div>
                    @if($report->signed_date)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Signed Date</label>
                                <p class="form-control-plaintext">{{ $report->signed_date->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    @if($report->amendment_reason)
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Amendment Reason</label>
                                <div class="border rounded p-3 bg-light">
                                    {!! $report->amendment_reason !!}
                                </div>
                            </div>
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
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Patient Name</label>
                                <p class="form-control-plaintext">{{ $report->study->request->patient->full_name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Patient Number</label>
                                <p class="form-control-plaintext">{{ $report->study->request->patient->patient_number }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Date of Birth</label>
                                <p class="form-control-plaintext">{{ $report->study->request->patient->date_of_birth ? $report->study->request->patient->date_of_birth->format('M d, Y') : 'Not provided' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Gender</label>
                                <p class="form-control-plaintext">{{ $report->study->request->patient->gender }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Study Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Study Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Imaging Modality</label>
                                <p class="form-control-plaintext">{{ $report->study->modality->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Study Date</label>
                                <p class="form-control-plaintext">{{ $report->study->study_date->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Study Description</label>
                        <p class="form-control-plaintext">{{ $report->study->study_description }}</p>
                    </div>
                    @if($report->study->study_notes)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Study Notes</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $report->study->study_notes !!}
                        </div>
                    </div>
                    @endif
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
                            {!! $report->study->request->clinical_history !!}
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Clinical Question</label>
                        <div class="border rounded p-3 bg-light">
                            {!! $report->study->request->clinical_question !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Content -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Report Content</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary mb-3">FINDINGS</h6>
                        <div class="border rounded p-4 bg-light">
                            {!! $report->findings !!}
                        </div>
                    </div>
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary mb-3">IMPRESSION</h6>
                        <div class="border rounded p-4 bg-light">
                            {!! $report->impression !!}
                        </div>
                    </div>
                    @if($report->recommendations)
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary mb-3">RECOMMENDATIONS</h6>
                        <div class="border rounded p-4 bg-light">
                            {!! $report->recommendations !!}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    @can('edit_radiology_reports')
                    <a href="{{ route('radiology.reports.edit', $report) }}" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-pencil"></i> Edit Report
                    </a>
                    @endcan
                    
                    @can('view_radiology_results')
                    <a href="{{ route('radiology.reports.pdf', $report) }}" class="btn btn-success w-100 mb-2" target="_blank">
                        <i class="bi bi-file-pdf"></i> Download PDF
                    </a>
                    @endcan
                    
                    @if($report->status === 'final' && !$report->isSigned())
                    @can('edit_radiology_reports')
                    <a href="#" class="btn btn-warning w-100 mb-2" onclick="signReport({{ $report->id }})">
                        <i class="bi bi-pen"></i> Sign Report
                    </a>
                    @endcan
                    @endif
                    
                    @can('view_radiology_requests')
                    <a href="{{ route('radiology.studies.show', $report->study) }}" class="btn btn-outline-info w-100 mb-2">
                        <i class="bi bi-camera"></i> View Study
                    </a>
                    
                    <a href="{{ route('radiology.show', $report->study->request) }}" class="btn btn-outline-secondary w-100 mb-2">
                        <i class="bi bi-arrow-left"></i> View Request
                    </a>
                    @endcan
                </div>
            </div>

            <!-- Report Timeline -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Report Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Report Created</h6>
                                <p class="timeline-text">{{ $report->dictated_date->format('M d, Y H:i') }}</p>
                                <small class="text-muted">by {{ $report->radiologist->full_name }}</small>
                            </div>
                        </div>
                        
                        @if($report->transcribed_date)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Transcribed</h6>
                                <p class="timeline-text">{{ $report->transcribed_date->format('M d, Y H:i') }}</p>
                                @if($report->transcribedBy)
                                <small class="text-muted">by {{ $report->transcribedBy->full_name }}</small>
                                @endif
                            </div>
                        </div>
                        @endif
                        
                        @if($report->signed_date)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Signed</h6>
                                <p class="timeline-text">{{ $report->signed_date->format('M d, Y H:i') }}</p>
                                <small class="text-muted">by {{ $report->radiologist->full_name }}</small>
                            </div>
                        </div>
                        @endif
                        
                        @if($report->status === 'amended')
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Amended</h6>
                                <p class="timeline-text">{{ $report->updated_at->format('M d, Y H:i') }}</p>
                                <small class="text-muted">Report was modified</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Study Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Study Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Study UID</label>
                        <p class="form-control-plaintext small">{{ $report->study->study_uid }}</p>
                    </div>
                    @if($report->study->equipment)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Equipment</label>
                        <p class="form-control-plaintext">{{ $report->study->equipment->name }}</p>
                    </div>
                    @endif
                    @if($report->study->technician)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Technician</label>
                        <p class="form-control-plaintext">{{ $report->study->technician->name }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sign Report Modal -->
<div class="modal fade" id="signReportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sign Radiology Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="signReportForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        By signing this report, you are confirming that the findings and impression are accurate and complete.
                    </div>
                    <div class="mb-3">
                        <label for="signature_confirmation" class="form-label">Signature Confirmation</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="signature_confirmation" name="signature_confirmation" required>
                            <label class="form-check-label" for="signature_confirmation">
                                I confirm that I have reviewed and approved this radiology report.
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Sign Report</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
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
    left: -35px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #007bff;
}

.timeline-title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 5px;
    color: #495057;
}

.timeline-text {
    font-size: 13px;
    margin-bottom: 5px;
    color: #6c757d;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -31px;
    top: 15px;
    width: 2px;
    height: calc(100% + 5px);
    background: #dee2e6;
}
</style>
@endpush

@push('scripts')
<script>
function signReport(reportId) {
    // Use Laravel route helper to generate correct URL
    document.getElementById('signReportForm').action = "{{ route('radiology.reports.update', ':reportId') }}".replace(':reportId', reportId);
    new bootstrap.Modal(document.getElementById('signReportModal')).show();
}
</script>
@endpush
