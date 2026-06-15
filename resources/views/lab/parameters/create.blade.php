@extends('layouts.app')

@section('title', 'Create Parameter')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-1" style="color: #1e3a5f;">Create Parameter</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('lab.templates') }}">Templates</a></li>
                <li class="breadcrumb-item"><a href="{{ route('lab.templates.show', $template) }}">{{ $template->template_name }}</a></li>
                <li class="breadcrumb-item active">Create Parameter</li>
            </ol>
        </nav>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 text-dark">New Parameter for: {{ $template->template_name }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('lab.parameters.store', $template->id) }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="parameter_code" class="form-label">Parameter Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('parameter_code') is-invalid @enderror" 
                               id="parameter_code" name="parameter_code" value="{{ old('parameter_code') }}" required>
                        @error('parameter_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">e.g., HGB, WBC, RBC</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="parameter_name" class="form-label">Parameter Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('parameter_name') is-invalid @enderror" 
                               id="parameter_name" name="parameter_name" value="{{ old('parameter_name') }}" required>
                        @error('parameter_name')
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
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="data_type" class="form-label">Data Type <span class="text-danger">*</span></label>
                        <select class="form-select @error('data_type') is-invalid @enderror" 
                                id="data_type" name="data_type" required>
                            <option value="">-- Select --</option>
                            <option value="numeric" {{ old('data_type') == 'numeric' ? 'selected' : '' }}>Numeric</option>
                            <option value="text" {{ old('data_type') == 'text' ? 'selected' : '' }}>Text</option>
                            <option value="boolean" {{ old('data_type') == 'boolean' ? 'selected' : '' }}>Boolean</option>
                            <option value="date" {{ old('data_type') == 'date' ? 'selected' : '' }}>Date</option>
                        </select>
                        @error('data_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="input_type" class="form-label">Input Type <span class="text-danger">*</span></label>
                        <select class="form-select @error('input_type') is-invalid @enderror" 
                                id="input_type" name="input_type" required>
                            <option value="">-- Select --</option>
                            <option value="number" {{ old('input_type') == 'number' ? 'selected' : '' }}>Number</option>
                            <option value="text" {{ old('input_type') == 'text' ? 'selected' : '' }}>Text</option>
                            <option value="select" {{ old('input_type') == 'select' ? 'selected' : '' }}>Dropdown</option>
                            <option value="radio" {{ old('input_type') == 'radio' ? 'selected' : '' }}>Radio Buttons</option>
                            <option value="textarea" {{ old('input_type') == 'textarea' ? 'selected' : '' }}>Textarea</option>
                            <option value="rich_text" {{ old('input_type') == 'rich_text' ? 'selected' : '' }}>Rich Text (WYSIWYG)</option>
                        </select>
                        @error('input_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="unit" class="form-label">Unit</label>
                        <input type="text" class="form-control @error('unit') is-invalid @enderror" 
                               id="unit" name="unit" value="{{ old('unit') }}" placeholder="g/dL, mg/dL, etc.">
                        @error('unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="decimal_places" class="form-label">Decimal Places</label>
                        <input type="number" class="form-control @error('decimal_places') is-invalid @enderror" 
                               id="decimal_places" name="decimal_places" value="{{ old('decimal_places', 2) }}" min="0" max="10">
                        @error('decimal_places')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3" id="options_section" style="display: none;">
                    <label for="input_options" class="form-label">Input Options (JSON array)</label>
                    <textarea class="form-control @error('input_options') is-invalid @enderror" 
                              id="input_options" name="input_options" rows="3" 
                              placeholder='["Positive", "Negative", "Indeterminate"]'>{{ old('input_options') }}</textarea>
                    @error('input_options')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">For dropdown/radio options. Format: ["Option 1", "Option 2"]</small>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                               id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="is_required" 
                                   name="is_required" {{ old('is_required', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_required">Required</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_critical" 
                                   name="is_critical" {{ old('is_critical') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_critical">Critical Parameter</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" 
                                   name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Create Parameter
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
    const inputType = document.getElementById('input_type');
    const optionsSection = document.getElementById('options_section');
    
    inputType.addEventListener('change', function() {
        if (this.value === 'select' || this.value === 'radio') {
            optionsSection.style.display = 'block';
        } else {
            optionsSection.style.display = 'none';
        }
    });
});
</script>
@endsection

