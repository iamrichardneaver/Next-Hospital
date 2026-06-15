@extends('layouts.app')

@section('title', 'Create New User')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Create New User</h1>
            <p class="text-secondary mb-0">Add a new user to the system with appropriate role and permissions</p>
        </div>
        <div>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>
    
    <!-- User Creation Form -->
    <div class="row">
        <div class="col-lg-8 col-xl-6 mx-auto">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark">
                            <i class="bi bi-person-circle me-2"></i>User Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('first_name') is-invalid @enderror" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="{{ old('first_name') }}" 
                                       placeholder="Enter first name"
                                       required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('last_name') is-invalid @enderror" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="{{ old('last_name') }}" 
                                       placeholder="Enter last name"
                                       required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       placeholder="user@example.com"
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" 
                                       name="phone" 
                                       value="{{ old('phone') }}" 
                                       placeholder="+233 XX XXX XXXX">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Enter strong password"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                
                                <!-- Password Requirements -->
                                <div class="mt-2">
                                    <small class="text-muted d-block mb-1"><strong>Password must contain:</strong></small>
                                    <ul class="list-unstyled mb-0" style="font-size: 0.875rem;">
                                        <li id="req-length" class="text-muted"><i class="bi bi-circle me-1"></i> At least 8 characters</li>
                                        <li id="req-uppercase" class="text-muted"><i class="bi bi-circle me-1"></i> One uppercase letter (A-Z)</li>
                                        <li id="req-lowercase" class="text-muted"><i class="bi bi-circle me-1"></i> One lowercase letter (a-z)</li>
                                        <li id="req-number" class="text-muted"><i class="bi bi-circle me-1"></i> One number (0-9)</li>
                                        <li id="req-special" class="text-muted"><i class="bi bi-circle me-1"></i> One special character (!@#$%^&*)</li>
                                    </ul>
                                </div>
                                
                                <!-- Password Strength Indicator -->
                                <div class="mt-2">
                                    <small class="text-muted">Password Strength:</small>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" id="password-strength-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small id="password-strength-text" class="text-muted"></small>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       placeholder="Re-enter password"
                                       required>
                                <small class="text-muted mt-1 d-block">Must match the password above</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Role Assignment -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-dark">
                            <i class="bi bi-shield-check me-2"></i>Role & Permissions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="role" class="form-label">User Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror" 
                                    id="role" 
                                    name="role" 
                                    required>
                                <option value="">Select a role</option>
                                @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" id="branch-field" style="display: none;">
                            <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                            <select class="form-select @error('branch_id') is-invalid @enderror"
                                    id="branch_id"
                                    name="branch_id">
                                @foreach($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ (string) old('branch_id', $defaultBranch?->id) === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                    @if($defaultBranch && $branch->id === $defaultBranch->id)
                                        (default)
                                    @endif
                                </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Staff users are assigned to this branch for consultations, lab, and other clinical data.</small>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong> Role permissions will be automatically assigned based on the selected role. 
                            You can modify individual permissions after creating the user.
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> Create User
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const staffRoles = @json(\App\Services\BranchAssignmentService::STAFF_ROLES);
    const roleSelect = document.getElementById('role');
    const branchField = document.getElementById('branch-field');
    const branchSelect = document.getElementById('branch_id');

    function toggleBranchField() {
        const isStaff = staffRoles.includes(roleSelect.value);
        branchField.style.display = isStaff ? 'block' : 'none';
        branchSelect.required = isStaff;
    }

    roleSelect.addEventListener('change', toggleBranchField);
    toggleBranchField();

    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('password_confirmation');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');
    
    // Toggle password visibility
    togglePasswordBtn.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        togglePasswordIcon.classList.toggle('bi-eye');
        togglePasswordIcon.classList.toggle('bi-eye-slash');
    });
    
    // Password validation and strength checker
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        // Check requirements
        const hasLength = password.length >= 8;
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        
        // Update requirement indicators
        updateRequirement('req-length', hasLength);
        updateRequirement('req-uppercase', hasUppercase);
        updateRequirement('req-lowercase', hasLowercase);
        updateRequirement('req-number', hasNumber);
        updateRequirement('req-special', hasSpecial);
        
        // Calculate strength
        if (hasLength) strength++;
        if (hasUppercase) strength++;
        if (hasLowercase) strength++;
        if (hasNumber) strength++;
        if (hasSpecial) strength++;
        
        // Update strength bar
        const strengthBar = document.getElementById('password-strength-bar');
        const strengthText = document.getElementById('password-strength-text');
        const percentage = (strength / 5) * 100;
        
        strengthBar.style.width = percentage + '%';
        
        if (strength === 0) {
            strengthBar.className = 'progress-bar';
            strengthText.textContent = '';
        } else if (strength <= 2) {
            strengthBar.className = 'progress-bar bg-danger';
            strengthText.textContent = 'Weak';
            strengthText.className = 'text-danger';
        } else if (strength <= 3) {
            strengthBar.className = 'progress-bar bg-warning';
            strengthText.textContent = 'Fair';
            strengthText.className = 'text-warning';
        } else if (strength === 4) {
            strengthBar.className = 'progress-bar bg-info';
            strengthText.textContent = 'Good';
            strengthText.className = 'text-info';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            strengthText.textContent = 'Strong';
            strengthText.className = 'text-success';
        }
        
        // Check password confirmation match
        if (confirmPasswordInput.value) {
            validatePasswordMatch();
        }
    });
    
    // Password confirmation validation
    confirmPasswordInput.addEventListener('input', validatePasswordMatch);
    
    function validatePasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (password !== confirmPassword) {
            confirmPasswordInput.setCustomValidity('Passwords do not match');
        } else {
            confirmPasswordInput.setCustomValidity('');
        }
    }
    
    function updateRequirement(elementId, isMet) {
        const element = document.getElementById(elementId);
        if (isMet) {
            element.classList.remove('text-muted');
            element.classList.add('text-success');
            element.querySelector('i').classList.remove('bi-circle');
            element.querySelector('i').classList.add('bi-check-circle-fill');
        } else {
            element.classList.remove('text-success');
            element.classList.add('text-muted');
            element.querySelector('i').classList.remove('bi-check-circle-fill');
            element.querySelector('i').classList.add('bi-circle');
        }
    }
});
</script>
@endsection
