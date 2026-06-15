@extends('layouts.app')

@section('title', 'Radiology Reports')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Radiology Reports</h1>
                <p class="page-subtitle">Manage radiology imaging reports</p>
            </div>
            <div class="page-actions d-flex gap-2">
                @include('components.export-dropdown', [
                    'exportRoute' => route('radiology.reports.export'),
                    'permission' => 'view_radiology_reports',
                    'params' => request()->only(['status', 'search']),
                ])
                <a href="{{ route('radiology.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Requests
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('radiology.reports') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Patient name or number">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="preliminary" {{ request('status') == 'preliminary' ? 'selected' : '' }}>Preliminary</option>
                        <option value="final" {{ request('status') == 'final' ? 'selected' : '' }}>Final</option>
                        <option value="amended" {{ request('status') == 'amended' ? 'selected' : '' }}>Amended</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('radiology.reports') }}" class="btn btn-outline-secondary">
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
                            <h6 class="card-title">Total Reports</h6>
                            <h3 class="mb-0">{{ $reports->total() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-file-text fs-1"></i>
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
                            <h6 class="card-title">Draft</h6>
                            <h3 class="mb-0">{{ $reports->where('status', 'draft')->count() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-pencil fs-1"></i>
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
                            <h6 class="card-title">Preliminary</h6>
                            <h3 class="mb-0">{{ $reports->where('status', 'preliminary')->count() }}</h3>
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
                            <h6 class="card-title">Final</h6>
                            <h3 class="mb-0">{{ $reports->where('status', 'final')->count() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Radiology Reports</h5>
        </div>
        <div class="card-body">
            @if($reports->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Report ID</th>
                                <th>Patient</th>
                                <th>Study</th>
                                <th>Radiologist</th>
                                <th>Status</th>
                                <th>Dictated Date</th>
                                <th>Signed Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $report)
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary">#{{ $report->id }}</span>
                                    </td>
                                    <td>
                                        @if($report->study && $report->study->request && $report->study->request->patient)
                                            <div>
                                                <div class="fw-bold">{{ $report->study->request->patient->first_name }} {{ $report->study->request->patient->last_name }}</div>
                                                <small class="text-muted">{{ $report->study->request->patient->patient_number }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">No patient data</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($report->study)
                                            <div>
                                                @if($report->study->modality)
                                                    <div class="fw-bold">{{ $report->study->modality->name }}</div>
                                                @endif
                                                <small class="text-muted">{{ $report->study->study_description }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($report->radiologist)
                                            <div class="fw-bold">{{ $report->radiologist->first_name }} {{ $report->radiologist->last_name }}</div>
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
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
                                    </td>
                                    <td>
                                        @if($report->dictated_date)
                                            {{ $report->dictated_date->format('M d, Y H:i') }}
                                        @else
                                            <span class="text-muted">Not dictated</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($report->signed_date)
                                            {{ $report->signed_date->format('M d, Y H:i') }}
                                        @else
                                            <span class="text-muted">Not signed</span>
                                        @endif
                                    </td>
                                    <td class="position-static">
                                        <div class="dropdown position-static">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                                @can('view_radiology_results')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('radiology.reports.show', $report) }}">
                                                        <i class="bi bi-eye me-2"></i>View Report
                                                    </a>
                                                </li>
                                                @endcan
                                                @can('edit_radiology_reports')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('radiology.reports.edit', $report) }}">
                                                        <i class="bi bi-pencil me-2"></i>Edit Report
                                                    </a>
                                                </li>
                                                @endcan
                                                @can('view_radiology_results')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('radiology.reports.pdf', $report) }}" target="_blank">
                                                        <i class="bi bi-file-pdf me-2"></i>Download PDF
                                                    </a>
                                                </li>
                                                @endcan
                                                @if($report->status === 'final' && !$report->isSigned())
                                                @can('edit_radiology_reports')
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="signReport({{ $report->id }})">
                                                        <i class="bi bi-pen me-2"></i>Sign Report
                                                    </a>
                                                </li>
                                                @endcan
                                                @endif
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
                    {{ $reports->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-file-text fs-1 text-muted"></i>
                    <h5 class="mt-3">No radiology reports found</h5>
                    <p class="text-muted">Reports will appear here once studies are completed and reports are created.</p>
                    <a href="{{ route('radiology.index') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Back to Requests
                    </a>
                </div>
            @endif
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

@push('scripts')
<script>
function signReport(reportId) {
    // Use Laravel route helper to generate correct URL
    document.getElementById('signReportForm').action = "{{ route('radiology.reports.update', ':reportId') }}".replace(':reportId', reportId);
    new bootstrap.Modal(document.getElementById('signReportModal')).show();
}
</script>
@endpush
