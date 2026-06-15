@extends('layouts.app')

@section('title', 'New Lab Request')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1" style="color: #1e3a5f;">New Lab Request</h1><p class="text-secondary mb-0">Create a new laboratory test request</p></div>
        <a href="{{ route('lab.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Cancel</a>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('lab.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                            <select class="form-select @error('patient_id') is-invalid @enderror" id="patient_id" name="patient_id" required>
                                <option value="">Select Patient</option>
                                @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                                    {{ $patient->patient_number }} - {{ $patient->full_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="test_category" class="form-label">Test Category <span class="text-danger">*</span></label>
                                <select class="form-select @error('test_category') is-invalid @enderror" id="test_category" name="test_category" required>
                                    <option value="">Select Category</option>
                                    @foreach($testCategories as $category)
                                    <option value="{{ $category->id }}" {{ old('test_category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('test_category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="test_type" class="form-label">Test Type(s) <span class="text-danger">*</span></label>
                                <select class="form-select @error('test_types') is-invalid @enderror" id="test_type" name="test_types[]" multiple size="6">
                                    <option value="">Select a category first</option>
                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple tests for one request.</small>
                                @error('test_types')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <input type="hidden" name="test_type" id="test_type_primary" value="">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Clinical Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('lab.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Create Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('test_category');
    const testTypeSelect = document.getElementById('test_type');
    
    // Handle category selection change
    categorySelect.addEventListener('change', function() {
        const selectedCategory = this.value;
        
        // Clear and disable test type dropdown
        testTypeSelect.innerHTML = '<option value="">Loading test types...</option>';
        testTypeSelect.disabled = true;
        
        if (!selectedCategory) {
            testTypeSelect.innerHTML = '<option value="">Select a category first</option>';
            testTypeSelect.disabled = true;
            return;
        }
        
        // Fetch test types for selected category
        fetch(`{{ route('lab.test-types-by-category') }}?category_id=${encodeURIComponent(selectedCategory)}`)
            .then(response => response.json())
            .then(data => {
                // Clear options
                testTypeSelect.innerHTML = '';
                
                if (data.test_types && data.test_types.length > 0) {
                    // Multi-select: no default empty option so user can pick one or more
                    data.test_types.forEach(testType => {
                        const option = document.createElement('option');
                        option.value = testType.id;
                        option.textContent = `${testType.test_code || ''} - ${testType.test_name}`;
                        testTypeSelect.appendChild(option);
                    });
                    
                    testTypeSelect.disabled = false;
                } else {
                    // No test types found
                    const noOption = document.createElement('option');
                    noOption.value = '';
                    noOption.textContent = 'No test types available for this category';
                    testTypeSelect.appendChild(noOption);
                    testTypeSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error fetching test types:', error);
                testTypeSelect.innerHTML = '<option value="">Error loading test types</option>';
                testTypeSelect.disabled = true;
            });
    });
    
    // Handle form submission: ensure at least one test type selected; set primary for backward compat
    document.querySelector('form').addEventListener('submit', function(e) {
        const selected = Array.from(testTypeSelect.selectedOptions).map(o => o.value).filter(Boolean);
        if (selected.length === 0) {
            e.preventDefault();
            alert('Please select at least one test type.');
            testTypeSelect.focus();
            return;
        }
        document.getElementById('test_type_primary').value = selected[0];
    });
});
</script>
@endpush
