@extends('layouts.app')

@section('title', 'Consultations')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Consultations</h1>
            <p class="text-secondary mb-0">Manage patient consultations and medical records</p>
        </div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('consultations.export'),
                'permission' => 'view_consultations',
                'params' => request()->only(['filter', 'search', 'date_from', 'date_to']),
            ])
            @can('create_consultations')
            <a href="{{ route('consultations.create-request') }}" class="btn btn-success">
                <i class="bi bi-clipboard-plus"></i> Create Request
            </a>
            <a href="{{ route('consultations.create') }}" class="btn btn-primary">
                <i class="bi bi-stethoscope"></i> Full Consultation
            </a>
            @endcan
            @can('manage_consultations')
            <a href="{{ route('consultations.doctor-queue') }}" class="btn btn-info">
                <i class="bi bi-list-ol"></i> Doctor Queue
            </a>
            <button type="button" 
                    class="btn btn-outline-danger" 
                    onclick="bulkDeleteConsultations()" 
                    id="bulk-delete-consultations-btn-top" 
                    disabled>
                <i class="bi bi-trash"></i> Delete Selected
            </button>
            @endcan
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-2 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div class="stat-label">Total</div>
                <div class="stat-value">{{ number_format($statistics['total']) }}</div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-calendar-day"></i>
                </div>
                <div class="stat-label">Today</div>
                <div class="stat-value">{{ number_format($statistics['today']) }}</div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-label">Pending</div>
                <div class="stat-value">{{ number_format($statistics['pending']) }}</div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div class="stat-label">In Progress</div>
                <div class="stat-value">{{ number_format($statistics['in_progress']) }}</div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Completed</div>
                <div class="stat-value">{{ number_format($statistics['completed']) }}</div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="stat-card secondary">
                <div class="stat-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="stat-label">Drafts</div>
                <div class="stat-value">{{ number_format($statistics['drafts']) }}</div>
            </div>
        </div>
    </div>
    
    <!-- Search and Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('consultations.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" 
                           class="form-control" 
                           id="search" 
                           name="search" 
                           value="{{ $searchTerm }}" 
                           placeholder="Patient name, number, consultation #...">
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" 
                           class="form-control" 
                           id="date_from" 
                           name="date_from" 
                           value="{{ $dateFrom }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" 
                           class="form-control" 
                           id="date_to" 
                           name="date_to" 
                           value="{{ $dateTo }}">
                </div>
                <div class="col-md-2">
                    <label for="filter" class="form-label">Status Filter</label>
                    <select class="form-select" id="filter" name="filter">
                        <option value="all" {{ $currentFilter === 'all' ? 'selected' : '' }}>All Consultations</option>
                        <option value="pending" {{ $currentFilter === 'pending' ? 'selected' : '' }}>Pending/Not Started</option>
                        <option value="draft" {{ $currentFilter === 'draft' ? 'selected' : '' }}>Drafts</option>
                        <option value="in_progress" {{ $currentFilter === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="ongoing" {{ $currentFilter === 'ongoing' ? 'selected' : '' }}>All Ongoing</option>
                        <option value="completed" {{ $currentFilter === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $currentFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('consultations.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
                @if($currentFilter !== 'all' || $searchTerm || $dateFrom || $dateTo)
                    <input type="hidden" name="filter" value="{{ $currentFilter }}">
                @endif
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark">
                <i class="bi bi-list-ul"></i> 
                @if($currentFilter === 'all')
                    All Consultations
                @elseif($currentFilter === 'pending')
                    Pending/Not Started Consultations
                @elseif($currentFilter === 'draft')
                    Draft Consultations
                @elseif($currentFilter === 'in_progress')
                    In Progress Consultations
                @elseif($currentFilter === 'ongoing')
                    All Ongoing Consultations
                @elseif($currentFilter === 'completed')
                    Completed Consultations
                @elseif($currentFilter === 'cancelled')
                    Cancelled Consultations
                @else
                    Consultations
                @endif
                <span class="badge bg-secondary ms-2">{{ $consultations->total() }}</span>
            </h5>
            <div class="d-flex gap-2">
                <div class="btn-group" role="group">
                    <a href="{{ route('consultations.index', array_merge(request()->except('filter'), ['filter' => 'all'])) }}" 
                       class="btn btn-outline-primary btn-sm {{ $currentFilter === 'all' ? 'active' : '' }}">
                        <i class="bi bi-list"></i> All
                    </a>
                    <a href="{{ route('consultations.index', array_merge(request()->except('filter'), ['filter' => 'pending'])) }}" 
                       class="btn btn-outline-warning btn-sm {{ $currentFilter === 'pending' ? 'active' : '' }}">
                        <i class="bi bi-hourglass-split"></i> Pending
                    </a>
                    <a href="{{ route('consultations.index', array_merge(request()->except('filter'), ['filter' => 'in_progress'])) }}" 
                       class="btn btn-outline-info btn-sm {{ $currentFilter === 'in_progress' ? 'active' : '' }}">
                        <i class="bi bi-arrow-repeat"></i> In Progress
                    </a>
                    <a href="{{ route('consultations.index', array_merge(request()->except('filter'), ['filter' => 'completed'])) }}" 
                       class="btn btn-outline-success btn-sm {{ $currentFilter === 'completed' ? 'active' : '' }}">
                        <i class="bi bi-check-circle"></i> Completed
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            @can('manage_consultations')
                            <th width="50">
                                <input type="checkbox" id="select-all-consultations" class="form-check-input">
                            </th>
                            @endcan
                            <th style="width: 120px;">Consultation #</th>
                            <th>Patient</th>
                            <th>Chief Complaint</th>
                            <th>Date & Time</th>
                            <th style="width: 100px;">Priority</th>
                            <th style="width: 150px;">Status</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consultations as $consultation)
                        @php
                            // Determine consultation state for proper badge display
                            $statusBadge = 'warning';
                            $statusText = 'Ongoing';
                            $stateBadge = '';
                            $stateText = '';
                            
                            if ($consultation->consultation_status === 'completed') {
                                $statusBadge = 'success';
                                $statusText = 'Completed';
                            } elseif ($consultation->consultation_status === 'cancelled') {
                                $statusBadge = 'danger';
                                $statusText = 'Cancelled';
                            } else {
                                if ($consultation->is_draft) {
                                    $stateBadge = 'secondary';
                                    $stateText = 'Draft/Pending';
                                } else {
                                    $stateBadge = 'info';
                                    $stateText = 'In Progress';
                                }
                            }
                            
                            // Priority badge
                            $priorityBadge = 'secondary';
                            if ($consultation->urgency === 'critical') {
                                $priorityBadge = 'danger';
                            } elseif ($consultation->urgency === 'urgent') {
                                $priorityBadge = 'warning';
                            }
                        @endphp
                        <tr class="{{ $consultation->is_draft ? 'table-warning' : ($consultation->consultation_status === 'completed' ? 'table-success' : '') }}">
                            @can('manage_consultations')
                            <td>
                                <input type="checkbox" class="form-check-input consultation-checkbox" value="{{ $consultation->id }}">
                            </td>
                            @endcan
                            <td>
                                <strong class="text-primary">{{ $consultation->consultation_number ?? 'N/A' }}</strong>
                                @if($consultation->visit)
                                    <br><small class="text-muted">{{ $consultation->visit->visit_token ?? '' }}</small>
                                @endif
                            </td>
                            <td>
                                @if($consultation->patient)
                                    <strong>{{ $consultation->patient->first_name }} {{ $consultation->patient->last_name }}</strong>
                                    <br><small class="text-muted">{{ $consultation->patient->patient_number ?? 'N/A' }}</small>
                                    @if($consultation->patient->phone)
                                        <br><small class="text-muted"><i class="bi bi-telephone"></i> {{ $consultation->patient->phone }}</small>
                                    @endif
                                @else
                                    <span class="text-danger">Patient Not Found</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 250px;" title="{{ $consultation->chief_complaint }}">
                                    {{ Str::limit($consultation->chief_complaint ?? 'No complaint recorded', 50) }}
                                </div>
                                @if($consultation->vitals && $consultation->vitals->count() > 0)
                                    @php $latestVitals = $consultation->vitals->last(); @endphp
                                    <div class="mt-1">
                                        @if($latestVitals->blood_pressure_systolic)
                                            <small class="badge bg-light text-dark me-1">BP: {{ $latestVitals->blood_pressure_systolic }}/{{ $latestVitals->blood_pressure_diastolic }}</small>
                                        @endif
                                        @if($latestVitals->temperature)
                                            <small class="badge bg-light text-dark me-1">T: {{ $latestVitals->temperature }}°C</small>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $consultation->consultation_date ? \Carbon\Carbon::parse($consultation->consultation_date)->format('M d, Y') : 'N/A' }}</div>
                                @if($consultation->consultation_time)
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($consultation->consultation_time)->format('h:i A') }}</small>
                                @endif
                                @if($consultation->created_at)
                                    <br><small class="text-muted">{{ $consultation->created_at->diffForHumans() }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $priorityBadge }}">
                                    {{ ucfirst($consultation->urgency ?? 'routine') }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusBadge }}">
                                    {{ $statusText }}
                                </span>
                                @if($stateText)
                                    <br><span class="badge bg-{{ $stateBadge }} mt-1">{{ $stateText }}</span>
                                @endif
                                @if($consultation->consultation_type)
                                    <br><small class="badge bg-info mt-1">{{ ucfirst($consultation->consultation_type) }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('consultations.show', $consultation) }}" 
                                       class="btn btn-info" 
                                       title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($consultation->is_draft && $consultation->consultation_status === 'ongoing')
                                        <a href="{{ route('consultations.create-from-queue', $consultation) }}" 
                                           class="btn btn-primary" 
                                           title="Start Consultation">
                                            <i class="bi bi-play-fill"></i>
                                        </a>
                                    @elseif($consultation->consultation_status === 'ongoing' && !$consultation->is_draft)
                                        <a href="{{ route('consultations.edit', $consultation) }}" 
                                           class="btn btn-warning" 
                                           title="Continue Consultation">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @elseif($consultation->consultation_status === 'completed')
                                        @can('edit_consultations')
                                        <a href="{{ route('consultations.edit', $consultation) }}" 
                                           class="btn btn-warning" 
                                           title="Amend Consultation">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->can('manage_consultations') ? '8' : '7' }}" class="text-center py-5">
                                <i class="bi bi-clipboard-x text-secondary" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="text-secondary mt-2 mb-0">
                                    @if($currentFilter !== 'all' || $searchTerm || $dateFrom || $dateTo)
                                        No consultations found matching your filters.
                                    @else
                                        No consultations found.
                                    @endif
                                </p>
                                @if($currentFilter !== 'all' || $searchTerm || $dateFrom || $dateTo)
                                    <a href="{{ route('consultations.index') }}" class="btn btn-outline-primary btn-sm mt-2">
                                        <i class="bi bi-arrow-clockwise"></i> Clear Filters
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($consultations->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div>
                <small class="text-muted">
                    Showing {{ $consultations->firstItem() }} to {{ $consultations->lastItem() }} of {{ $consultations->total() }} consultations
                </small>
            </div>
            <div>
                {{ $consultations->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Bulk delete functionality for consultations
function bulkDeleteConsultations() {
    const selectedIds = [];
    document.querySelectorAll('.consultation-checkbox:checked').forEach(checkbox => {
        selectedIds.push(checkbox.value);
    });
    
    if (selectedIds.length === 0) {
        alert('Please select at least one consultation to delete.');
        return;
    }
    
    const confirmMessage = selectedIds.length === 1
        ? 'Are you sure you want to delete this consultation? This action cannot be undone.'
        : `Are you sure you want to delete ${selectedIds.length} selected consultation(s)? This action cannot be undone.`;
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Create a form for bulk delete
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("consultations.bulk-delete") }}';
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);
    
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

// Initialize checkbox functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all-consultations');
    const consultationCheckboxes = document.querySelectorAll('.consultation-checkbox');
    const bulkDeleteBtnTop = document.getElementById('bulk-delete-consultations-btn-top');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            consultationCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkDeleteButtons();
        });
    }
    
    consultationCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkDeleteButtons();
            
            // Update select all checkbox
            if (selectAllCheckbox) {
                const checkedCount = document.querySelectorAll('.consultation-checkbox:checked').length;
                selectAllCheckbox.checked = checkedCount === consultationCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < consultationCheckboxes.length;
            }
        });
    });
    
    function updateBulkDeleteButtons() {
        const checkedCount = document.querySelectorAll('.consultation-checkbox:checked').length;
        const buttons = [bulkDeleteBtnTop].filter(btn => btn !== null);
        
        buttons.forEach(btn => {
            if (checkedCount > 0) {
                btn.disabled = false;
                btn.innerHTML = `<i class="bi bi-trash"></i> Delete Selected (${checkedCount})`;
            } else {
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-trash"></i> Delete Selected';
            }
        });
    }
    
    // Initial update
    updateBulkDeleteButtons();
});
</script>
@endpush
@endsection

