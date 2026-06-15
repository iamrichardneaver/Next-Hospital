@extends('layouts.app')

@section('title', 'Edit Test')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-1" style="color: #1e3a5f;">Edit Test</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('lab.tests') }}">Tests</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 text-dark">Edit Test: {{ $test->test_name }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('lab.tests.update', $test) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="test_code" class="form-label">Test Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('test_code') is-invalid @enderror" 
                               id="test_code" name="test_code" value="{{ old('test_code', $test->test_code) }}" required>
                        @error('test_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="test_name" class="form-label">Test Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('test_name') is-invalid @enderror" 
                               id="test_name" name="test_name" value="{{ old('test_name', $test->test_name) }}" required>
                        @error('test_name')
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
                                <option value="{{ $category->id }}" {{ old('category_id', $test->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="test_type_id" class="form-label">Test Type (Optional)</label>
                        <select class="form-select @error('test_type_id') is-invalid @enderror" 
                                id="test_type_id" name="test_type_id">
                            <option value="">-- Select Test Type --</option>
                        </select>
                        @error('test_type_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="template_id" class="form-label">Template (Optional)</label>
                        <select class="form-select @error('template_id') is-invalid @enderror" 
                                id="template_id" name="template_id">
                            <option value="">-- Select Template --</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" {{ old('template_id', $test->template_id) == $template->id ? 'selected' : '' }}>
                                    {{ $template->template_name }} ({{ ucfirst($template->template_type) }})
                                </option>
                            @endforeach
                        </select>
                        @error('template_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="specimen_type" class="form-label">Specimen Type</label>
                        <input type="text" class="form-control @error('specimen_type') is-invalid @enderror" 
                               id="specimen_type" name="specimen_type" value="{{ old('specimen_type', $test->specimen_type) }}">
                        @error('specimen_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" name="description" rows="2">{{ old('description', $test->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="cost" class="form-label">Cost (GHS)</label>
                        <input type="number" step="0.01" class="form-control @error('cost') is-invalid @enderror" 
                               id="cost" name="cost" value="{{ old('cost', $test->cost) }}" min="0">
                        @error('cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="nhis_cost" class="form-label">NHIS Cost (GHS)</label>
                        <input type="number" step="0.01" class="form-control @error('nhis_cost') is-invalid @enderror" 
                               id="nhis_cost" name="nhis_cost" value="{{ old('nhis_cost', $test->nhis_cost) }}" min="0">
                        @error('nhis_cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="turnaround_hours" class="form-label">Turnaround (hours)</label>
                        <input type="number" class="form-control @error('turnaround_hours') is-invalid @enderror" 
                               id="turnaround_hours" name="turnaround_hours" value="{{ old('turnaround_hours', $test->turnaround_hours) }}" min="1">
                        @error('turnaround_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                               id="sort_order" name="sort_order" value="{{ old('sort_order', $test->sort_order) }}" min="0">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="nhis_covered" 
                                   name="nhis_covered" {{ old('nhis_covered', $test->nhis_covered) ? 'checked' : '' }}>
                            <label class="form-check-label" for="nhis_covered">
                                NHIS Covered
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" 
                                   name="is_active" {{ old('is_active', $test->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Test
                    </button>
                    <a href="{{ route('lab.tests') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_id');
    const testTypeSelect = document.getElementById('test_type_id');
    const currentTestTypeId = '{{ $test->test_type_id }}';
    
    // Function to filter test types based on category
    function filterTestTypes(categoryId, preserveSelection = false) {
        // Clear current options except the first one (placeholder)
        testTypeSelect.innerHTML = '<option value="">-- Select Test Type --</option>';
        
        if (!categoryId) {
            return;
        }
        
        // Make AJAX call to get filtered test types
        fetch(`{{ route('lab.test-types-by-category') }}?category_id=${categoryId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.test_types && data.test_types.length > 0) {
                data.test_types.forEach(testType => {
                    const option = document.createElement('option');
                    option.value = testType.id;
                    option.textContent = `${testType.test_code} - ${testType.test_name}`;
                    
                    // Preserve the current selection when loading
                    if (preserveSelection && testType.id == currentTestTypeId) {
                        option.selected = true;
                    }
                    
                    testTypeSelect.appendChild(option);
                });
            } else {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No test types available for this category';
                option.disabled = true;
                testTypeSelect.appendChild(option);
            }
        })
        .catch(error => {
            console.error('Error fetching test types:', error);
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Error loading test types';
            option.disabled = true;
            testTypeSelect.appendChild(option);
        });
    }
    
    // Event listener for category change
    categorySelect.addEventListener('change', function() {
        const selectedCategoryId = this.value;
        filterTestTypes(selectedCategoryId, false);
    });
    
    // Trigger on page load to filter test types for the current category
    if (categorySelect.value) {
        filterTestTypes(categorySelect.value, true);
    }
});
</script>
@endpush
