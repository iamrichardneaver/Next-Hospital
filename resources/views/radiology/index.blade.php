@extends('layouts.app')

@section('title', 'Radiology Requests')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Radiology Requests</h1>
                <p class="page-subtitle">Manage radiology imaging requests and studies</p>
            </div>
            <div class="page-actions d-flex gap-2">
                @include('components.export-dropdown', [
                    'exportRoute' => route('radiology.export'),
                    'permission' => 'view_radiology_requests',
                    'params' => request()->only(['status', 'modality_id', 'priority', 'radiologist_id', 'search']),
                ])
                <a href="{{ route('radiology.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New Request
                </a>
                <a href="{{ route('radiology.studies') }}" class="btn btn-outline-primary">
                    <i class="bi bi-camera"></i> Studies
                </a>
                <a href="{{ route('radiology.reports') }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-text"></i> Reports
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('radiology.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Patient name or number">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="requested" {{ request('status') == 'requested' ? 'selected' : '' }}>Requested</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
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
                    <label for="priority" class="form-label">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">All Priorities</option>
                        <option value="routine" {{ request('priority') == 'routine' ? 'selected' : '' }}>Routine</option>
                        <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        <option value="stat" {{ request('priority') == 'stat' ? 'selected' : '' }}>STAT</option>
                        <option value="emergency" {{ request('priority') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('radiology.index') }}" class="btn btn-outline-secondary">
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
                            <h6 class="card-title">Total Requests</h6>
                            <h3 class="mb-0">{{ $requests->total() }}</h3>
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
                            <h6 class="card-title">Requested</h6>
                            <h3 class="mb-0">{{ $requests->where('status', 'requested')->count() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-clock fs-1"></i>
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
                            <h6 class="card-title">In Progress</h6>
                            <h3 class="mb-0">{{ $requests->where('status', 'in_progress')->count() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-play-circle fs-1"></i>
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
                            <h3 class="mb-0">{{ $requests->where('status', 'completed')->count() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Radiology Requests</h5>
        </div>
        <div class="card-body">
            @if($requests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Request #</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Modality</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Requested Date</th>
                                <th>Scheduled Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $request)
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary">{{ $request->request_number }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-bold">{{ $request->patient->full_name }}</div>
                                            <small class="text-muted">{{ $request->patient->patient_number }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-bold">{{ $request->doctor->full_name }}</div>
                                            <small class="text-muted">{{ $request->doctor->specialization ?? 'General' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $request->modality->name }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $priorityClasses = [
                                                'routine' => 'bg-secondary',
                                                'urgent' => 'bg-warning',
                                                'stat' => 'bg-danger',
                                                'emergency' => 'bg-dark'
                                            ];
                                        @endphp
                                        <span class="badge {{ $priorityClasses[$request->priority] ?? 'bg-secondary' }}">
                                            {{ ucfirst($request->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusClasses = [
                                                'requested' => 'bg-warning',
                                                'scheduled' => 'bg-info',
                                                'in_progress' => 'bg-primary',
                                                'completed' => 'bg-success',
                                                'cancelled' => 'bg-danger',
                                                'rejected' => 'bg-secondary'
                                            ];
                                        @endphp
                                        <span class="badge {{ $statusClasses[$request->status] ?? 'bg-secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $request->requested_date->format('M d, Y') }}</td>
                                    <td>
                                        @if($request->scheduled_date)
                                            {{ $request->scheduled_date->format('M d, Y') }}
                                            @if($request->scheduled_time)
                                                <br><small class="text-muted">{{ $request->scheduled_time->format('H:i') }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">Not scheduled</span>
                                        @endif
                                    </td>
                                    <td class="position-static">
                                        <div class="dropdown position-static">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('radiology.show', $request) }}">
                                                        <i class="bi bi-eye me-2"></i>View Details
                                                    </a>
                                                </li>
                                                @can('edit_radiology_requests')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('radiology.edit', $request) }}">
                                                        <i class="bi bi-pencil me-2"></i>Edit
                                                    </a>
                                                </li>
                                                @endcan
                                                @if($request->status === 'requested' || $request->status === 'scheduled')
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="startStudy({{ $request->id }})">
                                                        <i class="bi bi-play-circle me-2"></i>Start Study
                                                    </a>
                                                </li>
                                                @endif
                                                @if($request->study && $request->study->status === 'completed' && !$request->study->hasReport())
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('radiology.reports.create', $request->study) }}">
                                                        <i class="bi bi-file-text me-2"></i>Create Report
                                                    </a>
                                                </li>
                                                @endif
                                                @can('delete_radiology_requests')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('radiology.destroy', $request) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger" 
                                                                onclick="return confirm('Are you sure you want to delete this request?')">
                                                            <i class="bi bi-trash me-2"></i>Delete
                                                        </button>
                                                    </form>
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
                    {{ $requests->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-camera fs-1 text-muted"></i>
                    <h5 class="mt-3">No radiology requests found</h5>
                    <p class="text-muted">Get started by creating a new radiology request.</p>
                    <a href="{{ route('radiology.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create First Request
                    </a>
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
                                    <!-- Equipment options will be loaded via AJAX -->
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
    // Load equipment options
    fetch('/api/radiology/equipment/available')
        .then(response => response.json())
        .then(data => {
            const equipmentSelect = document.getElementById('equipment_id');
            equipmentSelect.innerHTML = '<option value="">Select Equipment</option>';
            data.data.forEach(equipment => {
                const option = document.createElement('option');
                option.value = equipment.id;
                option.textContent = equipment.name;
                equipmentSelect.appendChild(option);
            });
        });

    // Set form action using Laravel route helper
    document.getElementById('startStudyForm').action = "{{ route('radiology.start-study', ':requestId') }}".replace(':requestId', requestId);
    
    // Show modal
    new bootstrap.Modal(document.getElementById('startStudyModal')).show();
}
</script>
@endpush
