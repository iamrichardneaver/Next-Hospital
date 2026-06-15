@extends('layouts.app')

@section('title', 'Laboratory Archive')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }
    .filter-badge {
        display: inline-block;
        margin: 2px;
        padding: 4px 8px;
        background: #e3f2fd;
        border-radius: 12px;
        font-size: 12px;
    }
    .filter-badge .remove-filter {
        margin-left: 5px;
        cursor: pointer;
        color: #d32f2f;
    }
    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    .loading-overlay.show {
        display: flex;
    }
</style>
@endpush

@section('content')
<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-archive"></i> Laboratory Archive
            </h1>
            <p class="text-secondary mb-0">Search and view historical laboratory test results</p>
        </div>
        <div>
            <button type="button" class="btn btn-success me-2" onclick="exportResults()">
                <i class="bi bi-file-excel"></i> Export Results
            </button>
            <a href="{{ route('lab.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Lab
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-clipboard-data"></i>
                </div>
                <div class="stat-label">Total Requests</div>
                <div class="stat-value">{{ number_format($statistics['total_requests']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-calendar-day"></i>
                </div>
                <div class="stat-label">Today's Tests</div>
                <div class="stat-value">{{ number_format($statistics['today_requests']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-label">Abnormal Results</div>
                <div class="stat-value">{{ number_format($statistics['abnormal_results']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-shield-exclamation"></i>
                </div>
                <div class="stat-label">Critical Alerts</div>
                <div class="stat-value">{{ number_format($statistics['critical_alerts']) }}</div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Form -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-funnel"></i> Search & Filter
            </h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="toggleFilters">
                <i class="bi bi-chevron-down"></i> Toggle Filters
            </button>
        </div>
        <div class="card-body" id="filterSection">
            <!-- Active Filters Display -->
            @if(request()->hasAny(['search', 'date_from', 'date_to', 'patient_id', 'category_id', 'template_id', 'doctor_id', 'result_status', 'abnormal_flag']))
            <div class="mb-3">
                <strong class="me-2">Active Filters:</strong>
                @if(request('search'))
                    <span class="filter-badge">
                        Search: "{{ request('search') }}"
                        <i class="bi bi-x remove-filter" onclick="removeFilter('search')"></i>
                    </span>
                @endif
                @if(request('date_from'))
                    <span class="filter-badge">
                        From: {{ request('date_from') }}
                        <i class="bi bi-x remove-filter" onclick="removeFilter('date_from')"></i>
                    </span>
                @endif
                @if(request('date_to'))
                    <span class="filter-badge">
                        To: {{ request('date_to') }}
                        <i class="bi bi-x remove-filter" onclick="removeFilter('date_to')"></i>
                    </span>
                @endif
                @if(request('patient_id'))
                    <span class="filter-badge">
                        Patient: {{ $patients->where('id', request('patient_id'))->first()->full_name ?? 'Selected' }}
                        <i class="bi bi-x remove-filter" onclick="removeFilter('patient_id')"></i>
                    </span>
                @endif
                @if(request('category_id'))
                    <span class="filter-badge">
                        Category: {{ $categories->where('id', request('category_id'))->first()->name ?? 'Selected' }}
                        <i class="bi bi-x remove-filter" onclick="removeFilter('category_id')"></i>
                    </span>
                @endif
                @if(request('template_id'))
                    <span class="filter-badge">
                        Template: {{ $templates->where('id', request('template_id'))->first()->template_name ?? 'Selected' }}
                        <i class="bi bi-x remove-filter" onclick="removeFilter('template_id')"></i>
                    </span>
                @endif
                @if(request('doctor_id'))
                    <span class="filter-badge">
                        Doctor: {{ $doctors->where('id', request('doctor_id'))->first()->first_name ?? 'Selected' }}
                        <i class="bi bi-x remove-filter" onclick="removeFilter('doctor_id')"></i>
                    </span>
                @endif
                @if(request('result_status'))
                    <span class="filter-badge">
                        Status: {{ ucfirst(request('result_status')) }}
                        <i class="bi bi-x remove-filter" onclick="removeFilter('result_status')"></i>
                    </span>
                @endif
                @if(request('abnormal_flag'))
                    <span class="filter-badge">
                        Flag: {{ request('abnormal_flag') }}
                        <i class="bi bi-x remove-filter" onclick="removeFilter('abnormal_flag')"></i>
                    </span>
                @endif
            </div>
            @endif

            <form method="GET" action="{{ route('lab.archive.index') }}" id="searchForm">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            <i class="bi bi-search"></i> Search
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" id="searchInput"
                               value="{{ request('search') }}" 
                                   placeholder="Patient name, request #, test name..."
                                   autocomplete="off">
                            @if(request('search'))
                            <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()">
                                <i class="bi bi-x"></i>
                            </button>
                            @endif
                        </div>
                        <small class="text-muted">Search by patient name, number, request #, or test name</small>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">
                            <i class="bi bi-calendar"></i> Date From
                        </label>
                        <input type="date" class="form-control" name="date_from" 
                               value="{{ request('date_from') }}"
                               max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">
                            <i class="bi bi-calendar-check"></i> Date To
                        </label>
                        <input type="date" class="form-control" name="date_to" 
                               value="{{ request('date_to') }}"
                               max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">
                            <i class="bi bi-person"></i> Patient
                        </label>
                        <select class="form-select select2" name="patient_id" id="patientSelect">
                            <option value="">All Patients</option>
                            @foreach($patients as $patient)
                            <option value="{{ $patient->id }}" 
                                    {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                                {{ $patient->patient_number }} - {{ $patient->full_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">
                            <i class="bi bi-folder"></i> Test Category
                        </label>
                        <select class="form-select select2" name="category_id" id="categorySelect">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" 
                                    {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">
                            <i class="bi bi-file-medical"></i> Test Template
                        </label>
                        <select class="form-select select2" name="template_id" id="templateSelect">
                            <option value="">All Templates</option>
                            @foreach($templates as $template)
                            @php
                                // Get category name - handle both relationship and column
                                $categoryName = 'N/A';
                                if ($template->relationLoaded('category') && $template->getRelation('category')) {
                                    $categoryName = $template->getRelation('category')->name ?? 'N/A';
                                } elseif ($template->category_id) {
                                    // Load relationship if not already loaded
                                    $categoryObj = $template->category;
                                    if (is_object($categoryObj)) {
                                        $categoryName = $categoryObj->name ?? 'N/A';
                                    } else {
                                        // Fallback to column value if relationship not available
                                        $categoryName = is_string($template->category) ? $template->category : 'N/A';
                                    }
                                } elseif (is_string($template->category)) {
                                    $categoryName = $template->category;
                                }
                            @endphp
                            <option value="{{ $template->id }}" 
                                    {{ request('template_id') == $template->id ? 'selected' : '' }}
                                    data-category="{{ $categoryName }}">
                                {{ $template->template_name }}
                                @if($categoryName && $categoryName !== 'N/A')
                                    ({{ $categoryName }})
                                @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">
                            <i class="bi bi-person-badge"></i> Doctor
                        </label>
                        <select class="form-select select2" name="doctor_id" id="doctorSelect">
                            <option value="">All Doctors</option>
                            @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" 
                                    {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">
                            <i class="bi bi-clipboard-check"></i> Result Status
                        </label>
                        <select class="form-select" name="result_status" id="resultStatusSelect">
                            <option value="">All Status</option>
                            <option value="normal" {{ request('result_status') == 'normal' ? 'selected' : '' }}>
                                ✓ Normal
                            </option>
                            <option value="abnormal" {{ request('result_status') == 'abnormal' ? 'selected' : '' }}>
                                ⚠ Abnormal
                            </option>
                            <option value="critical" {{ request('result_status') == 'critical' ? 'selected' : '' }}>
                                ⚠️ Critical
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">
                            <i class="bi bi-exclamation-triangle"></i> Abnormal Flag
                        </label>
                        <select class="form-select" name="abnormal_flag" id="abnormalFlagSelect">
                            <option value="">All Flags</option>
                            <option value="H" {{ request('abnormal_flag') == 'H' ? 'selected' : '' }}>↑ High (H)</option>
                            <option value="L" {{ request('abnormal_flag') == 'L' ? 'selected' : '' }}>↓ Low (L)</option>
                            <option value="CRITICAL" {{ request('abnormal_flag') == 'CRITICAL' ? 'selected' : '' }}>🔴 Critical</option>
                            <option value="PANIC" {{ request('abnormal_flag') == 'PANIC' ? 'selected' : '' }}>⚠️ Panic</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('lab.archive.index') }}" class="btn btn-outline-secondary flex-fill">
                            <i class="bi bi-x-circle"></i> Clear All
                        </a>
                        <button type="button" class="btn btn-outline-info" onclick="saveFilterPreset()" title="Save Filter Preset">
                            <i class="bi bi-bookmark"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Lab Requests Archive</h6>
            <span class="badge bg-primary">{{ $labRequests->total() }} results found</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Request #</th>
                            <th>Patient</th>
                            <th>Test</th>
                            <th>Date</th>
                            <th>Doctor</th>
                            <th>Results</th>
                            <th>Status</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($labRequests as $labRequest)
                        <tr>
                            <td>
                                <strong>{{ $labRequest->request_number }}</strong>
                            </td>
                            <td>
                                @if($labRequest->patient)
                                    <div>
                                        <strong>{{ $labRequest->patient->full_name }}</strong><br>
                                        <small class="text-muted">{{ $labRequest->patient->patient_number }}</small>
                                    </div>
                                @else
                                    <div>
                                        <strong class="text-danger">Patient Not Found</strong><br>
                                        <small class="text-muted">ID: {{ $labRequest->patient_id ?? 'N/A' }}</small>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($labRequest->template)
                                    <div>
                                        <strong>{{ $labRequest->template->template_name }}</strong><br>
                                        <small class="text-muted">{{ $labRequest->template->category }}</small>
                                    </div>
                                @else
                                    <span class="text-muted">No template</span>
                                @endif
                            </td>
                            <td>
                                @if($labRequest->completed_at)
                                    <div>
                                        {{ $labRequest->completed_at->format('M d, Y') }}<br>
                                        <small class="text-muted">{{ $labRequest->completed_at->format('h:i A') }}</small>
                                    </div>
                                @else
                                    <span class="text-muted">Not recorded</span>
                                @endif
                            </td>
                            <td>
                                @if($labRequest->doctor)
                                    {{ $labRequest->doctor->first_name }} {{ $labRequest->doctor->last_name }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($labRequest->results->count() > 0)
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($labRequest->results->take(3) as $result)
                                            <span class="badge badge-sm {{ $result->getStatusBadgeClass() }}">
                                                {{ $result->abnormal_flag ?: 'N' }}
                                            </span>
                                        @endforeach
                                        @if($labRequest->results->count() > 3)
                                            <span class="badge badge-sm bg-secondary">
                                                +{{ $labRequest->results->count() - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">No results</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $hasCritical = $labRequest->results->contains('result_status', 'critical');
                                    $hasAbnormal = $labRequest->results->contains('result_status', 'abnormal');
                                @endphp
                                @if($hasCritical)
                                    <span class="badge bg-danger">Critical</span>
                                @elseif($hasAbnormal)
                                    <span class="badge bg-warning">Abnormal</span>
                                @else
                                    <span class="badge bg-success">Normal</span>
                                @endif
                            </td>
                            <td class="position-static">
                                <div class="dropdown position-static">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow">
                                        <a class="dropdown-item" href="{{ route('lab.show', $labRequest) }}">
                                            <i class="bi bi-eye"></i> View Details
                                        </a>
                                        @if($labRequest->patient)
                                        <a class="dropdown-item" href="{{ route('lab.archive.patient-history', $labRequest->patient) }}">
                                            <i class="bi bi-person-lines-fill"></i> Patient History
                                        </a>
                                        @endif
                                        @if($labRequest->results->count() > 0)
                                        <a class="dropdown-item" href="{{ route('lab.generate-pdf', $labRequest) }}">
                                            <i class="bi bi-file-pdf"></i> Download PDF
                                        </a>
                                        @endif
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#" onclick="compareResults({{ $labRequest->id }})">
                                            <i class="bi bi-arrow-left-right"></i> Compare
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-search display-4"></i>
                                    <p class="mt-2">No lab requests found matching your criteria.</p>
                                    <a href="{{ route('lab.archive.index') }}" class="btn btn-outline-primary btn-sm">
                                        Clear filters
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($labRequests->hasPages())
        <div class="card-footer">
            {{ $labRequests->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Compare Results Modal -->
<div class="modal fade" id="compareModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Compare Lab Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="GET" action="{{ route('lab.archive.compare-results') }}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">First Lab Request</label>
                            <select class="form-select" name="request1" required>
                                <option value="">Select first request</option>
                                @foreach($labRequests as $request)
                                <option value="{{ $request->id }}">{{ $request->request_number }} - {{ $request->patient ? $request->patient->full_name : 'Patient Not Found' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Second Lab Request</label>
                            <select class="form-select" name="request2" required>
                                <option value="">Select second request</option>
                                @foreach($labRequests as $request)
                                <option value="{{ $request->id }}">{{ $request->request_number }} - {{ $request->patient ? $request->patient->full_name : 'Patient Not Found' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Compare</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// Initialize Select2 for searchable dropdowns
function initializeSelect2() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder') || 'Select an option';
        },
        allowClear: true
    });
}

// Debounced search function
let searchTimeout;
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        document.getElementById('searchForm').submit();
    }, 800); // Wait 800ms after user stops typing
}

// Compare Results Modal
function compareResults(requestId) {
    document.querySelector('select[name="request1"]').value = requestId;
    new bootstrap.Modal(document.getElementById('compareModal')).show();
}

// Remove individual filter
function removeFilter(filterName) {
    const form = document.getElementById('searchForm');
    const input = form.querySelector(`[name="${filterName}"]`);
    if (input) {
        input.value = '';
        showLoading();
        form.submit();
    }
}

// Clear search
function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('searchForm').submit();
}

// Toggle filter section
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    initializeSelect2();
    
    const toggleBtn = document.getElementById('toggleFilters');
    const filterSection = document.getElementById('filterSection');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const isVisible = filterSection.style.display !== 'none';
            filterSection.style.display = isVisible ? 'none' : 'block';
            const icon = toggleBtn.querySelector('i');
            icon.className = isVisible ? 'bi bi-chevron-right' : 'bi bi-chevron-down';
        });
    }
    
    // Auto-submit form on select change
    const form = document.getElementById('searchForm');
    const selects = form.querySelectorAll('select');
    
    selects.forEach(select => {
        select.addEventListener('change', function() {
            showLoading();
            form.submit();
        });
    });
    
    // Debounced search on text input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounceSearch);
    }
    
    // Auto-submit form on date change
    const dateInputs = form.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.addEventListener('change', function() {
            showLoading();
            form.submit();
        });
    });
});

// Show loading overlay
function showLoading() {
    document.getElementById('loadingOverlay').classList.add('show');
}

// Export results function
function exportResults() {
    // Get current filter parameters
    const params = new URLSearchParams(window.location.search);
    
    // Show loading
    showLoading();
    
    // Redirect to export endpoint (to be implemented)
    const exportUrl = '{{ route("lab.archive.index") }}?' + params.toString() + '&export=excel';
    
    // Create temporary iframe for download
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = exportUrl;
    document.body.appendChild(iframe);
    
    // Hide loading after 2 seconds
    setTimeout(() => {
        document.getElementById('loadingOverlay').classList.remove('show');
        document.body.removeChild(iframe);
    }, 2000);
}

// Save filter preset
function saveFilterPreset() {
    const params = new URLSearchParams(window.location.search);
    const filterParams = {};
    
    params.forEach((value, key) => {
        if (value) filterParams[key] = value;
    });
    
    if (Object.keys(filterParams).length === 0) {
        alert('No active filters to save');
        return;
    }
    
    const presetName = prompt('Enter a name for this filter preset:');
    if (presetName) {
        // Save to localStorage
        const presets = JSON.parse(localStorage.getItem('labArchiveFilters') || '{}');
        presets[presetName] = filterParams;
        localStorage.setItem('labArchiveFilters', JSON.stringify(presets));
        
        alert('Filter preset "' + presetName + '" saved successfully!');
    }
}

// Load filter preset
function loadFilterPreset() {
    const presets = JSON.parse(localStorage.getItem('labArchiveFilters') || '{}');
    const presetNames = Object.keys(presets);
    
    if (presetNames.length === 0) {
        alert('No saved filter presets found');
        return;
    }
    
    const presetName = prompt('Available presets:\n' + presetNames.join('\n') + '\n\nEnter preset name to load:');
    
    if (presetName && presets[presetName]) {
        const params = new URLSearchParams(presets[presetName]);
        window.location.href = '{{ route("lab.archive.index") }}?' + params.toString();
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K for quick search focus
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
    
    // Escape to clear search
    if (e.key === 'Escape' && document.getElementById('searchInput') === document.activeElement) {
        clearSearch();
    }
});
</script>
@endpush
@endsection
