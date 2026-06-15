@extends('layouts.app')

@section('title', 'Create Reference Range')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-1" style="color: #1e3a5f;">Create Reference Range</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('lab.templates') }}">Templates</a></li>
                <li class="breadcrumb-item"><a href="{{ route('lab.templates.show', $parameter->template) }}">{{ $parameter->template->template_name }}</a></li>
                <li class="breadcrumb-item active">Add Range for {{ $parameter->parameter_name }}</li>
            </ol>
        </nav>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 text-dark">New Reference Range for: {{ $parameter->parameter_name }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('lab.reference-ranges.store', $parameter->id) }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="age_group" class="form-label">Age Group <span class="text-danger">*</span></label>
                        <select class="form-select @error('age_group') is-invalid @enderror" 
                                id="age_group" name="age_group" required>
                            <option value="">-- Select --</option>
                            <option value="Newborn" {{ old('age_group') == 'Newborn' ? 'selected' : '' }}>Newborn (< 1 year)</option>
                            <option value="Infant" {{ old('age_group') == 'Infant' ? 'selected' : '' }}>Infant (1-2 years)</option>
                            <option value="Child" {{ old('age_group') == 'Child' ? 'selected' : '' }}>Child (2-12 years)</option>
                            <option value="Adolescent" {{ old('age_group') == 'Adolescent' ? 'selected' : '' }}>Adolescent (12-18 years)</option>
                            <option value="Adult" {{ old('age_group', 'Adult') == 'Adult' ? 'selected' : '' }}>Adult (18-65 years)</option>
                            <option value="Elderly" {{ old('age_group') == 'Elderly' ? 'selected' : '' }}>Elderly (> 65 years)</option>
                        </select>
                        @error('age_group')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                        <select class="form-select @error('gender') is-invalid @enderror" 
                                id="gender" name="gender" required>
                            <option value="">-- Select --</option>
                            <option value="Both" {{ old('gender', 'Both') == 'Both' ? 'selected' : '' }}>Both</option>
                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_pregnant" 
                                   name="is_pregnant" {{ old('is_pregnant') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_pregnant">
                                Pregnant
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3" id="trimester_section" style="display: none;">
                        <label for="pregnancy_trimester" class="form-label">Pregnancy Trimester</label>
                        <select class="form-select @error('pregnancy_trimester') is-invalid @enderror" 
                                id="pregnancy_trimester" name="pregnancy_trimester">
                            <option value="">-- Select --</option>
                            <option value="First" {{ old('pregnancy_trimester') == 'First' ? 'selected' : '' }}>First Trimester</option>
                            <option value="Second" {{ old('pregnancy_trimester') == 'Second' ? 'selected' : '' }}>Second Trimester</option>
                            <option value="Third" {{ old('pregnancy_trimester') == 'Third' ? 'selected' : '' }}>Third Trimester</option>
                        </select>
                        @error('pregnancy_trimester')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="min_operator" class="form-label">Min Operator</label>
                        <select class="form-select @error('min_operator') is-invalid @enderror" 
                                id="min_operator" name="min_operator">
                            <option value=">=" {{ old('min_operator', '>=') == '>=' ? 'selected' : '' }}>>=</option>
                            <option value=">" {{ old('min_operator') == '>' ? 'selected' : '' }}>></option>
                        </select>
                        @error('min_operator')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="min_value" class="form-label">Minimum Value</label>
                        <input type="number" step="0.0001" class="form-control @error('min_value') is-invalid @enderror" 
                               id="min_value" name="min_value" value="{{ old('min_value') }}">
                        @error('min_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="max_value" class="form-label">Maximum Value</label>
                        <input type="number" step="0.0001" class="form-control @error('max_value') is-invalid @enderror" 
                               id="max_value" name="max_value" value="{{ old('max_value') }}">
                        @error('max_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="max_operator" class="form-label">Max Operator</label>
                        <select class="form-select @error('max_operator') is-invalid @enderror" 
                                id="max_operator" name="max_operator">
                            <option value="<=" {{ old('max_operator', '<=') == '<=' ? 'selected' : '' }}><=</option>
                            <option value="<" {{ old('max_operator') == '<' ? 'selected' : '' }}><</option>
                        </select>
                        @error('max_operator')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="unit" class="form-label">Unit</label>
                        <input type="text" class="form-control @error('unit') is-invalid @enderror" 
                               id="unit" name="unit" value="{{ old('unit', $parameter->unit) }}">
                        @error('unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="source" class="form-label">Source</label>
                        <input type="text" class="form-control @error('source') is-invalid @enderror" 
                               id="source" name="source" value="{{ old('source') }}" 
                               placeholder="e.g., WHO, CDC, CLSI">
                        @error('source')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="reference" class="form-label">Reference/Citation</label>
                    <input type="text" class="form-control @error('reference') is-invalid @enderror" 
                           id="reference" name="reference" value="{{ old('reference') }}">
                    @error('reference')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                              id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_active" 
                               name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Create Reference Range
                    </button>
                    <a href="{{ route('lab.templates.show', $parameter->template) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isPregnant = document.getElementById('is_pregnant');
    const trimesterSection = document.getElementById('trimester_section');
    
    isPregnant.addEventListener('change', function() {
        if (this.checked) {
            trimesterSection.style.display = 'block';
        } else {
            trimesterSection.style.display = 'none';
        }
    });
});
</script>
@endsection

