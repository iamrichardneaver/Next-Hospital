@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">System Settings</h1>
            <p class="text-secondary mb-0">Configure system preferences and settings</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('settings.payment') }}" class="btn btn-outline-primary">
                <i class="bi bi-credit-card me-1"></i> Payment Gateway
            </a>
            <a href="{{ route('settings.jitsi') }}" class="btn btn-outline-info">
                <i class="bi bi-camera-video me-1"></i> Jitsi Settings
            </a>
        </div>
    </div>
    
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <!-- General Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0 text-dark">
                    <i class="bi bi-gear me-2"></i>General Settings
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hospital Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="hospital_name" 
                               value="{{ optional($settings['general']->firstWhere('key', 'hospital_name'))->value ?? ($hospitalBranding['name'] ?? 'Hospital') }}" required>
                        <small class="text-muted">Your hospital or clinic name</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">System Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="system_email" 
                               value="{{ optional($settings['general']->firstWhere('key', 'system_email'))->value ?? '' }}" required>
                        <small class="text-muted">Main contact email</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">System Phone</label>
                        <input type="text" class="form-control" name="system_phone" 
                               value="{{ optional($settings['general']->firstWhere('key', 'system_phone'))->value ?? '' }}">
                        <small class="text-muted">Contact phone number</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Timezone</label>
                        <select class="form-select" name="timezone">
                            <option value="Africa/Accra" {{ optional($settings['general']->firstWhere('key', 'timezone'))->value == 'Africa/Accra' ? 'selected' : '' }}>Africa/Accra (GMT)</option>
                            <option value="Africa/Lagos" {{ optional($settings['general']->firstWhere('key', 'timezone'))->value == 'Africa/Lagos' ? 'selected' : '' }}>Africa/Lagos (WAT)</option>
                            <option value="Africa/Johannesburg" {{ optional($settings['general']->firstWhere('key', 'timezone'))->value == 'Africa/Johannesburg' ? 'selected' : '' }}>Africa/Johannesburg (SAST)</option>
                            <option value="UTC" {{ optional($settings['general']->firstWhere('key', 'timezone'))->value == 'UTC' ? 'selected' : '' }}>UTC</option>
                        </select>
                        <small class="text-muted">System timezone</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="2">{{ optional($settings['general']->firstWhere('key', 'address'))->value ?? '' }}</textarea>
                    <small class="text-muted">Hospital physical address</small>
                </div>
            </div>
        </div>
        
        <!-- Billing Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0 text-dark">
                    <i class="bi bi-credit-card me-2"></i>Billing Settings
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Currency Code</label>
                        <input type="text" class="form-control" name="currency" 
                               value="{{ optional($settings['billing']->firstWhere('key', 'currency'))->value ?? 'GHS' }}">
                        <small class="text-muted">e.g., GHS, USD, EUR</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Currency Symbol</label>
                        <input type="text" class="form-control" name="currency_symbol" 
                               value="{{ optional($settings['billing']->firstWhere('key', 'currency_symbol'))->value ?? '₵' }}">
                        <small class="text-muted">e.g., ₵, $, €</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tax Rate (%)</label>
                        <input type="number" step="0.01" class="form-control" name="tax_rate" 
                               value="{{ optional($settings['billing']->firstWhere('key', 'tax_rate'))->value ?? '0' }}">
                        <small class="text-muted">Default tax percentage</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Registration Fee (One-time for new patients) -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0 text-dark">
                    <i class="bi bi-cash-coin me-2"></i>Registration Fee (Pricing)
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">A one-time fee charged to every new patient when they register. The amount is added to their first bill and collected at the cashier. Set to 0 to disable.</p>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Registration Fee Amount ({{ $systemSettings->currency ?? 'GHS' }})</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="registration_fee" 
                               value="{{ old('registration_fee', $systemSettings->registration_fee ?? 0) }}">
                        <small class="text-muted">e.g., 10.00 for GH₵10</small>
                    </div>
                    <div class="col-md-8 mb-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="registration_fee_apply_to_new_patients" value="0">
                            <input type="checkbox" class="form-check-input" name="registration_fee_apply_to_new_patients" value="1" id="registration_fee_apply"
                                {{ old('registration_fee_apply_to_new_patients', $systemSettings->registration_fee_apply_to_new_patients ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="registration_fee_apply">Apply to all new patients (force registration fee for every new registration)</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lab Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0 text-dark">
                    <i class="bi bi-clipboard2-pulse me-2"></i>Laboratory Settings
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Default Turnaround Time (Hours)</label>
                        <input type="number" class="form-control" name="default_lab_tat" 
                               value="{{ optional($settings['lab']->firstWhere('key', 'default_lab_tat'))->value ?? '24' }}">
                        <small class="text-muted">Default time to complete lab tests</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Branding Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0 text-dark">
                    <i class="bi bi-palette me-2"></i>Branding & Appearance
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Platform Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="platform_name" 
                               value="{{ $branding->platform_name ?? ($hospitalBranding['name'] ?? 'Hospital') }}" required>
                        <small class="text-muted">Displayed in the application</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Business Name</label>
                        <input type="text" class="form-control" name="business_name" 
                               value="{{ $branding->business_name ?? '' }}">
                        <small class="text-muted">Official business/legal name</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Business Email</label>
                        <input type="email" class="form-control" name="business_email" 
                               value="{{ $branding->business_email ?? '' }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Business Phone</label>
                        <input type="text" class="form-control" name="business_phone" 
                               value="{{ $branding->business_phone ?? '' }}">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Business Address</label>
                    <textarea class="form-control" name="business_address" rows="2">{{ $branding->business_address ?? '' }}</textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Website URL</label>
                    <input type="url" class="form-control" name="business_website" 
                           value="{{ $branding->business_website ?? '' }}" placeholder="https://example.com">
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Logo</label>
                        @if($branding->hasFile('logo_path') && $branding->logo_url)
                        <div class="mb-2" id="logo-preview-container">
                            <img src="{{ $branding->logo_url }}" alt="Logo" class="img-thumbnail" id="logo-preview" style="max-height: 80px;">
                        </div>
                        @else
                        <div class="mb-2" id="logo-preview-container" style="display: none;"></div>
                        @endif
                        <input type="file" class="form-control" name="logo" accept="image/*">
                        <small class="text-muted">PNG, JPG, or GIF. Max 2MB. Recommended: 200x60px</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Favicon</label>
                        @if($branding->hasFile('favicon_path') && $branding->favicon_url)
                        <div class="mb-2" id="favicon-preview-container">
                            <img src="{{ $branding->favicon_url }}" alt="Favicon" class="img-thumbnail" id="favicon-preview" style="max-height: 32px;">
                        </div>
                        @else
                        <div class="mb-2" id="favicon-preview-container" style="display: none;"></div>
                        @endif
                        <input type="file" class="form-control" name="favicon" accept="image/*">
                        <small class="text-muted">ICO or PNG. Recommended: 32x32px or 16x16px</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Primary Color</label>
                        <input type="color" class="form-control form-control-color" name="primary_color" 
                               value="{{ $branding->primary_color ?? '#009ef7' }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Secondary Color</label>
                        <input type="color" class="form-control form-control-color" name="secondary_color" 
                               value="{{ $branding->secondary_color ?? '#f1f1f1' }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Accent Color</label>
                        <input type="color" class="form-control form-control-color" name="accent_color" 
                               value="{{ $branding->accent_color ?? '#ffc700' }}">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ID Prefix Settings -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-dark">
                    <i class="bi bi-hash me-2"></i>ID Prefix Settings
                </h5>
                <a href="{{ route('id-prefixes.index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-gear me-1"></i> Manage All Prefixes
                </a>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> ID prefix patterns control how system generates unique IDs for different entities. 
                    Visit the <a href="{{ route('id-prefixes.index') }}" class="alert-link">full ID Prefix management page</a> to edit patterns.
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Entity Type</th>
                                <th>Pattern</th>
                                <th>Example</th>
                                <th>Status</th>
                                <th>Current Sequence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($idPrefixes->take(10) as $prefix)
                            <tr>
                                <td><strong>{{ ucfirst(str_replace('_', ' ', $prefix->entity_type)) }}</strong></td>
                                <td><code class="text-primary">{{ $prefix->pattern }}</code></td>
                                <td><span class="badge bg-light text-dark">{{ $prefix->formatId() }}</span></td>
                                <td>
                                    <span class="badge bg-{{ $prefix->is_active ? 'success' : 'secondary' }}">
                                        {{ $prefix->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    @if($prefix->is_locked)
                                    <span class="badge bg-warning">
                                        <i class="bi bi-lock-fill"></i> Locked
                                    </span>
                                    @endif
                                </td>
                                <td>{{ number_format($prefix->current_sequence) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($idPrefixes->count() > 10)
                <div class="text-center mt-3">
                    <p class="text-muted">Showing 10 of {{ $idPrefixes->count() }} ID prefix settings</p>
                    <a href="{{ route('id-prefixes.index') }}" class="btn btn-sm btn-outline-primary">
                        View All {{ $idPrefixes->count() }} Settings
                    </a>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Clean Data Feature (Super Admin Only) -->
        @can('manage_data_cleanup') @if($cleanableModules)
        <div class="card mb-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0 text-white">
                    <i class="bi bi-exclamation-triangle me-2"></i>Data Cleanup (Super Admin Only)
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Warning:</strong> This feature allows you to completely clear data from system modules. 
                    This action cannot be undone. Use with extreme caution.
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <h6 class="text-muted mb-3">System Statistics</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h4 class="text-primary mb-1">{{ $systemStats['total_modules'] }}</h4>
                                        <small class="text-muted">Modules</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h4 class="text-info mb-1">{{ number_format($systemStats['total_tables']) }}</h4>
                                        <small class="text-muted">Tables</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h4 class="text-warning mb-1">{{ number_format($systemStats['total_records']) }}</h4>
                                        <small class="text-muted">Total Records</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h4 class="text-success mb-1">{{ $systemStats['modules_with_data'] }}</h4>
                                        <small class="text-muted">With Data</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="{{ route('settings.clean-data') }}" class="btn btn-danger btn-lg">
                            <i class="bi bi-trash3 me-2"></i>Manage Data Cleanup
                        </a>
                        <p class="text-muted mt-2 small">Access detailed cleanup options</p>
                    </div>
                </div>
            </div>
        </div>
        @endif @endcan

        <!-- Save Button -->
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save Settings
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </a>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Logo preview on file select
    document.querySelector('input[name="logo"]')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = e.target.closest('.col-md-6').querySelector('.img-thumbnail');
                if (preview) {
                    preview.src = event.target.result;
                } else {
                    // Create new preview if none exists
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.className = 'img-thumbnail mb-2';
                    img.style.maxHeight = '80px';
                    const container = document.createElement('div');
                    container.className = 'mb-2';
                    container.appendChild(img);
                    e.target.parentElement.insertBefore(container, e.target);
                }
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Favicon preview on file select
    document.querySelector('input[name="favicon"]')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = e.target.closest('.col-md-6').querySelector('.img-thumbnail');
                if (preview) {
                    preview.src = event.target.result;
                } else {
                    // Create new preview if none exists
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.className = 'img-thumbnail mb-2';
                    img.style.maxHeight = '32px';
                    const container = document.createElement('div');
                    container.className = 'mb-2';
                    container.appendChild(img);
                    e.target.parentElement.insertBefore(container, e.target);
                }
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Show loading state on form submit
    document.querySelector('form')?.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        }
    });
</script>
@endpush
@endsection
