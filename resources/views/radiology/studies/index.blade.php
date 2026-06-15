@extends('layouts.app')

@section('title', 'Radiology Studies')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Radiology Studies</h1>
                <p class="page-subtitle">Manage radiology imaging studies</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('radiology.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Requests
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('radiology.studies') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Patient name or number">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="modality_id" class="form-label">Modality</label>
                    <select class="form-select" id="modality_id" name="modality_id">
                        <option value="">All Modalities</option>
                        @foreach($modalities as $modality)
                            <option value="{{ $modality->id }}" {{ request('modality_id') == $modality->id ? 'selected' : '' }}>
                                {{ $modality->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('radiology.studies') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Studies</h6>
                            <h3 class="mb-0">{{ $studies->total() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-camera fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">In Progress</h6>
                            <h3 class="mb-0">{{ $studies->where('status', 'in_progress')->count() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-play-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Needs Review</h6>
                            <h3 class="mb-0">{{ $studies->where('status', 'needs_review')->count() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-eye fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Completed</h6>
                            <h3 class="mb-0">{{ $studies->where('status', 'completed')->count() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Studies Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Radiology Studies</h5>
        </div>
        <div class="card-body">
            @if($studies->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Study ID</th>
                                <th>Patient</th>
                                <th>Modality</th>
                                <th>Technician</th>
                                <th>Radiologist</th>
                                <th>Status</th>
                                <th>Study Date</th>
                                <th>Completed Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($studies as $study)
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary">#{{ $study->id }}</span>
                                        <br><small class="text-muted">{{ $study->study_uid }}</small>
                                    </td>
                                    <td>
                                        @if($study->request && $study->request->patient)
                                            <div>
                                                <div class="fw-bold">{{ $study->request->patient->first_name }} {{ $study->request->patient->last_name }}</div>
                                                <small class="text-muted">{{ $study->request->patient->patient_number }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">No patient data</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($study->modality)
                                            <span class="badge bg-info">{{ $study->modality->name }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($study->technician && $study->technician->user)
                                            <div class="fw-bold">{{ $study->technician->user->first_name }} {{ $study->technician->user->last_name }}</div>
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($study->radiologist)
                                            <div class="fw-bold">{{ $study->radiologist->first_name }} {{ $study->radiologist->last_name }}</div>
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
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
                                    </td>
                                    <td>{{ $study->study_date->format('M d, Y H:i') }}</td>
                                    <td>
                                        @if($study->completed_date)
                                            {{ $study->completed_date->format('M d, Y H:i') }}
                                        @else
                                            <span class="text-muted">Not completed</span>
                                        @endif
                                    </td>
                                    <td class="position-static">
                                        <div class="dropdown position-static">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                                @can('view_radiology_requests')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('radiology.studies.show', $study) }}">
                                                        <i class="bi bi-eye me-2"></i>View Study
                                                    </a>
                                                </li>
                                                @endcan
                                                @if($study->status === 'in_progress')
                                                @can('edit_radiology_requests')
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="completeStudy({{ $study->id }})">
                                                        <i class="bi bi-check-circle me-2"></i>Complete Study
                                                    </a>
                                                </li>
                                                @endcan
                                                @endif
                                                @if($study->status === 'completed' && !$study->hasReport())
                                                @can('create_radiology_requests')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('radiology.reports.create', $study) }}">
                                                        <i class="bi bi-file-text me-2"></i>Create Report
                                                    </a>
                                                </li>
                                                @endcan
                                                @endif
                                                @if($study->hasReport())
                                                @can('view_radiology_results')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('radiology.reports.show', $study->report) }}">
                                                        <i class="bi bi-file-text me-2"></i>View Report
                                                    </a>
                                                </li>
                                                @endcan
                                                @endif
                                                @can('view_radiology_requests')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('radiology.studies.pdf', $study) }}" target="_blank">
                                                        <i class="bi bi-file-pdf me-2"></i>Download PDF
                                                    </a>
                                                </li>
                                                @endcan
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $studies->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-camera fs-1 text-muted"></i>
                    <h5 class="mt-3">No radiology studies found</h5>
                    <p class="text-muted">Studies will appear here once radiology requests are started.</p>
                    <a href="{{ route('radiology.index') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Back to Requests
                    </a>
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
