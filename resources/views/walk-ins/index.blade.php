@extends('layouts.app')

@section('title', 'Daily Walk-ins Register')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <!-- Toolbar -->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                    Daily Walk-ins Register
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Walk-ins Register</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                @can('create_patients')
                <a href="{{ route('patients.create') }}" class="btn btn-sm btn-success">
                    <i class="bi bi-person-plus-fill fs-4"></i> Register Patient
                </a>
                @endcan
                @include('components.export-dropdown', [
                    'exportRoute' => route('walk-ins.export-csv'),
                    'permission' => 'export_walk_ins_register',
                    'params' => request()->only(['branch_id', 'date', 'visit_type', 'status', 'search']),
                    'btnClass' => 'btn btn-sm btn-outline-success',
                    'extraLinks' => [[
                        'url' => route('walk-ins.export', array_merge(request()->only(['branch_id', 'date', 'visit_type', 'status', 'search']), ['branch_id' => $branchId, 'date' => $date])),
                        'label' => 'Export PDF',
                        'icon' => 'bi-file-pdf',
                    ]],
                ])
            </div>
        </div>
    </div>

    <!-- Content -->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            
            <!-- Statistics Cards -->
            <div class="row g-3 mb-5">
                <!-- Total Visits -->
                <div class="col-xl-3">
                    <div class="stat-card danger">
                        <div class="stat-icon">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <div class="stat-label">Total Visits Today</div>
                        <div class="stat-value">{{ $stats['total_visits'] }}</div>
                        <div class="small opacity-75 mt-2">
                            Active: {{ $stats['active_visits'] }} | Completed: {{ $stats['completed_visits'] }}
                        </div>
                    </div>
                </div>

                <!-- OPD Visits -->
                <div class="col-xl-3">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="bi bi-hospital"></i>
                        </div>
                        <div class="stat-label">OPD Visits</div>
                        <div class="stat-value">{{ $stats['opd_visits'] }}</div>
                        <div class="small opacity-75 mt-2">
                            Emergency: {{ $stats['emergency_visits'] }} | IPD: {{ $stats['ipd_visits'] }}
                        </div>
                    </div>
                </div>

                <!-- Queue Status -->
                <div class="col-xl-3">
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="stat-label">Waiting in Queue</div>
                        <div class="stat-value">{{ $stats['waiting_in_queue'] }}</div>
                        <div class="small opacity-75 mt-2">
                            Being Served: {{ $stats['being_served'] }} | Avg: {{ $stats['avg_wait_time'] }}m
                        </div>
                    </div>
                </div>

                <!-- Direct Services -->
                <div class="col-xl-3">
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="bi bi-lightning"></i>
                        </div>
                        <div class="stat-label">Direct Services</div>
                        <div class="stat-value">{{ $stats['lab_only_visits'] + $stats['pharmacy_only_visits'] }}</div>
                        <div class="small opacity-75 mt-2">
                            Lab: {{ $stats['lab_only_visits'] }} | Pharmacy: {{ $stats['pharmacy_only_visits'] }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Search and Filter Section -->
            <div class="card mb-5 shadow-sm">
                <div class="card-body p-4">
                    <!-- Search Bar -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="search-container position-relative">
                                <div class="search-icon">
                                    <i class="bi bi-search"></i>
                                </div>
                                <input type="text" 
                                       id="searchInput" 
                                       class="form-control form-control-lg search-input" 
                                       placeholder="Search patients by name, ID, phone, or NHIS number..." 
                                       value="{{ request('search') }}" />
                                <div class="search-clear" id="searchClear" style="display: none;">
                                    <i class="bi bi-x-circle-fill"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Controls -->
                    <div class="row g-3 align-items-end">
                        <!-- Date Filter -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label fw-semibold text-gray-700 mb-2">
                                <i class="bi bi-calendar3 me-1"></i>Date
                            </label>
                            <div class="position-relative">
                                <input type="date" 
                                       id="dateFilter" 
                                       class="form-control form-control-solid filter-input" 
                                       value="{{ $date }}" />
                                <div class="filter-icon">
                                    <i class="bi bi-calendar3"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Branch Filter -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label fw-semibold text-gray-700 mb-2">
                                <i class="bi bi-building me-1"></i>Branch
                            </label>
                            <div class="position-relative">
                                <select id="branchFilter" class="form-select form-select-solid filter-input">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="filter-icon">
                                    <i class="bi bi-building"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Visit Type Filter -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label fw-semibold text-gray-700 mb-2">
                                <i class="bi bi-hospital me-1"></i>Type
                            </label>
                            <div class="position-relative">
                                <select id="visitTypeFilter" class="form-select form-select-solid filter-input">
                                    <option value="all" {{ $visitType == 'all' ? 'selected' : '' }}>All Types</option>
                                    <option value="OPD" {{ $visitType == 'OPD' ? 'selected' : '' }}>OPD</option>
                                    <option value="IPD" {{ $visitType == 'IPD' ? 'selected' : '' }}>IPD</option>
                                    <option value="Emergency" {{ $visitType == 'Emergency' ? 'selected' : '' }}>Emergency</option>
                                    <option value="LabOnly" {{ $visitType == 'LabOnly' ? 'selected' : '' }}>Lab Only</option>
                                    <option value="PharmacyOnly" {{ $visitType == 'PharmacyOnly' ? 'selected' : '' }}>Pharmacy Only</option>
                                    <option value="RadiologyOnly" {{ $visitType == 'RadiologyOnly' ? 'selected' : '' }}>Radiology Only</option>
                                </select>
                                <div class="filter-icon">
                                    <i class="bi bi-hospital"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label fw-semibold text-gray-700 mb-2">
                                <i class="bi bi-flag me-1"></i>Status
                            </label>
                            <div class="position-relative">
                                <select id="statusFilter" class="form-select form-select-solid filter-input">
                                    <option value="all" {{ $status == 'all' ? 'selected' : '' }}>All Status</option>
                                    <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="completed" {{ $status == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                <div class="filter-icon">
                                    <i class="bi bi-flag"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-lg-4 col-md-12">
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" id="clearFiltersBtn" class="btn btn-light btn-sm">
                                    <i class="bi bi-x-circle me-1"></i>Clear
                                </button>
                                <button type="button" id="refreshBtn" class="btn btn-primary btn-sm">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                </button>
                                <button type="button" id="exportBtn" class="btn btn-success btn-sm">
                                    <i class="bi bi-download me-1"></i>Export
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Active Filters Display -->
                    <div class="row mt-3" id="activeFilters" style="display: none;">
                        <div class="col-12">
                            <div class="d-flex flex-wrap gap-2">
                                <span class="text-muted small">Active filters:</span>
                                <div id="filterTags"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading walk-ins data...</p>
                    </div>

                    <!-- Visits Table -->
                    <div class="table-responsive" id="visitsTable">
                        <table class="table table-row-bordered table-row-gray-300 align-middle gs-0 gy-3">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th class="min-w-50px">Time</th>
                                    <th class="min-w-120px">Patient</th>
                                    <th class="min-w-100px">Visit Token</th>
                                    <th class="min-w-100px">Visit Type</th>
                                    <th class="min-w-100px">Status</th>
                                    <th class="min-w-100px">Priority</th>
                                    <th class="min-w-150px">Current Queue</th>
                                    <th class="min-w-120px">Assigned To</th>
                                    <th class="min-w-100px text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($visits as $visit)
                                <tr>
                                    <td>
                                        <span class="text-dark fw-bold d-block fs-6">{{ $visit->check_in_time->format('H:i') }}</span>
                                        <span class="text-muted fw-semibold d-block fs-7">{{ $visit->check_in_time->diffForHumans() }}</span>
                                    </td>
                                    <td>
                                        @if($visit->patient)
                                            <a href="{{ route('patients.show', $visit->patient_id) }}" class="text-dark fw-bold text-hover-primary d-block fs-6">
                                                {{ $visit->patient->first_name }} {{ $visit->patient->last_name }}
                                            </a>
                                            <span class="text-muted fw-semibold d-block fs-7">{{ $visit->patient->patient_number }}</span>
                                            @if($visit->patient->nhis_number)
                                            <span class="badge badge-light-info fs-8">NHIS: {{ $visit->patient->nhis_number }}</span>
                                            @endif
                                        @else
                                            <span class="text-danger fw-bold d-block fs-6">
                                                <i class="bi bi-exclamation-triangle"></i> Patient Not Found
                                            </span>
                                            <span class="text-muted fw-semibold d-block fs-7">ID: {{ $visit->patient_id }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-light-primary">{{ $visit->visit_token ?? '—' }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $typeColors = [
                                                'OPD' => 'primary',
                                                'IPD' => 'success',
                                                'Emergency' => 'danger',
                                                'LabOnly' => 'info',
                                                'PharmacyOnly' => 'warning',
                                                'RadiologyOnly' => 'info'
                                            ];
                                            $typeLabels = [
                                                'LabOnly' => 'Lab Only',
                                                'PharmacyOnly' => 'Pharmacy Only',
                                                'RadiologyOnly' => 'Radiology Only',
                                            ];
                                            $displayType = $visit->visit_type ? ($typeLabels[$visit->visit_type] ?? $visit->visit_type) : '—';
                                            $color = $typeColors[$visit->visit_type ?? ''] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-light-{{ $color }}">{{ $displayType }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'active' => 'success',
                                                'completed' => 'secondary',
                                                'cancelled' => 'danger',
                                                'transferred' => 'warning'
                                            ];
                                            $color = $statusColors[$visit->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $color }}">{{ ucfirst($visit->status) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $priorityColors = [
                                                'routine' => 'secondary',
                                                'urgent' => 'warning',
                                                'critical' => 'danger'
                                            ];
                                            $color = $priorityColors[$visit->priority] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $color }}">{{ ucfirst($visit->priority) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $activeQueue = $visit->queues->whereIn('status', ['waiting', 'called', 'serving'])->first();
                                        @endphp
                                        @if($activeQueue)
                                            <div class="d-flex flex-column">
                                                <span class="text-dark fw-bold d-block fs-7">{{ $activeQueue->queue_type }}</span>
                                                <span class="text-muted fw-semibold d-block fs-8">
                                                    @if($activeQueue->status == 'waiting')
                                                        <span class="badge badge-light-warning">Waiting (Pos: {{ $activeQueue->position }})</span>
                                                    @elseif($activeQueue->status == 'called')
                                                        <span class="badge badge-light-info">Called</span>
                                                    @elseif($activeQueue->status == 'serving')
                                                        <span class="badge badge-light-success">Being Served</span>
                                                    @endif
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-muted">No active queue</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($visit->assignedDoctor)
                                            <div class="d-flex flex-column">
                                                <span class="text-dark fw-bold d-block fs-7">Dr. {{ $visit->assignedDoctor->first_name }} {{ $visit->assignedDoctor->last_name }}</span>
                                                @if($visit->assignedNurse)
                                                    <span class="text-muted fw-semibold d-block fs-8">Nurse: {{ $visit->assignedNurse->first_name }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown position-static">
                                            <button class="btn btn-sm btn-light btn-active-light-primary" type="button" data-bs-toggle="dropdown">
                                                Actions <i class="bi bi-chevron-down fs-8"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                                @can('view_walk_ins')
                                                <li><a class="dropdown-item" href="{{ route('walk-ins.show', $visit->id) }}">
                                                    <i class="bi bi-eye text-primary"></i> View Details
                                                </a></li>
                                                @endcan
                                                <li><a class="dropdown-item view-timeline" href="#" data-visit-id="{{ $visit->id }}">
                                                    <i class="bi bi-clock-history text-info"></i> View Timeline
                                                </a></li>
                                                @can('view_patients')
                                                <li><a class="dropdown-item" href="{{ route('patients.show', $visit->patient_id) }}">
                                                    <i class="bi bi-person text-success"></i> View Patient
                                                </a></li>
                                                @endcan
                                                @if($visit->consultation)
                                                @can('view_consultations')
                                                <li><a class="dropdown-item" href="{{ route('consultations.show', $visit->consultation->id) }}">
                                                    <i class="bi bi-clipboard-pulse text-warning"></i> View Consultation
                                                </a></li>
                                                @endcan
                                                @endif
                                                
                                                @if($visit->visit_type === 'LabOnly')
                                                <li><hr class="dropdown-divider"></li>
                                                @can('create_lab_requests')
                                                <li><a class="dropdown-item" href="{{ route('lab.create-from-walk-in', $visit->id) }}">
                                                    <i class="bi bi-clipboard-plus text-info"></i> Create Lab Request
                                                </a></li>
                                                @endcan
                                                @can('edit_walk_ins')
                                                <li><a class="dropdown-item" href="#" onclick="completeLabService({{ $visit->id }})">
                                                    <i class="bi bi-check-circle text-success"></i> Complete Lab Service
                                                </a></li>
                                                @endcan
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-10">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-inbox fs-3x text-gray-400 mb-3"></i>
                                            <span class="text-muted fs-5">No visits found for this date</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap mt-5">
                        <div class="text-muted">
                            Showing {{ $visits->firstItem() ?? 0 }} to {{ $visits->lastItem() ?? 0 }} of {{ $visits->total() }} visits
                        </div>
                        <div>
                            {{ $visits->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Timeline Modal -->
<div class="modal fade" id="timelineModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visit Timeline</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="timelineContent">
                <div class="text-center py-5">
                    <span class="spinner-border spinner-border-lg text-primary"></span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize search clear button visibility
    updateSearchClearVisibility();
    updateActiveFilters();

    // Search functionality with enhanced UX
    let searchTimeout;
    $('#searchInput').on('input', function() {
        updateSearchClearVisibility();
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            applyFilters();
        }, 500);
    });

    // Search clear functionality
    $('#searchClear').on('click', function() {
        $('#searchInput').val('').focus();
        updateSearchClearVisibility();
        applyFilters();
    });

    // Filter change handlers
    $('#dateFilter, #branchFilter, #visitTypeFilter, #statusFilter').on('change', function() {
        updateActiveFilters();
        applyFilters();
    });

    // Clear all filters
    $('#clearFiltersBtn').on('click', function() {
        $('#searchInput').val('');
        $('#dateFilter').val('{{ now()->toDateString() }}');
        $('#branchFilter').val('');
        $('#visitTypeFilter').val('all');
        $('#statusFilter').val('all');
        updateSearchClearVisibility();
        updateActiveFilters();
        applyFilters();
    });

    // Refresh button
    $('#refreshBtn').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.html();
        $btn.html('<i class="bi bi-arrow-clockwise me-1"></i>Refreshing...').prop('disabled', true);
        
        setTimeout(function() {
            window.location.reload();
        }, 500);
    });

    // Export button
    $('#exportBtn').on('click', function() {
        const params = new URLSearchParams();
        
        const search = $('#searchInput').val();
        if (search) params.append('search', search);
        
        const date = $('#dateFilter').val();
        if (date) params.append('date', date);
        
        const branch = $('#branchFilter').val();
        if (branch) params.append('branch_id', branch);
        
        const visitType = $('#visitTypeFilter').val();
        if (visitType && visitType !== 'all') params.append('visit_type', visitType);
        
        const status = $('#statusFilter').val();
        if (status && status !== 'all') params.append('status', status);
        
        window.open('{{ route("walk-ins.export") }}?' + params.toString(), '_blank');
    });

    // Update search clear button visibility
    function updateSearchClearVisibility() {
        const searchValue = $('#searchInput').val();
        if (searchValue && searchValue.length > 0) {
            $('#searchClear').show();
        } else {
            $('#searchClear').hide();
        }
    }

    // Update active filters display
    function updateActiveFilters() {
        const filters = [];
        
        const search = $('#searchInput').val();
        if (search) {
            filters.push({
                type: 'search',
                label: 'Search: "' + search + '"',
                value: search
            });
        }
        
        const date = $('#dateFilter').val();
        if (date && date !== '{{ now()->toDateString() }}') {
            filters.push({
                type: 'date',
                label: 'Date: ' + new Date(date).toLocaleDateString(),
                value: date
            });
        }
        
        const branch = $('#branchFilter').val();
        if (branch) {
            const branchText = $('#branchFilter option:selected').text();
            filters.push({
                type: 'branch',
                label: 'Branch: ' + branchText,
                value: branch
            });
        }
        
        const visitType = $('#visitTypeFilter').val();
        if (visitType && visitType !== 'all') {
            filters.push({
                type: 'visitType',
                label: 'Type: ' + visitType,
                value: visitType
            });
        }
        
        const status = $('#statusFilter').val();
        if (status && status !== 'all') {
            filters.push({
                type: 'status',
                label: 'Status: ' + status.charAt(0).toUpperCase() + status.slice(1),
                value: status
            });
        }
        
        if (filters.length > 0) {
            $('#activeFilters').show();
            let filterTagsHtml = '';
            filters.forEach(function(filter) {
                filterTagsHtml += `
                    <span class="filter-tag">
                        ${filter.label}
                        <span class="remove-tag" data-type="${filter.type}">×</span>
                    </span>
                `;
            });
            $('#filterTags').html(filterTagsHtml);
        } else {
            $('#activeFilters').hide();
        }
    }

    // Remove individual filter tags
    $(document).on('click', '.remove-tag', function() {
        const filterType = $(this).data('type');
        
        switch(filterType) {
            case 'search':
                $('#searchInput').val('');
                updateSearchClearVisibility();
                break;
            case 'date':
                $('#dateFilter').val('{{ now()->toDateString() }}');
                break;
            case 'branch':
                $('#branchFilter').val('');
                break;
            case 'visitType':
                $('#visitTypeFilter').val('all');
                break;
            case 'status':
                $('#statusFilter').val('all');
                break;
        }
        
        updateActiveFilters();
        applyFilters();
    });

    // Apply filters function
    function applyFilters() {
        const params = new URLSearchParams();
        
        const search = $('#searchInput').val();
        if (search) params.append('search', search);
        
        const date = $('#dateFilter').val();
        if (date) params.append('date', date);
        
        const branch = $('#branchFilter').val();
        if (branch) params.append('branch_id', branch);
        
        const visitType = $('#visitTypeFilter').val();
        if (visitType && visitType !== 'all') params.append('visit_type', visitType);
        
        const status = $('#statusFilter').val();
        if (status && status !== 'all') params.append('status', status);
        
        // Show loading state
        $('#loadingIndicator').show();
        $('#visitsTable').hide();
        
        // Add loading state to refresh button
        const $refreshBtn = $('#refreshBtn');
        const originalText = $refreshBtn.html();
        $refreshBtn.html('<i class="bi bi-arrow-clockwise me-1"></i>Loading...').prop('disabled', true);
        
        window.location.href = '{{ route("walk-ins.index") }}?' + params.toString();
    }

    // View timeline
    $(document).on('click', '.view-timeline', function(e) {
        e.preventDefault();
        const visitId = $(this).data('visit-id');
        
        $('#timelineModal').modal('show');
        $('#timelineContent').html('<div class="text-center py-5"><span class="spinner-border spinner-border-lg text-primary"></span></div>');
        
        $.ajax({
            url: `/walk-ins/${visitId}/timeline`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    let html = '<div class="timeline timeline-border-dashed">';
                    
                    response.data.forEach(function(item, index) {
                        html += `
                            <div class="timeline-item">
                                <div class="timeline-line"></div>
                                <div class="timeline-icon">
                                    <i class="bi ${item.icon} text-${item.color} fs-2"></i>
                                </div>
                                <div class="timeline-content mb-10">
                                    <div class="fw-bold text-gray-800 mb-2">${item.event}</div>
                                    <div class="text-muted fs-7">${item.description}</div>
                                    <div class="text-gray-600 fw-semibold fs-8 mt-1">${item.time_formatted || (typeof moment !== 'undefined' ? moment(item.time).format('DD MMM YYYY, HH:mm') : new Date(item.time).toLocaleString())}</div>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += '</div>';
                    $('#timelineContent').html(html);
                }
            },
            error: function() {
                $('#timelineContent').html('<div class="alert alert-danger">Failed to load timeline</div>');
            }
        });
    });

    // Auto-refresh every 2 minutes
    setInterval(function() {
        // Refresh statistics via AJAX
        const date = $('#dateFilter').val();
        const branchId = $('#branchFilter').val();
        
        $.ajax({
            url: '{{ route("walk-ins.statistics") }}',
            data: { date, branch_id: branchId },
            success: function(response) {
                if (response.success) {
                    // Update statistics on page
                    console.log('Statistics updated');
                }
            }
        });
    }, 120000); // 2 minutes

    // Complete lab service function
    window.completeLabService = function(visitId) {
        if (confirm('Are you sure you want to complete this lab service? This will mark the visit as completed.')) {
            $.ajax({
                url: `/visits/${visitId}/complete-lab-service`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        const alert = `
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                ${response.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `;
                        $('body').prepend(alert);
                        
                        // Reload the page to update the status
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    alert('Error: ' + (response?.message || 'Something went wrong'));
                }
            });
        }
    };
});
</script>
@endpush

