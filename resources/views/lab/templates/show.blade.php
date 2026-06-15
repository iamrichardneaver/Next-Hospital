@extends('layouts.app')

@section('title', 'Template Details')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-1" style="color: #1e3a5f;">Template Details</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('lab.templates') }}">Templates</a></li>
                <li class="breadcrumb-item active">{{ $template->template_name }}</li>
            </ol>
        </nav>
    </div>
    
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    <!-- Template Info Card -->
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-dark">{{ $template->template_name }}</h5>
                <div>
                    @can('edit_lab_requests')
                    <a href="{{ route('lab.templates.edit', $template) }}" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Template Code:</th>
                            <td>{{ $template->template_code }}</td>
                        </tr>
                        <tr>
                            <th>Category:</th>
                            <td>
                                @if($template->category && is_object($template->category))
                                    <span class="badge bg-info">{{ $template->category->name }}</span>
                                @elseif($template->category)
                                    <span class="badge bg-info">{{ $template->category }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Subcategory:</th>
                            <td>{{ $template->subcategory ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Template Type:</th>
                            <td>
                                @php
                                    $typeColors = ['quantitative' => 'primary', 'qualitative' => 'success', 'narrative' => 'warning', 'combined' => 'info'];
                                @endphp
                                <span class="badge bg-{{ $typeColors[$template->template_type] ?? 'secondary' }}">
                                    {{ ucfirst($template->template_type) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Specimen Type:</th>
                            <td>{{ $template->specimen_type }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Methodology:</th>
                            <td>{{ $template->methodology ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Equipment:</th>
                            <td>{{ $template->equipment_required ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>TAT (R/U/S):</th>
                            <td>{{ $template->routine_tat_hours }}/{{ $template->urgent_tat_hours }}/{{ $template->stat_tat_hours }} hours</td>
                        </tr>
                        <tr>
                            <th>Cost:</th>
                            <td>GHS {{ number_format($template->cost, 2) ?? '0.00' }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @if($template->nhis_covered)
                                    <span class="badge bg-primary">NHIS</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            @if($template->description)
            <div class="mt-2">
                <strong>Description:</strong>
                <p>{{ $template->description }}</p>
            </div>
            @endif
            
            @if($template->template_content && ($template->test_type === 'narrative' || $template->test_type === 'combined'))
            <div class="mt-3">
                <strong>Narrative Template Content:</strong>
                <div class="border p-3 bg-light mt-2">
                    {!! $template->template_content !!}
                </div>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Parameters Card -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white fw-bold">
                    <i class="bi bi-list-ul text-white"></i> 
                    <span class="text-white">Parameters</span> 
                    <span class="badge bg-primary ms-2">{{ $template->parameters->count() }}</span>
                </h5>
                <div class="d-flex gap-2">
                    @if($template->parameters->where('data_type', 'numeric')->count() > 0)
                    {{-- Only show Toggle All Ranges button if there are numeric parameters --}}
                    <button class="btn btn-sm btn-outline-light" onclick="toggleAllRanges()" id="toggleAllBtn">
                        <i class="bi bi-chevron-down"></i> Toggle All Ranges
                    </button>
                    @endif
                    @can('create_lab_requests')
                    <a href="{{ route('lab.parameters.create', $template->id) }}" class="btn btn-sm btn-success">
                        <i class="bi bi-plus-circle"></i> Add Parameter
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($template->parameters->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="10%">
                                <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th width="15%">Code</th>
                            <th width="25%">Parameter Name</th>
                            <th width="15%">Type</th>
                            <th width="10%">Unit</th>
                            <th width="15%">Ranges/Options</th>
                            <th width="10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($template->parameters->sortBy('sort_order') as $param)
                        <tr class="parameter-row" data-param-id="{{ $param->id }}">
                            <td>
                                <input type="checkbox" class="form-check-input param-checkbox" value="{{ $param->id }}">
                            </td>
                            <td>
                                <code class="text-primary fw-bold">{{ $param->parameter_code }}</code>
                                @if($param->is_active)
                                    <span class="badge bg-success badge-sm">Active</span>
                                @else
                                    <span class="badge bg-secondary badge-sm">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <strong>{{ $param->parameter_name }}</strong>
                                    @if($param->description)
                                        <small class="text-muted">{{ Str::limit($param->description, 50) }}</small>
                                    @endif
                                    <div class="mt-1">
                                        @if($param->is_required)
                                            <span class="badge bg-danger badge-sm me-1">Required</span>
                                        @endif
                                        @if($param->is_critical)
                                            <span class="badge bg-warning badge-sm me-1">Critical</span>
                                        @endif
                                        @if($param->allows_delta_check)
                                            <span class="badge bg-info badge-sm">Delta Check</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    @if($param->data_type === 'numeric')
                                        {{-- Quantitative: Show Numeric type --}}
                                        <span class="badge bg-primary">Numeric</span>
                                        @if($param->decimal_places > 0)
                                            <small class="text-muted mt-1">Decimal Places: {{ $param->decimal_places }}</small>
                                        @endif
                                    @else
                                        {{-- Qualitative: Show Input Type (Select/Radio/Checkbox) --}}
                                        <span class="badge bg-success">
                                            {{ $param->input_type === 'select' ? 'Dropdown' : ($param->input_type === 'radio' ? 'Radio Buttons' : 'Checkboxes') }}
                                        </span>
                                        <small class="text-muted mt-1">(Qualitative)</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($param->data_type === 'numeric')
                                    @if($param->unit)
                                        <span class="badge bg-secondary">{{ $param->unit }}</span>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary" onclick="addUnit({{ $param->id }})">
                                            <i class="bi bi-plus"></i> Add Unit
                                        </button>
                                    @endif
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($param->data_type === 'numeric')
                                    {{-- Quantitative: Show Reference Ranges --}}
                                    <div class="d-flex flex-column">
                                        <span class="badge bg-primary">{{ $param->referenceRanges->count() }} range(s)</span>
                                        @if($param->referenceRanges->count() > 0)
                                            <button class="btn btn-sm btn-link p-0 mt-1" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#ranges_{{ $param->id }}"
                                                    onclick="toggleRangeIcon(this)">
                                                <i class="bi bi-chevron-down"></i> View Ranges
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-success mt-1" 
                                                    onclick="addReferenceRange({{ $param->id }})">
                                                <i class="bi bi-plus"></i> Add Range
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    {{-- Qualitative: Show Options --}}
                                    <div class="d-flex flex-column">
                                        @if($param->input_options && count($param->input_options) > 0)
                                            <span class="badge bg-success">{{ count($param->input_options) }} option(s)</span>
                                            <button class="btn btn-sm btn-link p-0 mt-1" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#options_{{ $param->id }}"
                                                    onclick="toggleRangeIcon(this)">
                                                <i class="bi bi-chevron-down"></i> View Options
                                            </button>
                                        @else
                                            <span class="text-muted">No options</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        @can('edit_lab_requests')
                                        @if($param->data_type === 'numeric')
                                        {{-- Reference Range option only for Quantitative parameters --}}
                                        <li>
                                            <a class="dropdown-item" href="{{ route('lab.reference-ranges.create', $param->id) }}">
                                                <i class="bi bi-plus-circle me-2"></i>Add Reference Range
                                            </a>
                                        </li>
                                        @endif
                                        @endcan
                                        @can('edit_lab_requests')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('lab.parameters.edit', $param) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit Parameter
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item" onclick="toggleParameterStatus({{ $param->id }}, {{ $param->is_active ? 'false' : 'true' }})">
                                                <i class="bi bi-{{ $param->is_active ? 'pause' : 'play' }}-circle me-2"></i>
                                                {{ $param->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </li>
                                        @endcan
                                        @can('delete_lab_requests')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('lab.parameters.destroy', $param) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this parameter? This action cannot be undone.');">
                                                    <i class="bi bi-trash me-2"></i>Delete Parameter
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        
                        {{-- Collapsible section for Reference Ranges (Quantitative only) --}}
                        @if($param->data_type === 'numeric' && $param->referenceRanges->count() > 0)
                        <tr class="collapse range-detail-row" id="ranges_{{ $param->id }}">
                            <td colspan="7" class="bg-light">
                                <div class="p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0 text-dark">
                                            <i class="bi bi-graph-up"></i> Reference Ranges for {{ $param->parameter_name }}
                                        </h6>
                                        <button class="btn btn-sm btn-success" onclick="addReferenceRange({{ $param->id }})">
                                            <i class="bi bi-plus-circle"></i> Add New Range
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-secondary">
                                                <tr>
                                                    <th>Age Group</th>
                                                    <th>Gender</th>
                                                    <th>Range</th>
                                                    <th>Unit</th>
                                                    <th>Source</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($param->referenceRanges->sortBy('age_group') as $range)
                                                <tr class="{{ $range->is_active ? '' : 'table-secondary' }}">
                                                    <td>
                                                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $range->age_group)) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">{{ ucfirst($range->gender) }}</span>
                                                        @if($range->is_pregnant)
                                                            <span class="badge bg-pink badge-sm">Pregnant</span>
                                                        @endif
                                                        @if($range->pregnancy_trimester)
                                                            <small class="text-muted">({{ ucfirst($range->pregnancy_trimester) }} Trimester)</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <code class="text-success fw-bold">{{ $range->getFormattedRange() }}</code>
                                                        @if($range->notes)
                                                            <br><small class="text-muted">{{ $range->notes }}</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($range->unit)
                                                            <span class="badge bg-primary">{{ $range->unit }}</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">{{ $range->source ?? 'Custom' }}</small>
                                                        @if($range->reference)
                                                            <br><small class="text-muted">{{ $range->reference }}</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ $range->is_active ? 'success' : 'secondary' }}">
                                                            {{ $range->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('lab.reference-ranges.edit', $range) }}" 
                                                               class="btn btn-sm btn-outline-primary" title="Edit Range">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <button class="btn btn-sm btn-outline-{{ $range->is_active ? 'warning' : 'success' }}" 
                                                                    onclick="toggleRangeStatus({{ $range->id }}, {{ $range->is_active ? 'false' : 'true' }})"
                                                                    title="{{ $range->is_active ? 'Deactivate' : 'Activate' }} Range">
                                                                <i class="bi bi-{{ $range->is_active ? 'pause' : 'play' }}"></i>
                                                            </button>
                                                            <form action="{{ route('lab.reference-ranges.destroy', $range) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                        onclick="return confirm('Are you sure you want to delete this reference range?');"
                                                                        title="Delete Range">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif
                        
                        {{-- Collapsible section for Options (Qualitative only) --}}
                        @if($param->data_type !== 'numeric' && $param->input_options && count($param->input_options) > 0)
                        <tr class="collapse range-detail-row" id="options_{{ $param->id }}">
                            <td colspan="7" class="bg-light">
                                <div class="p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0 text-dark">
                                            <i class="bi bi-list-check"></i> Options for {{ $param->parameter_name }}
                                        </h6>
                                    </div>
                                    <div class="row">
                                        @foreach($param->input_options as $option)
                                        <div class="col-md-3 mb-2">
                                            <div class="alert alert-success mb-0 py-2">
                                                <i class="bi bi-check-circle"></i> <strong>{{ $option }}</strong>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle"></i> 
                                            Input Type: <strong>{{ ucfirst($param->input_type) }}</strong>
                                            ({{ $param->input_type === 'select' ? 'Dropdown' : ($param->input_type === 'radio' ? 'Radio Buttons' : 'Checkboxes') }})
                                        </small>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-list-ul display-1 text-muted"></i>
                </div>
                <h5 class="text-muted">No Parameters Defined</h5>
                <p class="text-secondary">This template doesn't have any parameters yet. Add parameters to define what data will be collected for this lab test.</p>
                @can('create_lab_requests')
                <a href="{{ route('lab.parameters.create', $template->id) }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle"></i> Add First Parameter
                </a>
                @endcan
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
});

// Toggle all reference ranges
function toggleAllRanges() {
    const toggleBtn = document.getElementById('toggleAllBtn');
    const collapseElements = document.querySelectorAll('.range-detail-row');
    const isCollapsed = collapseElements[0] && collapseElements[0].classList.contains('show');
    
    collapseElements.forEach(element => {
        const collapse = new bootstrap.Collapse(element, {
            toggle: !isCollapsed
        });
    });
    
    // Update button text and icon
    const icon = toggleBtn.querySelector('i');
    if (isCollapsed) {
        toggleBtn.innerHTML = '<i class="bi bi-chevron-down"></i> Toggle All Ranges';
    } else {
        toggleBtn.innerHTML = '<i class="bi bi-chevron-up"></i> Hide All Ranges';
    }
}

// Toggle select all checkboxes
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const paramCheckboxes = document.querySelectorAll('.param-checkbox');
    
    paramCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Toggle range icon when collapse is toggled
function toggleRangeIcon(button) {
    const icon = button.querySelector('i');
    const targetId = button.getAttribute('data-bs-target');
    const targetElement = document.querySelector(targetId);
    
    if (targetElement) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    if (targetElement.classList.contains('show')) {
                        icon.className = 'bi bi-chevron-up';
                        button.innerHTML = '<i class="bi bi-chevron-up"></i> Hide Ranges';
                    } else {
                        icon.className = 'bi bi-chevron-down';
                        button.innerHTML = '<i class="bi bi-chevron-down"></i> View Ranges';
                    }
                }
            });
        });
        
        observer.observe(targetElement, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
}

// Add unit to parameter
function addUnit(parameterId) {
    const unit = prompt('Enter the unit for this parameter (e.g., mg/dL, g/L, %):');
    if (unit && unit.trim()) {
        // Make AJAX request to update unit
        fetch(`/lab/management/parameters/${parameterId}/update-unit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                unit: unit.trim()
            })
        })
        .then(response => {
            // Check if response is ok and is JSON
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating unit: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating unit: ' + error.message + '. Please try again.');
        });
    }
}

// Add reference range
function addReferenceRange(parameterId) {
    window.location.href = `/lab/management/parameters/${parameterId}/ranges/create`;
}

// Toggle parameter status
function toggleParameterStatus(parameterId, newStatus) {
    fetch(`/lab/management/parameters/${parameterId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            is_active: newStatus === 'true'
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Response is not JSON');
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating parameter status: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating parameter status: ' + error.message + '. Please try again.');
    });
}

// Toggle reference range status
function toggleRangeStatus(rangeId, newStatus) {
    fetch(`/lab/management/ranges/${rangeId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            is_active: newStatus === 'true'
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Response is not JSON');
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating range status: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating range status: ' + error.message + '. Please try again.');
    });
}

// Bulk actions for selected parameters
function bulkAction(action) {
    const selectedCheckboxes = document.querySelectorAll('.param-checkbox:checked');
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        alert('Please select at least one parameter.');
        return;
    }
    
    if (confirm(`Are you sure you want to ${action} ${selectedIds.length} parameter(s)?`)) {
        fetch('/lab/management/parameters/bulk-action', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                action: action,
                parameter_ids: selectedIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error performing bulk action: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error performing bulk action. Please try again.');
        });
    }
}
</script>
@endpush

