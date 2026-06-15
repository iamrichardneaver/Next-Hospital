@extends('layouts.app')

@section('title', 'Edit Test Template')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-1" style="color: #1e3a5f;">Edit Test Template</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('lab.templates') }}">Templates</a></li>
                <li class="breadcrumb-item"><a href="{{ route('lab.templates.show', $template) }}">{{ $template->template_name }}</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Validation Errors</h5>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 text-dark">Edit Template: {{ $template->template_name }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('lab.templates.update', $template) }}" method="POST" id="editTemplateForm">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="template_code" class="form-label">Template Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('template_code') is-invalid @enderror" 
                               id="template_code" name="template_code" value="{{ old('template_code', $template->template_code) }}" required>
                        @error('template_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="template_name" class="form-label">Template Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('template_name') is-invalid @enderror" 
                               id="template_name" name="template_name" value="{{ old('template_name', $template->template_name) }}" required>
                        @error('template_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select @error('category_id') is-invalid @enderror" 
                                id="category_id" name="category_id">
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                    {{ old('category_id', $template->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="category" class="form-label">Category Text <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('category') is-invalid @enderror" 
                               id="category" name="category" value="{{ old('category', $template->category) }}" required>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="subcategory" class="form-label">Subcategory (Test Type)</label>
                        <select class="form-select @error('subcategory') is-invalid @enderror" 
                                id="subcategory" name="subcategory">
                            <option value="">-- Select a category first --</option>
                        </select>
                        <small class="text-muted">Select a category above to load test types (subcategories).</small>
                        @error('subcategory')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="template_type" class="form-label">Template Type <span class="text-danger">*</span></label>
                        <select class="form-select @error('template_type') is-invalid @enderror" 
                                id="template_type" name="template_type" required>
                            <option value="">-- Select Template Type --</option>
                            <option value="quantitative" {{ old('template_type', $template->template_type) == 'quantitative' ? 'selected' : '' }}>Quantitative</option>
                            <option value="qualitative" {{ old('template_type', $template->template_type) == 'qualitative' ? 'selected' : '' }}>Qualitative</option>
                            <option value="narrative" {{ old('template_type', $template->template_type) == 'narrative' ? 'selected' : '' }}>Narrative</option>
                            <option value="combined" {{ old('template_type', $template->template_type) == 'combined' ? 'selected' : '' }}>Combined</option>
                        </select>
                        @error('template_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" name="description" rows="2">{{ old('description', $template->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Quantitative Parameters Section -->
                <div class="mb-4" id="quantitative_section" style="display: none;">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-123"></i> Quantitative Parameters</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <strong><i class="bi bi-info-circle"></i> Quantitative Tests:</strong> 
                                Add numeric parameters with units, reference ranges, and critical values.
                            </div>
                            
                            <div id="quantitative_parameters_container">
                                @if($template->parameters->where('data_type', 'numeric')->count() > 0)
                                    @foreach($template->parameters->where('data_type', 'numeric') as $index => $param)
                                        <div class="card mb-3 border-primary" id="quantitative_param_{{ $index + 1 }}">
                                            <div class="card-header bg-light">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">Parameter {{ $index + 1 }}</h6>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuantitativeParameter({{ $index + 1 }})">
                                                        <i class="bi bi-trash"></i> Remove
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Parameter Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][parameter_name]" 
                                                               value="{{ $param->parameter_name }}" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Parameter Code</label>
                                                        <input type="text" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][parameter_code]" 
                                                               value="{{ $param->parameter_code }}">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Unit</label>
                                                        <input type="text" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][unit]" 
                                                               value="{{ $param->unit }}">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Decimal Places</label>
                                                        <input type="number" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][decimal_places]" 
                                                               value="{{ $param->decimal_places }}" min="0" max="4">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Sort Order</label>
                                                        <input type="number" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][sort_order]" 
                                                               value="{{ $param->sort_order }}" min="1">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <label class="form-label">Reference Ranges by Demographics</label>
                                                        <div class="alert alert-info">
                                                            <small><i class="bi bi-info-circle"></i> Add reference ranges for different patient demographics. At least one demographic group is required.</small>
                                                        </div>
                                                        <div id="reference_ranges_{{ $index + 1 }}">
                                                            @if($param->reference_ranges && is_array($param->reference_ranges))
                                                                @foreach($param->reference_ranges as $rangeIndex => $range)
                                                                    <div class="card mb-2 border-secondary">
                                                                        <div class="card-header bg-light py-2">
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                <h6 class="mb-0">Reference Range {{ $rangeIndex + 1 }}</h6>
                                                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeReferenceRange({{ $index + 1 }}, {{ $rangeIndex + 1 }})">
                                                                                    <i class="bi bi-trash"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                        <div class="card-body py-2">
                                                                            <div class="row">
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Age Group</label>
                                                                                    <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][{{ $rangeIndex + 1 }}][age_group]" required>
                                                                                        <option value="">Select Age Group</option>
                                                                                        <option value="neonate" {{ ($range['age_group'] ?? '') == 'neonate' ? 'selected' : '' }}>Neonate (0-28 days)</option>
                                                                                        <option value="infant" {{ ($range['age_group'] ?? '') == 'infant' ? 'selected' : '' }}>Infant (1-12 months)</option>
                                                                                        <option value="child" {{ ($range['age_group'] ?? '') == 'child' ? 'selected' : '' }}>Child (1-12 years)</option>
                                                                                        <option value="adolescent" {{ ($range['age_group'] ?? '') == 'adolescent' ? 'selected' : '' }}>Adolescent (13-17 years)</option>
                                                                                        <option value="adult" {{ ($range['age_group'] ?? '') == 'adult' ? 'selected' : '' }}>Adult (18-64 years)</option>
                                                                                        <option value="elderly" {{ ($range['age_group'] ?? '') == 'elderly' ? 'selected' : '' }}>Elderly (65+ years)</option>
                                                                                        <option value="all_ages" {{ ($range['age_group'] ?? '') == 'all_ages' ? 'selected' : '' }}>All Ages</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Gender</label>
                                                                                    <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][{{ $rangeIndex + 1 }}][gender]" required>
                                                                                        <option value="">Select Gender</option>
                                                                                        <option value="male" {{ ($range['gender'] ?? '') == 'male' ? 'selected' : '' }}>Male</option>
                                                                                        <option value="female" {{ ($range['gender'] ?? '') == 'female' ? 'selected' : '' }}>Female</option>
                                                                                        <option value="both" {{ ($range['gender'] ?? '') == 'both' ? 'selected' : '' }}>Both</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Pregnancy Status</label>
                                                                                    <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][{{ $rangeIndex + 1 }}][pregnancy_status]">
                                                                                        <option value="not_pregnant" {{ ($range['pregnancy_status'] ?? 'not_pregnant') == 'not_pregnant' ? 'selected' : '' }}>Not Pregnant</option>
                                                                                        <option value="pregnant" {{ ($range['pregnancy_status'] ?? '') == 'pregnant' ? 'selected' : '' }}>Pregnant</option>
                                                                                        <option value="both" {{ ($range['pregnancy_status'] ?? '') == 'both' ? 'selected' : '' }}>Both</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Trimester (if pregnant)</label>
                                                                                    <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][{{ $rangeIndex + 1 }}][trimester]">
                                                                                        <option value="">N/A</option>
                                                                                        <option value="first" {{ ($range['trimester'] ?? '') == 'first' ? 'selected' : '' }}>First Trimester</option>
                                                                                        <option value="second" {{ ($range['trimester'] ?? '') == 'second' ? 'selected' : '' }}>Second Trimester</option>
                                                                                        <option value="third" {{ ($range['trimester'] ?? '') == 'third' ? 'selected' : '' }}>Third Trimester</option>
                                                                                        <option value="all" {{ ($range['trimester'] ?? '') == 'all' ? 'selected' : '' }}>All Trimesters</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-4 mb-2">
                                                                                    <label class="form-label">Min Value</label>
                                                                                    <input type="number" step="0.01" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][{{ $rangeIndex + 1 }}][min_value]" 
                                                                                           value="{{ $range['min_value'] ?? '' }}" placeholder="e.g., 12.0">
                                                                                </div>
                                                                                <div class="col-md-4 mb-2">
                                                                                    <label class="form-label">Max Value</label>
                                                                                    <input type="number" step="0.01" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][{{ $rangeIndex + 1 }}][max_value]" 
                                                                                           value="{{ $range['max_value'] ?? '' }}" placeholder="e.g., 16.0">
                                                                                </div>
                                                                                <div class="col-md-4 mb-2">
                                                                                    <label class="form-label">Unit</label>
                                                                                    <input type="text" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][{{ $rangeIndex + 1 }}][unit]" 
                                                                                           value="{{ $range['unit'] ?? '' }}" placeholder="e.g., g/dL">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                <div class="card mb-2 border-secondary">
                                                                    <div class="card-header bg-light py-2">
                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                            <h6 class="mb-0">Reference Range 1</h6>
                                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeReferenceRange({{ $index + 1 }}, 1)">
                                                                                <i class="bi bi-trash"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    <div class="card-body py-2">
                                                                        <div class="row">
                                                                            <div class="col-md-3 mb-2">
                                                                                <label class="form-label">Age Group</label>
                                                                                <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][1][age_group]" required>
                                                                                    <option value="">Select Age Group</option>
                                                                                    <option value="neonate">Neonate (0-28 days)</option>
                                                                                    <option value="infant">Infant (1-12 months)</option>
                                                                                    <option value="child">Child (1-12 years)</option>
                                                                                    <option value="adolescent">Adolescent (13-17 years)</option>
                                                                                    <option value="adult">Adult (18-64 years)</option>
                                                                                    <option value="elderly">Elderly (65+ years)</option>
                                                                                    <option value="all_ages">All Ages</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-3 mb-2">
                                                                                <label class="form-label">Gender</label>
                                                                                <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][1][gender]" required>
                                                                                    <option value="">Select Gender</option>
                                                                                    <option value="male">Male</option>
                                                                                    <option value="female">Female</option>
                                                                                    <option value="both">Both</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-3 mb-2">
                                                                                <label class="form-label">Pregnancy Status</label>
                                                                                <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][1][pregnancy_status]">
                                                                                    <option value="not_pregnant">Not Pregnant</option>
                                                                                    <option value="pregnant">Pregnant</option>
                                                                                    <option value="both">Both</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-3 mb-2">
                                                                                <label class="form-label">Trimester (if pregnant)</label>
                                                                                <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][1][trimester]">
                                                                                    <option value="">N/A</option>
                                                                                    <option value="first">First Trimester</option>
                                                                                    <option value="second">Second Trimester</option>
                                                                                    <option value="third">Third Trimester</option>
                                                                                    <option value="all">All Trimesters</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-4 mb-2">
                                                                                <label class="form-label">Min Value</label>
                                                                                <input type="number" step="0.01" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][1][min_value]" 
                                                                                       placeholder="e.g., 12.0">
                                                                            </div>
                                                                            <div class="col-md-4 mb-2">
                                                                                <label class="form-label">Max Value</label>
                                                                                <input type="number" step="0.01" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][1][max_value]" 
                                                                                       placeholder="e.g., 16.0">
                                                                            </div>
                                                                            <div class="col-md-4 mb-2">
                                                                                <label class="form-label">Unit</label>
                                                                                <input type="text" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][reference_ranges][1][unit]" 
                                                                                       placeholder="e.g., g/dL">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addReferenceRange({{ $index + 1 }})">
                                                            <i class="bi bi-plus-circle"></i> Add Reference Range
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <label class="form-label">Critical Values by Demographics</label>
                                                        <div class="alert alert-warning">
                                                            <small><i class="bi bi-exclamation-triangle"></i> Add critical values for different patient demographics. These trigger immediate alerts.</small>
                                                        </div>
                                                        <div id="critical_values_{{ $index + 1 }}">
                                                            @if($param->critical_values && is_array($param->critical_values))
                                                                @foreach($param->critical_values as $criticalIndex => $critical)
                                                                    <div class="card mb-2 border-warning">
                                                                        <div class="card-header bg-light py-2">
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                <h6 class="mb-0">Critical Values {{ $criticalIndex + 1 }}</h6>
                                                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCriticalValues({{ $index + 1 }}, {{ $criticalIndex + 1 }})">
                                                                                    <i class="bi bi-trash"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                        <div class="card-body py-2">
                                                                            <div class="row">
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Age Group</label>
                                                                                    <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][critical_values][{{ $criticalIndex + 1 }}][age_group]">
                                                                                        <option value="">Select Age Group</option>
                                                                                        <option value="neonate" {{ ($critical['age_group'] ?? '') == 'neonate' ? 'selected' : '' }}>Neonate (0-28 days)</option>
                                                                                        <option value="infant" {{ ($critical['age_group'] ?? '') == 'infant' ? 'selected' : '' }}>Infant (1-12 months)</option>
                                                                                        <option value="child" {{ ($critical['age_group'] ?? '') == 'child' ? 'selected' : '' }}>Child (1-12 years)</option>
                                                                                        <option value="adolescent" {{ ($critical['age_group'] ?? '') == 'adolescent' ? 'selected' : '' }}>Adolescent (13-17 years)</option>
                                                                                        <option value="adult" {{ ($critical['age_group'] ?? '') == 'adult' ? 'selected' : '' }}>Adult (18-64 years)</option>
                                                                                        <option value="elderly" {{ ($critical['age_group'] ?? '') == 'elderly' ? 'selected' : '' }}>Elderly (65+ years)</option>
                                                                                        <option value="all_ages" {{ ($critical['age_group'] ?? '') == 'all_ages' ? 'selected' : '' }}>All Ages</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Gender</label>
                                                                                    <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][critical_values][{{ $criticalIndex + 1 }}][gender]">
                                                                                        <option value="">Select Gender</option>
                                                                                        <option value="male" {{ ($critical['gender'] ?? '') == 'male' ? 'selected' : '' }}>Male</option>
                                                                                        <option value="female" {{ ($critical['gender'] ?? '') == 'female' ? 'selected' : '' }}>Female</option>
                                                                                        <option value="both" {{ ($critical['gender'] ?? '') == 'both' ? 'selected' : '' }}>Both</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Pregnancy Status</label>
                                                                                    <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][critical_values][{{ $criticalIndex + 1 }}][pregnancy_status]">
                                                                                        <option value="not_pregnant" {{ ($critical['pregnancy_status'] ?? 'not_pregnant') == 'not_pregnant' ? 'selected' : '' }}>Not Pregnant</option>
                                                                                        <option value="pregnant" {{ ($critical['pregnancy_status'] ?? '') == 'pregnant' ? 'selected' : '' }}>Pregnant</option>
                                                                                        <option value="both" {{ ($critical['pregnancy_status'] ?? '') == 'both' ? 'selected' : '' }}>Both</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Trimester (if pregnant)</label>
                                                                                    <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][critical_values][{{ $criticalIndex + 1 }}][trimester]">
                                                                                        <option value="">N/A</option>
                                                                                        <option value="first" {{ ($critical['trimester'] ?? '') == 'first' ? 'selected' : '' }}>First Trimester</option>
                                                                                        <option value="second" {{ ($critical['trimester'] ?? '') == 'second' ? 'selected' : '' }}>Second Trimester</option>
                                                                                        <option value="third" {{ ($critical['trimester'] ?? '') == 'third' ? 'selected' : '' }}>Third Trimester</option>
                                                                                        <option value="all" {{ ($critical['trimester'] ?? '') == 'all' ? 'selected' : '' }}>All Trimesters</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Critical Low</label>
                                                                                    <input type="number" step="0.01" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][critical_values][{{ $criticalIndex + 1 }}][critical_low]" 
                                                                                           value="{{ $critical['critical_low'] ?? '' }}" placeholder="e.g., 7.0">
                                                                                </div>
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Critical High</label>
                                                                                    <input type="number" step="0.01" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][critical_values][{{ $criticalIndex + 1 }}][critical_high]" 
                                                                                           value="{{ $critical['critical_high'] ?? '' }}" placeholder="e.g., 20.0">
                                                                                </div>
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Panic Low</label>
                                                                                    <input type="number" step="0.01" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][critical_values][{{ $criticalIndex + 1 }}][panic_low]" 
                                                                                           value="{{ $critical['panic_low'] ?? '' }}" placeholder="e.g., 5.0">
                                                                                </div>
                                                                                <div class="col-md-3 mb-2">
                                                                                    <label class="form-label">Panic High</label>
                                                                                    <input type="number" step="0.01" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][critical_values][{{ $criticalIndex + 1 }}][panic_high]" 
                                                                                           value="{{ $critical['panic_high'] ?? '' }}" placeholder="e.g., 25.0">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                <div class="card mb-2 border-warning">
                                                                    <div class="card-header bg-light py-2">
                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                            <h6 class="mb-0">Critical Values 1</h6>
                                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCriticalValues({{ $index + 1 }}, 1)">
                                                                                <i class="bi bi-trash"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    <div class="card-body py-2">
                                                                        <div class="row">
                                                                            <div class="col-md-3 mb-2">
                                                                                <label class="form-label">Age Group</label>
                                                                                <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][critical_values][1][age_group]">
                                                                                    <option value="">Select Age Group</option>
                                                                                    <option value="neonate">Neonate (0-28 days)</option>
                                                                                    <option value="infant">Infant (1-12 months)</option>
                                                                                    <option value="child">Child (1-12 years)</option>
                                                                                    <option value="adolescent">Adolescent (13-17 years)</option>
                                                                                    <option value="adult">Adult (18-64 years)</option>
                                                                                    <option value="elderly">Elderly (65+ years)</option>
                                                                                    <option value="all_ages">All Ages</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-3 mb-2">
                                                                                <label class="form-label">Gender</label>
                                                                                <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][critical_values][1][gender]">
                                                                                    <option value="">Select Gender</option>
                                                                                    <option value="male">Male</option>
                                                                                    <option value="female">Female</option>
                                                                                    <option value="both">Both</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-3 mb-2">
                                                                                <label class="form-label">Pregnancy Status</label>
                                                                                <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][critical_values][1][pregnancy_status]">
                                                                                    <option value="not_pregnant">Not Pregnant</option>
                                                                                    <option value="pregnant">Pregnant</option>
                                                                                    <option value="both">Both</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-3 mb-2">
                                                                                <label class="form-label">Trimester (if pregnant)</label>
                                                                                <select class="form-select" name="quantitative_parameters[{{ $index + 1 }}][critical_values][1][trimester]">
                                                                                    <option value="">N/A</option>
                                                                                    <option value="first">First Trimester</option>
                                                                                    <option value="second">Second Trimester</option>
                                                                                    <option value="third">Third Trimester</option>
                                                                                    <option value="all">All Trimesters</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-3 mb-2">
                                                                                <label class="form-label">Critical Low</label>
                                                                                <input type="number" step="0.01" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][critical_values][1][critical_low]" 
                                                                                       placeholder="e.g., 7.0">
                                                                            </div>
                                                                            <div class="col-md-3 mb-2">
                                                                                <label class="form-label">Critical High</label>
                                                                                <input type="number" step="0.01" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][critical_values][1][critical_high]" 
                                                                                       placeholder="e.g., 20.0">
                                                                            </div>
                                                                            <div class="col-md-3 mb-2">
                                                                                <label class="form-label">Panic Low</label>
                                                                                <input type="number" step="0.01" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][critical_values][1][panic_low]" 
                                                                                       placeholder="e.g., 5.0">
                                                                            </div>
                                                                            <div class="col-md-3 mb-2">
                                                                                <label class="form-label">Panic High</label>
                                                                                <input type="number" step="0.01" class="form-control" name="quantitative_parameters[{{ $index + 1 }}][critical_values][1][panic_high]" 
                                                                                       placeholder="e.g., 25.0">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="addCriticalValues({{ $index + 1 }})">
                                                            <i class="bi bi-plus-circle"></i> Add Critical Values
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <div class="form-check">
                                                            <input type="hidden" name="quantitative_parameters[{{ $index + 1 }}][is_required]" value="0">
                                                            <input class="form-check-input" type="checkbox" name="quantitative_parameters[{{ $index + 1 }}][is_required]" value="1"
                                                                   {{ $param->is_required ? 'checked' : '' }}>
                                                            <label class="form-check-label">Required Parameter</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input type="hidden" name="quantitative_parameters[{{ $index + 1 }}][is_critical]" value="0">
                                                            <input class="form-check-input" type="checkbox" name="quantitative_parameters[{{ $index + 1 }}][is_critical]" value="1" 
                                                                   {{ $param->is_critical ? 'checked' : '' }}>
                                                            <label class="form-check-label">Critical Parameter</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input type="hidden" name="quantitative_parameters[{{ $index + 1 }}][allows_delta_check]" value="0">
                                                            <input class="form-check-input" type="checkbox" name="quantitative_parameters[{{ $index + 1 }}][allows_delta_check]" value="1"
                                                                   {{ $param->allows_delta_check ? 'checked' : '' }}>
                                                            <label class="form-check-label">Allow Delta Check</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add_quantitative_parameter">
                                <i class="bi bi-plus-circle"></i> Add Parameter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Qualitative Parameters Section -->
                <div class="mb-4" id="qualitative_section" style="display: none;">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="bi bi-list-check"></i> Qualitative Parameters</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <strong><i class="bi bi-info-circle"></i> Qualitative Tests:</strong> 
                                Add discrete value parameters with predefined options (dropdown/radio).
                            </div>
                            
                            <div id="qualitative_parameters_container">
                                @if($template->parameters->where('data_type', 'text')->count() > 0)
                                    @foreach($template->parameters->where('data_type', 'text') as $index => $param)
                                        <div class="card mb-3 border-success" id="qualitative_param_{{ $index + 1 }}">
                                            <div class="card-header bg-light">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">Parameter {{ $index + 1 }}</h6>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQualitativeParameter({{ $index + 1 }})">
                                                        <i class="bi bi-trash"></i> Remove
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Parameter Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="qualitative_parameters[{{ $index + 1 }}][parameter_name]" 
                                                               value="{{ $param->parameter_name }}" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Parameter Code</label>
                                                        <input type="text" class="form-control" name="qualitative_parameters[{{ $index + 1 }}][parameter_code]" 
                                                               value="{{ $param->parameter_code }}">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Input Type</label>
                                                        <select class="form-select" name="qualitative_parameters[{{ $index + 1 }}][input_type]" onchange="toggleInputOptions({{ $index + 1 }})">
                                                            <option value="select" {{ $param->input_type == 'select' ? 'selected' : '' }}>Dropdown</option>
                                                            <option value="radio" {{ $param->input_type == 'radio' ? 'selected' : '' }}>Radio Buttons</option>
                                                            <option value="checkbox" {{ $param->input_type == 'checkbox' ? 'selected' : '' }}>Checkboxes</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Sort Order</label>
                                                        <input type="number" class="form-control" name="qualitative_parameters[{{ $index + 1 }}][sort_order]" 
                                                               value="{{ $param->sort_order }}" min="1">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <label class="form-label">Options <span class="text-danger">*</span></label>
                                                        <div id="options_container_{{ $index + 1 }}">
                                                            @if($param->input_options)
                                                                @foreach($param->input_options as $optionIndex => $option)
                                                                    <div class="input-group mb-2">
                                                                        <input type="text" class="form-control" name="qualitative_parameters[{{ $index + 1 }}][options][]" 
                                                                               value="{{ $option }}" required>
                                                                        @if($optionIndex == 0)
                                                                            <button type="button" class="btn btn-outline-success" onclick="addOption({{ $index + 1 }})">
                                                                                <i class="bi bi-plus"></i>
                                                                            </button>
                                                                        @else
                                                                            <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                                                                                <i class="bi bi-dash"></i>
                                                                            </button>
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                <div class="input-group mb-2">
                                                                    <input type="text" class="form-control" name="qualitative_parameters[{{ $index + 1 }}][options][]" 
                                                                           placeholder="e.g., A+" required>
                                                                    <button type="button" class="btn btn-outline-success" onclick="addOption({{ $index + 1 }})">
                                                                        <i class="bi bi-plus"></i>
                                                                    </button>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <small class="text-muted">Add at least one option for the parameter</small>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <div class="form-check">
                                                            <input type="hidden" name="qualitative_parameters[{{ $index + 1 }}][is_required]" value="0">
                                                            <input class="form-check-input" type="checkbox" name="qualitative_parameters[{{ $index + 1 }}][is_required]" value="1"
                                                                   {{ $param->is_required ? 'checked' : '' }}>
                                                            <label class="form-check-label">Required Parameter</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input type="hidden" name="qualitative_parameters[{{ $index + 1 }}][is_critical]" value="0">
                                                            <input class="form-check-input" type="checkbox" name="qualitative_parameters[{{ $index + 1 }}][is_critical]" value="1" 
                                                                   {{ $param->is_critical ? 'checked' : '' }}>
                                                            <label class="form-check-label">Critical Parameter</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            
                            <button type="button" class="btn btn-outline-success btn-sm" id="add_qualitative_parameter">
                                <i class="bi bi-plus-circle"></i> Add Parameter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Narrative Template Content (WYSIWYG Editor) -->
                <div class="mb-3" id="narrative_section" style="display: none;">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bi bi-file-text"></i> Narrative Template Content</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <strong><i class="bi bi-info-circle"></i> For Narrative Tests:</strong> 
                                Create a rich text template structure. Lab technicians will use this as a guide when entering results.
                            </div>
                            <textarea class="form-control" id="template_content" name="template_content" rows="10">{{ old('template_content', $template->template_content) }}</textarea>
                            <small class="text-muted">Use formatting tools above. You can include placeholders like {patient_name}, {test_date}, etc.</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="specimen_type" class="form-label">Specimen Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('specimen_type') is-invalid @enderror" 
                               id="specimen_type" name="specimen_type" value="{{ old('specimen_type', $template->specimen_type) }}" required>
                        @error('specimen_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="methodology" class="form-label">Methodology</label>
                        <input type="text" class="form-control @error('methodology') is-invalid @enderror" 
                               id="methodology" name="methodology" value="{{ old('methodology', $template->methodology) }}">
                        @error('methodology')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="equipment_required" class="form-label">Equipment Required</label>
                    <input type="text" class="form-control @error('equipment_required') is-invalid @enderror" 
                           id="equipment_required" name="equipment_required" value="{{ old('equipment_required', $template->equipment_required) }}">
                    @error('equipment_required')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="routine_tat_hours" class="form-label">Routine TAT (hours)</label>
                        <input type="number" class="form-control @error('routine_tat_hours') is-invalid @enderror" 
                               id="routine_tat_hours" name="routine_tat_hours" value="{{ old('routine_tat_hours', $template->routine_tat_hours) }}" min="1">
                        @error('routine_tat_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="urgent_tat_hours" class="form-label">Urgent TAT (hours)</label>
                        <input type="number" class="form-control @error('urgent_tat_hours') is-invalid @enderror" 
                               id="urgent_tat_hours" name="urgent_tat_hours" value="{{ old('urgent_tat_hours', $template->urgent_tat_hours) }}" min="1">
                        @error('urgent_tat_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="stat_tat_hours" class="form-label">STAT TAT (hours)</label>
                        <input type="number" class="form-control @error('stat_tat_hours') is-invalid @enderror" 
                               id="stat_tat_hours" name="stat_tat_hours" value="{{ old('stat_tat_hours', $template->stat_tat_hours) }}" min="1">
                        @error('stat_tat_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="cost" class="form-label">Cost (GHS)</label>
                        <input type="number" step="0.01" class="form-control @error('cost') is-invalid @enderror" 
                               id="cost" name="cost" value="{{ old('cost', $template->cost) }}" min="0">
                        @error('cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="nhis_cost" class="form-label">NHIS Cost (GHS)</label>
                        <input type="number" step="0.01" class="form-control @error('nhis_cost') is-invalid @enderror" 
                               id="nhis_cost" name="nhis_cost" value="{{ old('nhis_cost', $template->nhis_cost) }}" min="0">
                        @error('nhis_cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="nhis_covered" 
                                   name="nhis_covered" {{ old('nhis_covered', $template->nhis_covered) ? 'checked' : '' }}>
                            <label class="form-check-label" for="nhis_covered">
                                NHIS Covered
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" 
                                   name="is_active" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="updateBtn">
                        <i class="bi bi-check-circle"></i> Update Template
                    </button>
                    <a href="{{ route('lab.templates.show', $template) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let templateContentEditor = null;
    
    // CKEditor configuration for lab template content
    const editorConfig = {
        toolbar: {
            items: [
                'heading', '|',
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'bulletedList', 'numberedList', '|',
                'outdent', 'indent', '|',
                'blockQuote', 'insertTable', '|',
                'undo', 'redo'
            ]
        },
        language: 'en',
        table: {
            contentToolbar: [
                'tableColumn',
                'tableRow',
                'mergeTableCells'
            ]
        },
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
            ]
        }
    };
    
    const templateTypeSelect = document.getElementById('template_type');
    const quantitativeSection = document.getElementById('quantitative_section');
    const qualitativeSection = document.getElementById('qualitative_section');
    const narrativeSection = document.getElementById('narrative_section');
    
    // Subcategory (Test Type) dropdown: populate from test types when category is selected
    const testTypesByCategory = @json($testTypesByCategory ?? collect());
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory');
    const currentSubcategory = @json(old('subcategory', $template->subcategory ?? ''));
    
    function populateSubcategory() {
        const categoryId = categorySelect.value;
        subcategorySelect.innerHTML = '<option value="">-- Select Test Type --</option>';
        subcategorySelect.disabled = !categoryId;
        
        if (categoryId) {
            const testTypes = testTypesByCategory[categoryId] || testTypesByCategory[String(categoryId)] || [];
            testTypes.forEach(function(tt) {
                const opt = document.createElement('option');
                opt.value = tt.test_name;
                opt.textContent = tt.test_name + ' (' + (tt.test_code || '') + ')';
                if (currentSubcategory && tt.test_name === currentSubcategory) {
                    opt.selected = true;
                }
                subcategorySelect.appendChild(opt);
            });
        }
    }
    
    if (categorySelect) categorySelect.addEventListener('change', populateSubcategory);
    if (categorySelect && categorySelect.value) populateSubcategory();
    
    let quantitativeParameterCount = {{ $template->parameters->where('data_type', 'numeric')->count() }};
    let qualitativeParameterCount = {{ $template->parameters->where('data_type', 'text')->count() }};
    
    function toggleTemplateSections() {
        const templateType = templateTypeSelect.value;
        
        // Hide all sections first
        quantitativeSection.style.display = 'none';
        qualitativeSection.style.display = 'none';
        narrativeSection.style.display = 'none';
        
        // Show relevant sections based on template type
        if (templateType === 'quantitative' || templateType === 'combined') {
            quantitativeSection.style.display = 'block';
        }
        
        if (templateType === 'qualitative' || templateType === 'combined') {
            qualitativeSection.style.display = 'block';
        }
        
        if (templateType === 'narrative' || templateType === 'combined') {
            narrativeSection.style.display = 'block';
            // Initialize CKEditor for narrative content if not already initialized
            if (!templateContentEditor) {
                const element = document.getElementById('template_content');
                if (element) {
                    ClassicEditor
                        .create(element, editorConfig)
                        .then(editor => {
                            templateContentEditor = editor;
                            console.log('CKEditor initialized for template_content');
                            editor.ui.view.editable.element.classList.add('ck-editor__editable--medical');
                        })
                        .catch(error => {
                            console.error('Error initializing CKEditor for template_content:', error);
                        });
                }
            }
        } else {
            // Destroy CKEditor if not needed
            if (templateContentEditor) {
                templateContentEditor.destroy()
                    .then(() => {
                        templateContentEditor = null;
                        console.log('CKEditor destroyed for template_content');
                    })
                    .catch(error => {
                        console.error('Error destroying CKEditor:', error);
                    });
            }
        }
    }
    
    templateTypeSelect.addEventListener('change', toggleTemplateSections);
    toggleTemplateSections(); // Initialize on load
    
    // Quantitative Parameter Management
    function addQuantitativeParameter() {
        quantitativeParameterCount++;
        const container = document.getElementById('quantitative_parameters_container');
        
        const parameterDiv = document.createElement('div');
        parameterDiv.className = 'card mb-3 border-primary';
        parameterDiv.id = `quantitative_param_${quantitativeParameterCount}`;
        
        parameterDiv.innerHTML = `
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Parameter ${quantitativeParameterCount}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuantitativeParameter(${quantitativeParameterCount})">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Parameter Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][parameter_name]" 
                               placeholder="e.g., Hemoglobin" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Parameter Code</label>
                        <input type="text" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][parameter_code]" 
                               placeholder="e.g., HGB">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Unit</label>
                        <input type="text" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][unit]" 
                               placeholder="e.g., g/dL">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Decimal Places</label>
                        <input type="number" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][decimal_places]" 
                               value="2" min="0" max="4">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][sort_order]" 
                               value="${quantitativeParameterCount}" min="1">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Reference Range (Min)</label>
                        <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][ref_min]" 
                               placeholder="e.g., 12.0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Reference Range (Max)</label>
                        <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][ref_max]" 
                               placeholder="e.g., 16.0">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Critical Low</label>
                        <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][critical_low]" 
                               placeholder="e.g., 7.0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Critical High</label>
                        <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][critical_high]" 
                               placeholder="e.g., 20.0">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input type="hidden" name="quantitative_parameters[${quantitativeParameterCount}][is_required]" value="0">
                            <input class="form-check-input" type="checkbox" name="quantitative_parameters[${quantitativeParameterCount}][is_required]" value="1" checked>
                            <label class="form-check-label">Required Parameter</label>
                        </div>
                        <div class="form-check">
                            <input type="hidden" name="quantitative_parameters[${quantitativeParameterCount}][is_critical]" value="0">
                            <input class="form-check-input" type="checkbox" name="quantitative_parameters[${quantitativeParameterCount}][is_critical]" value="1">
                            <label class="form-check-label">Critical Parameter</label>
                        </div>
                        <div class="form-check">
                            <input type="hidden" name="quantitative_parameters[${quantitativeParameterCount}][allows_delta_check]" value="0">
                            <input class="form-check-input" type="checkbox" name="quantitative_parameters[${quantitativeParameterCount}][allows_delta_check]" value="1">
                            <label class="form-check-label">Allow Delta Check</label>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.appendChild(parameterDiv);
    }
    
    function removeQuantitativeParameter(paramId) {
        const element = document.getElementById(`quantitative_param_${paramId}`);
        if (element) {
            element.remove();
        }
    }
    
    // Qualitative Parameter Management
    function addQualitativeParameter() {
        qualitativeParameterCount++;
        const container = document.getElementById('qualitative_parameters_container');
        
        const parameterDiv = document.createElement('div');
        parameterDiv.className = 'card mb-3 border-success';
        parameterDiv.id = `qualitative_param_${qualitativeParameterCount}`;
        
        parameterDiv.innerHTML = `
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Parameter ${qualitativeParameterCount}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQualitativeParameter(${qualitativeParameterCount})">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Parameter Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="qualitative_parameters[${qualitativeParameterCount}][parameter_name]" 
                               placeholder="e.g., Blood Group" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Parameter Code</label>
                        <input type="text" class="form-control" name="qualitative_parameters[${qualitativeParameterCount}][parameter_code]" 
                               placeholder="e.g., BG">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Input Type</label>
                        <select class="form-select" name="qualitative_parameters[${qualitativeParameterCount}][input_type]" onchange="toggleInputOptions(${qualitativeParameterCount})">
                            <option value="select">Dropdown</option>
                            <option value="radio">Radio Buttons</option>
                            <option value="checkbox">Checkboxes</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" class="form-control" name="qualitative_parameters[${qualitativeParameterCount}][sort_order]" 
                               value="${qualitativeParameterCount}" min="1">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Options <span class="text-danger">*</span></label>
                        <div id="options_container_${qualitativeParameterCount}">
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="qualitative_parameters[${qualitativeParameterCount}][options][]" 
                                       placeholder="e.g., A+" required>
                                <button type="button" class="btn btn-outline-success" onclick="addOption(${qualitativeParameterCount})">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Add at least one option for the parameter</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input type="hidden" name="qualitative_parameters[${qualitativeParameterCount}][is_required]" value="0">
                            <input class="form-check-input" type="checkbox" name="qualitative_parameters[${qualitativeParameterCount}][is_required]" value="1" checked>
                            <label class="form-check-label">Required Parameter</label>
                        </div>
                        <div class="form-check">
                            <input type="hidden" name="qualitative_parameters[${qualitativeParameterCount}][is_critical]" value="0">
                            <input class="form-check-input" type="checkbox" name="qualitative_parameters[${qualitativeParameterCount}][is_critical]" value="1">
                            <label class="form-check-label">Critical Parameter</label>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.appendChild(parameterDiv);
    }
    
    function removeQualitativeParameter(paramId) {
        const element = document.getElementById(`qualitative_param_${paramId}`);
        if (element) {
            element.remove();
        }
    }
    
    function addOption(paramId) {
        const container = document.getElementById(`options_container_${paramId}`);
        const optionDiv = document.createElement('div');
        optionDiv.className = 'input-group mb-2';
        optionDiv.innerHTML = `
            <input type="text" class="form-control" name="qualitative_parameters[${paramId}][options][]" 
                   placeholder="e.g., B+">
            <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                <i class="bi bi-dash"></i>
            </button>
        `;
        container.appendChild(optionDiv);
    }
    
    function removeOption(button) {
        button.parentElement.remove();
    }
    
    function toggleInputOptions(paramId) {
        // This function can be used to show/hide different input option types
        // For now, we'll keep it simple with the same option structure
    }
    
    // Reference Range Management
    function addReferenceRange(paramId) {
        const container = document.getElementById(`reference_ranges_${paramId}`);
        const rangeCount = container.children.length + 1;
        
        const rangeDiv = document.createElement('div');
        rangeDiv.className = 'card mb-2 border-secondary';
        rangeDiv.innerHTML = `
            <div class="card-header bg-light py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Reference Range ${rangeCount}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeReferenceRange(${paramId}, ${rangeCount})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body py-2">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Age Group</label>
                        <select class="form-select" name="quantitative_parameters[${paramId}][reference_ranges][${rangeCount}][age_group]" required>
                            <option value="">Select Age Group</option>
                            <option value="neonate">Neonate (0-28 days)</option>
                            <option value="infant">Infant (1-12 months)</option>
                            <option value="child">Child (1-12 years)</option>
                            <option value="adolescent">Adolescent (13-17 years)</option>
                            <option value="adult">Adult (18-64 years)</option>
                            <option value="elderly">Elderly (65+ years)</option>
                            <option value="all_ages">All Ages</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="quantitative_parameters[${paramId}][reference_ranges][${rangeCount}][gender]" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Pregnancy Status</label>
                        <select class="form-select" name="quantitative_parameters[${paramId}][reference_ranges][${rangeCount}][pregnancy_status]">
                            <option value="not_pregnant">Not Pregnant</option>
                            <option value="pregnant">Pregnant</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Trimester (if pregnant)</label>
                        <select class="form-select" name="quantitative_parameters[${paramId}][reference_ranges][${rangeCount}][trimester]">
                            <option value="">N/A</option>
                            <option value="first">First Trimester</option>
                            <option value="second">Second Trimester</option>
                            <option value="third">Third Trimester</option>
                            <option value="all">All Trimesters</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Min Value</label>
                        <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${paramId}][reference_ranges][${rangeCount}][min_value]" 
                               placeholder="e.g., 12.0">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Max Value</label>
                        <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${paramId}][reference_ranges][${rangeCount}][max_value]" 
                               placeholder="e.g., 16.0">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Unit</label>
                        <input type="text" class="form-control" name="quantitative_parameters[${paramId}][reference_ranges][${rangeCount}][unit]" 
                               placeholder="e.g., g/dL">
                    </div>
                </div>
            </div>
        `;
        
        container.appendChild(rangeDiv);
    }
    
    function removeReferenceRange(paramId, rangeId) {
        const element = document.querySelector(`#reference_ranges_${paramId} .card:nth-child(${rangeId})`);
        if (element) {
            element.remove();
        }
    }
    
    // Critical Values Management
    function addCriticalValues(paramId) {
        const container = document.getElementById(`critical_values_${paramId}`);
        const criticalCount = container.children.length + 1;
        
        const criticalDiv = document.createElement('div');
        criticalDiv.className = 'card mb-2 border-warning';
        criticalDiv.innerHTML = `
            <div class="card-header bg-light py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Critical Values ${criticalCount}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCriticalValues(${paramId}, ${criticalCount})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body py-2">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Age Group</label>
                        <select class="form-select" name="quantitative_parameters[${paramId}][critical_values][${criticalCount}][age_group]">
                            <option value="">Select Age Group</option>
                            <option value="neonate">Neonate (0-28 days)</option>
                            <option value="infant">Infant (1-12 months)</option>
                            <option value="child">Child (1-12 years)</option>
                            <option value="adolescent">Adolescent (13-17 years)</option>
                            <option value="adult">Adult (18-64 years)</option>
                            <option value="elderly">Elderly (65+ years)</option>
                            <option value="all_ages">All Ages</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="quantitative_parameters[${paramId}][critical_values][${criticalCount}][gender]">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Pregnancy Status</label>
                        <select class="form-select" name="quantitative_parameters[${paramId}][critical_values][${criticalCount}][pregnancy_status]">
                            <option value="not_pregnant">Not Pregnant</option>
                            <option value="pregnant">Pregnant</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Trimester (if pregnant)</label>
                        <select class="form-select" name="quantitative_parameters[${paramId}][critical_values][${criticalCount}][trimester]">
                            <option value="">N/A</option>
                            <option value="first">First Trimester</option>
                            <option value="second">Second Trimester</option>
                            <option value="third">Third Trimester</option>
                            <option value="all">All Trimesters</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Critical Low</label>
                        <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${paramId}][critical_values][${criticalCount}][critical_low]" 
                               placeholder="e.g., 7.0">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Critical High</label>
                        <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${paramId}][critical_values][${criticalCount}][critical_high]" 
                               placeholder="e.g., 20.0">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Panic Low</label>
                        <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${paramId}][critical_values][${criticalCount}][panic_low]" 
                               placeholder="e.g., 5.0">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Panic High</label>
                        <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${paramId}][critical_values][${criticalCount}][panic_high]" 
                               placeholder="e.g., 25.0">
                    </div>
                </div>
            </div>
        `;
        
        container.appendChild(criticalDiv);
    }
    
    function removeCriticalValues(paramId, criticalId) {
        const element = document.querySelector(`#critical_values_${paramId} .card:nth-child(${criticalId})`);
        if (element) {
            element.remove();
        }
    }
    
    // Event listeners for add parameter buttons
    document.getElementById('add_quantitative_parameter').addEventListener('click', addQuantitativeParameter);
    document.getElementById('add_qualitative_parameter').addEventListener('click', addQualitativeParameter);
    
    // Make functions globally available
    window.removeQuantitativeParameter = removeQuantitativeParameter;
    window.removeQualitativeParameter = removeQualitativeParameter;
    window.addOption = addOption;
    window.removeOption = removeOption;
    window.toggleInputOptions = toggleInputOptions;
    window.addReferenceRange = addReferenceRange;
    window.removeReferenceRange = removeReferenceRange;
    window.addCriticalValues = addCriticalValues;
    window.removeCriticalValues = removeCriticalValues;
    
    // Form submission debugging
    const form = document.getElementById('editTemplateForm');
    const updateBtn = document.getElementById('updateBtn');
    
    form.addEventListener('submit', function(e) {
        console.log('Edit form is submitting...');
        console.log('Form action:', form.action);
        console.log('Form method:', form.method);
        
        // Collect all form data
        const formData = new FormData(form);
        console.log('Form data being submitted:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: ${value}`);
        }
        
        // Disable button to prevent double submission
        updateBtn.disabled = true;
        updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    });
    
    console.log('Lab template edit page loaded successfully');
});
</script>
@endsection

