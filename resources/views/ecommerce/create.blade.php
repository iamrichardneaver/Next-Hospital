@extends('layouts.app')

@section('title', 'Add Store Item')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-plus me-2"></i>Add New Store Item
        </h1>
        <a href="{{ route('ecommerce.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Validation Errors:</h6>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Store Item Information</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('ecommerce.store') }}" id="storeItemForm">
                @csrf
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" 
                                   value="{{ old('sku') }}">
                            @error('sku')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-control @error('category') is-invalid @enderror" required>
                                <option value="">-- Select Category --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>
                                        {{ $cat }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Selling Price (GH₵) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" 
                                   value="{{ old('price') }}" required>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Cost Price (GH₵) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror" 
                                   value="{{ old('cost_price') }}" required>
                            @error('cost_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="stock_quantity" class="form-control @error('stock_quantity') is-invalid @enderror" 
                                   value="{{ old('stock_quantity') }}" required>
                            @error('stock_quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Minimum Stock Level <span class="text-danger">*</span></label>
                            <input type="number" name="minimum_stock" class="form-control @error('minimum_stock') is-invalid @enderror" 
                                   value="{{ old('minimum_stock') }}" required>
                            @error('minimum_stock')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input @error('is_active') is-invalid @enderror" 
                                       id="is_active" name="is_active" 
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Active (Available for sale)</label>
                            </div>
                            @error('is_active')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>

                <div class="form-group mb-0">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Store Item
                    </button>
                    <a href="{{ route('ecommerce.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
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
    console.log('=== E-COMMERCE CREATE PAGE DEBUG START ===');
    console.log('Page loaded at:', new Date().toISOString());
    console.log('Current URL:', window.location.href);
    
    const form = document.getElementById('storeItemForm');
    console.log('Form element found:', form !== null);
    
    if (!form) {
        console.error('CRITICAL: Form element not found!');
        return;
    }
    
    console.log('Form action:', form.action);
    console.log('Form method:', form.method);
    
    // Check CSRF token
    const csrfToken = document.querySelector('input[name="_token"]');
    if (csrfToken) {
        console.log('CSRF Token found:', csrfToken.value.substring(0, 20) + '...');
        console.log('CSRF Token length:', csrfToken.value.length);
    } else {
        console.error('CRITICAL: CSRF Token not found!');
    }
    
    // Check meta CSRF token
    const metaCsrf = document.querySelector('meta[name="csrf-token"]');
    if (metaCsrf) {
        console.log('Meta CSRF Token found:', metaCsrf.content.substring(0, 20) + '...');
    } else {
        console.warn('WARNING: Meta CSRF token not found');
    }
    
    // Monitor all form inputs
    const inputs = form.querySelectorAll('input, select, textarea');
    console.log('Total form inputs:', inputs.length);
    
    // Add submit event listener
    form.addEventListener('submit', function(e) {
        console.log('\n=== FORM SUBMISSION DEBUG ===');
        console.log('Submit event triggered at:', new Date().toISOString());
        console.log('Form is valid:', form.checkValidity());
        
        // Collect all form data
        const formData = new FormData(form);
        console.log('\nForm Data:');
        for (let [key, value] of formData.entries()) {
            if (key === '_token') {
                console.log(`  ${key}: ${value.substring(0, 20)}...`);
            } else {
                console.log(`  ${key}: ${value}`);
            }
        }
        
        // Check required fields
        const requiredFields = form.querySelectorAll('[required]');
        console.log('\nRequired fields validation:');
        let allValid = true;
        requiredFields.forEach(field => {
            const isValid = field.value.trim() !== '';
            console.log(`  ${field.name}: ${isValid ? '✓ Valid' : '✗ Empty'}`);
            if (!isValid) allValid = false;
        });
        
        if (!allValid) {
            console.error('VALIDATION FAILED: Some required fields are empty');
            return; // Let browser handle validation
        }
        
        console.log('\nAll validations passed. Submitting form...');
        console.log('Submit button will be disabled to prevent double submission');
        
        // Disable submit button
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            console.log('Submit button disabled and text changed');
        }
        
        // Monitor form submission
        console.log('\nWaiting for server response...');
        console.log('If nothing happens after 5 seconds, check:');
        console.log('  1. Browser Network tab for request status');
        console.log('  2. Laravel logs at: storage/logs/laravel.log');
        console.log('  3. PHP error logs');
        
        // Set a timeout to detect if submission hangs
        setTimeout(function() {
            console.warn('WARNING: Form submission taking longer than 5 seconds');
            console.warn('Check Network tab for request status');
        }, 5000);
    });
    
    // Monitor form validation errors
    form.addEventListener('invalid', function(e) {
        console.error('Form validation error on field:', e.target.name);
        console.error('Error message:', e.target.validationMessage);
    }, true);
    
    // Monitor AJAX errors (if any)
    window.addEventListener('error', function(e) {
        console.error('JavaScript Error:', e.message);
        console.error('Error source:', e.filename + ':' + e.lineno);
    });
    
    // Monitor unhandled promise rejections
    window.addEventListener('unhandledrejection', function(e) {
        console.error('Unhandled Promise Rejection:', e.reason);
    });
    
    console.log('=== E-COMMERCE CREATE PAGE DEBUG INITIALIZED ===\n');
});

// Monitor page unload (form submission redirect)
window.addEventListener('beforeunload', function(e) {
    console.log('Page is about to unload (redirect or close)');
});
</script>
@endpush
