@extends('layouts.app')

@section('title', 'Create Test Template')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-1" style="color: #1e3a5f;">Create Test Template</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('lab.templates') }}">Templates</a></li>
                <li class="breadcrumb-item active">Create</li>
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
            <h5 class="mb-0 text-dark">New Test Template</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('lab.templates.store') }}" method="POST" id="templateForm">
                @csrf
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="template_code" class="form-label">Template Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('template_code') is-invalid @enderror" 
                               id="template_code" name="template_code" value="{{ old('template_code') }}" required>
                        @error('template_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Unique identifier (e.g., TPL-CBC-001)</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="template_name" class="form-label">Template Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('template_name') is-invalid @enderror" 
                               id="template_name" name="template_name" value="{{ old('template_name') }}" required>
                        @error('template_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select @error('category_id') is-invalid @enderror" 
                                id="category_id" name="category_id" required>
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="subcategory" class="form-label">Subcategory (Test Type)</label>
                        <select class="form-select @error('subcategory') is-invalid @enderror" 
                                id="subcategory" name="subcategory" disabled>
                            <option value="">-- Select a category first --</option>
                        </select>
                        <small class="text-muted">Select a category above to load test types (subcategories).</small>
                        @error('subcategory')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="template_type" class="form-label">Template Type <span class="text-danger">*</span></label>
                        <select class="form-select @error('template_type') is-invalid @enderror" 
                                id="template_type" name="template_type" required>
                            <option value="">-- Select Template Type --</option>
                            <option value="quantitative" {{ old('template_type') == 'quantitative' ? 'selected' : '' }}>Quantitative (Numeric)</option>
                            <option value="qualitative" {{ old('template_type') == 'qualitative' ? 'selected' : '' }}>Qualitative (Discrete Values)</option>
                            <option value="narrative" {{ old('template_type') == 'narrative' ? 'selected' : '' }}>Narrative (WYSIWYG Text)</option>
                            <option value="combined" {{ old('template_type') == 'combined' ? 'selected' : '' }}>Combined (Mixed)</option>
                        </select>
                        @error('template_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" name="description" rows="2">{{ old('description') }}</textarea>
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
                                <!-- Parameters will be added dynamically here -->
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
                                <!-- Parameters will be added dynamically here -->
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
                            <textarea class="form-control" id="template_content" name="template_content" rows="10">{{ old('template_content') }}</textarea>
                            <small class="text-muted">Use formatting tools above. You can include placeholders like {patient_name}, {test_date}, etc.</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="specimen_type" class="form-label">Specimen Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('specimen_type') is-invalid @enderror" 
                               id="specimen_type" name="specimen_type" value="{{ old('specimen_type') }}" 
                               placeholder="Blood, Urine, Stool, etc." required>
                        @error('specimen_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="methodology" class="form-label">Methodology</label>
                        <input type="text" class="form-control @error('methodology') is-invalid @enderror" 
                               id="methodology" name="methodology" value="{{ old('methodology') }}" 
                               placeholder="e.g., Automated Hematology Analyzer">
                        @error('methodology')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="equipment_required" class="form-label">Equipment Required</label>
                    <input type="text" class="form-control @error('equipment_required') is-invalid @enderror" 
                           id="equipment_required" name="equipment_required" value="{{ old('equipment_required') }}" 
                           placeholder="e.g., Sysmex XN-1000">
                    @error('equipment_required')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="routine_tat_hours" class="form-label">Routine TAT (hours)</label>
                        <input type="number" class="form-control @error('routine_tat_hours') is-invalid @enderror" 
                               id="routine_tat_hours" name="routine_tat_hours" value="{{ old('routine_tat_hours', 24) }}" min="1">
                        @error('routine_tat_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="urgent_tat_hours" class="form-label">Urgent TAT (hours)</label>
                        <input type="number" class="form-control @error('urgent_tat_hours') is-invalid @enderror" 
                               id="urgent_tat_hours" name="urgent_tat_hours" value="{{ old('urgent_tat_hours', 4) }}" min="1">
                        @error('urgent_tat_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="stat_tat_hours" class="form-label">STAT TAT (hours)</label>
                        <input type="number" class="form-control @error('stat_tat_hours') is-invalid @enderror" 
                               id="stat_tat_hours" name="stat_tat_hours" value="{{ old('stat_tat_hours', 1) }}" min="1">
                        @error('stat_tat_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="cost" class="form-label">Cost (GHS)</label>
                        <input type="number" step="0.01" class="form-control @error('cost') is-invalid @enderror" 
                               id="cost" name="cost" value="{{ old('cost') }}" min="0">
                        @error('cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="nhis_cost" class="form-label">NHIS Cost (GHS)</label>
                        <input type="number" step="0.01" class="form-control @error('nhis_cost') is-invalid @enderror" 
                               id="nhis_cost" name="nhis_cost" value="{{ old('nhis_cost') }}" min="0">
                        @error('nhis_cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="nhis_covered" 
                                   name="nhis_covered" {{ old('nhis_covered') ? 'checked' : '' }}>
                            <label class="form-check-label" for="nhis_covered">
                                NHIS Covered
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" 
                                   name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <strong><i class="bi bi-info-circle"></i> Template Type Guide:</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>Quantitative:</strong> Numeric tests with reference ranges (e.g., Hemoglobin, Glucose)</li>
                        <li><strong>Qualitative:</strong> Discrete value tests without reference ranges (e.g., Blood Group: A+/B+/O+, HIV: Positive/Negative/Reactive)</li>
                        <li><strong>Narrative:</strong> Free text reports (e.g., Histopathology, Microscopy findings)</li>
                        <li><strong>Combined:</strong> Mix of numeric and discrete parameters</li>
                    </ul>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-check-circle"></i> Create Template
                    </button>
                    <a href="{{ route('lab.templates') }}" class="btn btn-secondary">
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
    
    // Show/hide sections based on template type
    const templateTypeSelect = document.getElementById('template_type');
    const quantitativeSection = document.getElementById('quantitative_section');
    const qualitativeSection = document.getElementById('qualitative_section');
    const narrativeSection = document.getElementById('narrative_section');
    
    let quantitativeParameterCount = 0;
    let qualitativeParameterCount = 0;
    
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
    
    // Trigger on load if old value exists
    toggleTemplateSections();
    
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
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Reference Ranges by Demographics</label>
                        <div class="alert alert-info">
                            <small><i class="bi bi-info-circle"></i> Add reference ranges for different patient demographics. At least one demographic group is required.</small>
                        </div>
                        <div id="reference_ranges_${quantitativeParameterCount}">
                            <div class="card mb-2 border-secondary">
                                <div class="card-header bg-light py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Reference Range 1</h6>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeReferenceRange(${quantitativeParameterCount}, 1)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body py-2">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Age Group</label>
                                            <select class="form-select" name="quantitative_parameters[${quantitativeParameterCount}][reference_ranges][1][age_group]" required>
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
                                            <select class="form-select" name="quantitative_parameters[${quantitativeParameterCount}][reference_ranges][1][gender]" required>
                                                <option value="">Select Gender</option>
                                                <option value="male">Male</option>
                                                <option value="female">Female</option>
                                                <option value="both">Both</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Pregnancy Status</label>
                                            <select class="form-select" name="quantitative_parameters[${quantitativeParameterCount}][reference_ranges][1][pregnancy_status]">
                                                <option value="not_pregnant">Not Pregnant</option>
                                                <option value="pregnant">Pregnant</option>
                                                <option value="both">Both</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Trimester (if pregnant)</label>
                                            <select class="form-select" name="quantitative_parameters[${quantitativeParameterCount}][reference_ranges][1][trimester]">
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
                                            <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][reference_ranges][1][min_value]" 
                                                   placeholder="e.g., 12.0">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Max Value</label>
                                            <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][reference_ranges][1][max_value]" 
                                                   placeholder="e.g., 16.0">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Unit</label>
                                            <input type="text" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][reference_ranges][1][unit]" 
                                                   placeholder="e.g., g/dL">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addReferenceRange(${quantitativeParameterCount})">
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
                        <div id="critical_values_${quantitativeParameterCount}">
                            <div class="card mb-2 border-warning">
                                <div class="card-header bg-light py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Critical Values 1</h6>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCriticalValues(${quantitativeParameterCount}, 1)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body py-2">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Age Group</label>
                                            <select class="form-select" name="quantitative_parameters[${quantitativeParameterCount}][critical_values][1][age_group]">
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
                                            <select class="form-select" name="quantitative_parameters[${quantitativeParameterCount}][critical_values][1][gender]">
                                                <option value="">Select Gender</option>
                                                <option value="male">Male</option>
                                                <option value="female">Female</option>
                                                <option value="both">Both</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Pregnancy Status</label>
                                            <select class="form-select" name="quantitative_parameters[${quantitativeParameterCount}][critical_values][1][pregnancy_status]">
                                                <option value="not_pregnant">Not Pregnant</option>
                                                <option value="pregnant">Pregnant</option>
                                                <option value="both">Both</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Trimester (if pregnant)</label>
                                            <select class="form-select" name="quantitative_parameters[${quantitativeParameterCount}][critical_values][1][trimester]">
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
                                            <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][critical_values][1][critical_low]" 
                                                   placeholder="e.g., 7.0">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Critical High</label>
                                            <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][critical_values][1][critical_high]" 
                                                   placeholder="e.g., 20.0">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Panic Low</label>
                                            <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][critical_values][1][panic_low]" 
                                                   placeholder="e.g., 5.0">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">Panic High</label>
                                            <input type="number" step="0.01" class="form-control" name="quantitative_parameters[${quantitativeParameterCount}][critical_values][1][panic_high]" 
                                                   placeholder="e.g., 25.0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="addCriticalValues(${quantitativeParameterCount})">
                            <i class="bi bi-plus-circle"></i> Add Critical Values
                        </button>
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
    
    // Subcategory (Test Type) dropdown: populate from test types when category is selected
    const testTypesByCategory = @json($testTypesByCategory ?? collect());
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory');
    const oldSubcategory = @json(old('subcategory', ''));
    
    function populateSubcategory() {
        const categoryId = categorySelect.value;
        subcategorySelect.innerHTML = '<option value="">-- Select Test Type --</option>';
        subcategorySelect.disabled = !categoryId;
        
        if (categoryId) {
            const testTypes = testTypesByCategory[categoryId] || [];
            testTypes.forEach(function(tt) {
                const opt = document.createElement('option');
                opt.value = tt.test_name;
                opt.textContent = tt.test_name + ' (' + (tt.test_code || '') + ')';
                if (oldSubcategory && tt.test_name === oldSubcategory) {
                    opt.selected = true;
                }
                subcategorySelect.appendChild(opt);
            });
        }
    }
    
    categorySelect.addEventListener('change', populateSubcategory);
    // On load: if a category is already selected (e.g. old input after validation), populate subcategory
    if (categorySelect.value) {
        populateSubcategory();
    }
    
    // Form submission debugging
    const form = document.getElementById('templateForm');
    const submitBtn = document.getElementById('submitBtn');
    
    form.addEventListener('submit', function(e) {
        console.log('Form is submitting...');
        console.log('Form action:', form.action);
        console.log('Form method:', form.method);
        
        // Collect all form data
        const formData = new FormData(form);
        console.log('Form data being submitted:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: ${value}`);
        }
        
        // Validate template type specific requirements
        const templateType = templateTypeSelect.value;
        let validationErrors = [];
        
        if (templateType === 'quantitative' || templateType === 'combined') {
            const quantitativeParams = document.querySelectorAll('[id^="quantitative_param_"]');
            if (quantitativeParams.length === 0) {
                validationErrors.push('Please add at least one quantitative parameter');
            }
        }
        
        if (templateType === 'qualitative' || templateType === 'combined') {
            const qualitativeParams = document.querySelectorAll('[id^="qualitative_param_"]');
            if (qualitativeParams.length === 0) {
                validationErrors.push('Please add at least one qualitative parameter');
            }
        }
        
        if (templateType === 'narrative' || templateType === 'combined') {
            const templateContent = document.getElementById('template_content').value;
            if (!templateContent.trim()) {
                validationErrors.push('Please provide narrative template content');
            }
        }
        
        if (validationErrors.length > 0) {
            e.preventDefault();
            alert('Validation Errors:\n' + validationErrors.join('\n'));
            return false;
        }
        
        // Check required fields
        const requiredFields = form.querySelectorAll('[required]');
        let allFilled = true;
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                console.error('Missing required field:', field.name || field.id, 'Label:', field.labels?.[0]?.textContent);
                allFilled = false;
            }
        });
        
        if (!allFilled) {
            console.error('Some required fields are missing - form should be blocked by HTML5 validation');
        } else {
            console.log('All required fields filled - form will submit');
            // Disable button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
        }
    });
    
    console.log('Lab template create page loaded successfully');
    console.log('Form route:', '{{ route("lab.templates.store") }}');
});
</script>
@endsection

