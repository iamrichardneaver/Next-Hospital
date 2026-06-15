@php
    use App\Helpers\BrandingHelper;
    $branding = BrandingHelper::getBranding();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Patient Registration - {{ BrandingHelper::getPlatformName() }}</title>
    
    <!-- Favicon -->
    @if(BrandingHelper::hasFavicon())
        <link rel="icon" type="image/x-icon" href="{{ BrandingHelper::getFaviconUrl() }}">
    @endif
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary: {{ BrandingHelper::getPrimaryColor() }};
            --secondary: {{ BrandingHelper::getSecondaryColor() }};
            --accent: {{ BrandingHelper::getAccentColor() }};
            --dark: #0a0f1a;
        }
        
        body {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            padding: 40px 0;
        }
        
        .register-container {
            width: 100%;
            max-width: 700px;
            padding: 20px;
        }
        
        .register-card {
            background: rgba(15, 28, 46, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .register-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-logo img {
            max-height: 70px;
            max-width: 180px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        
        .register-logo i {
            font-size: 50px;
            color: var(--accent);
            margin-bottom: 10px;
        }
        
        .register-logo h2 {
            color: white;
            margin-top: 10px;
            font-weight: 700;
            font-size: 24px;
        }
        
        .register-logo p {
            color: #8895a7;
            margin-top: 5px;
            font-size: 14px;
        }
        
        .form-control {
            background: rgba(10, 15, 26, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 12px 15px;
            border-radius: 10px;
        }
        
        .form-control:focus {
            background: rgba(10, 15, 26, 0.9);
            border-color: var(--accent);
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .form-select {
            background: rgba(10, 15, 26, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 12px 15px;
            border-radius: 10px;
        }
        
        .form-select:focus {
            background: rgba(10, 15, 26, 0.9);
            border-color: var(--accent);
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .form-select option {
            background: #0f1c2e;
            color: white;
        }
        
        .form-label {
            color: #8895a7;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.4);
        }
        
        .btn-secondary {
            background: rgba(136, 149, 167, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(136, 149, 167, 0.3);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .input-group-text {
            background: rgba(10, 15, 26, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #8895a7;
        }
        
        .section-title {
            color: var(--accent);
            font-weight: 600;
            margin: 20px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 16px;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .text-white-muted {
            color: #8895a7;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <!-- Logo -->
            <div class="register-logo">
                @if(BrandingHelper::hasLogo())
                    <img src="{{ BrandingHelper::getLogoUrl() }}" alt="{{ BrandingHelper::getPlatformName() }}">
                @else
                    <i class="bi bi-hospital"></i>
                @endif
                <h2>Patient Registration</h2>
                <p>{{ BrandingHelper::getPlatformName() }}</p>
            </div>
            
            <!-- Alerts -->
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <!-- Registration Form -->
            <form action="{{ route('register.submit') }}" method="POST" id="registrationForm">
                @csrf
                
                <!-- Personal Information -->
                <div class="section-title">
                    <i class="bi bi-person-fill me-2"></i>Personal Information
                </div>

                <div class="mb-3">
                    <label for="branch_id" class="form-label">Branch <span class="required">*</span></label>
                    @if($branches->isEmpty())
                        <div class="alert alert-warning mb-0" style="background: rgba(241, 196, 15, 0.1); border: 1px solid rgba(241, 196, 15, 0.3); color: #f1c40f;">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            No active branches are available. Please contact the hospital administrator.
                        </div>
                    @else
                        <select class="form-select @error('branch_id') is-invalid @enderror"
                                id="branch_id"
                                name="branch_id"
                                required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    @endif
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">First Name <span class="required">*</span></label>
                        <input type="text" 
                               class="form-control @error('first_name') is-invalid @enderror" 
                               id="first_name" 
                               name="first_name" 
                               value="{{ old('first_name') }}" 
                               required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name <span class="required">*</span></label>
                        <input type="text" 
                               class="form-control @error('last_name') is-invalid @enderror" 
                               id="last_name" 
                               name="last_name" 
                               value="{{ old('last_name') }}" 
                               required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="other_names" class="form-label">Other Names</label>
                    <input type="text" 
                           class="form-control @error('other_names') is-invalid @enderror" 
                           id="other_names" 
                           name="other_names" 
                           value="{{ old('other_names') }}">
                    @error('other_names')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="gender" class="form-label">Gender <span class="required">*</span></label>
                        <select class="form-select @error('gender') is-invalid @enderror" 
                                id="gender" 
                                name="gender" 
                                required>
                            <option value="">Select Gender</option>
                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="age" class="form-label">Age (Years)</label>
                        <input type="number" 
                               class="form-control @error('age') is-invalid @enderror" 
                               id="age" 
                               name="age" 
                               value="{{ old('age') }}" 
                               min="0" 
                               max="150"
                               placeholder="Enter age">
                        <small class="text-muted">Or provide date of birth</small>
                        @error('age')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input type="date" 
                               class="form-control @error('date_of_birth') is-invalid @enderror" 
                               id="date_of_birth" 
                               name="date_of_birth" 
                               value="{{ old('date_of_birth') }}" 
                               max="{{ date('Y-m-d') }}">
                        <small class="text-muted">Optional - provide age or date of birth</small>
                        @error('date_of_birth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="section-title">
                    <i class="bi bi-telephone-fill me-2"></i>Contact Information
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone Number <span class="required">*</span></label>
                        <input type="tel" 
                               class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" 
                               name="phone" 
                               value="{{ old('phone') }}" 
                               placeholder="0XXXXXXXXX"
                               required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" 
                              id="address" 
                              name="address" 
                              rows="2">{{ old('address') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Additional Information -->
                <div class="section-title">
                    <i class="bi bi-card-list me-2"></i>Additional Information
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nhis_number" class="form-label">NHIS Number</label>
                        <input type="text" 
                               class="form-control @error('nhis_number') is-invalid @enderror" 
                               id="nhis_number" 
                               name="nhis_number" 
                               value="{{ old('nhis_number') }}">
                        @error('nhis_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="ghana_card_number" class="form-label">Ghana Card Number</label>
                        <input type="text" 
                               class="form-control @error('ghana_card_number') is-invalid @enderror" 
                               id="ghana_card_number" 
                               name="ghana_card_number" 
                               value="{{ old('ghana_card_number') }}">
                        @error('ghana_card_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- Emergency Contact -->
                <div class="section-title">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Emergency Contact
                </div>
                
                <div class="mb-3">
                    <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                    <input type="text" 
                           class="form-control @error('emergency_contact_name') is-invalid @enderror" 
                           id="emergency_contact_name" 
                           name="emergency_contact_name" 
                           value="{{ old('emergency_contact_name') }}">
                    @error('emergency_contact_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                        <input type="tel" 
                               class="form-control @error('emergency_contact_phone') is-invalid @enderror" 
                               id="emergency_contact_phone" 
                               name="emergency_contact_phone" 
                               value="{{ old('emergency_contact_phone') }}">
                        @error('emergency_contact_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                        <input type="text" 
                               class="form-control @error('emergency_contact_relationship') is-invalid @enderror" 
                               id="emergency_contact_relationship" 
                               name="emergency_contact_relationship" 
                               value="{{ old('emergency_contact_relationship') }}"
                               placeholder="e.g., Spouse, Parent, Sibling">
                        @error('emergency_contact_relationship')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- Account Security -->
                <div class="section-title">
                    <i class="bi bi-shield-lock-fill me-2"></i>Account Security
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password <span class="required">*</span></label>
                    <input type="password" 
                           class="form-control @error('password') is-invalid @enderror" 
                           id="password" 
                           name="password" 
                           required
                           minlength="8">
                    <small class="text-white-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Password must be at least 8 characters long
                    </small>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm Password <span class="required">*</span></label>
                    <input type="password" 
                           class="form-control" 
                           id="password_confirmation" 
                           name="password_confirmation" 
                           required
                           minlength="8">
                </div>
                
                <!-- Terms and Conditions -->
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="terms" 
                               name="terms" 
                               required>
                        <label class="form-check-label text-white-muted" for="terms">
                            I agree to the Terms and Conditions and Privacy Policy <span class="required">*</span>
                        </label>
                    </div>
                </div>
                
                <!-- Information Alert -->
                <div class="alert alert-info" style="background: rgba(52, 152, 219, 0.1); border: 1px solid rgba(52, 152, 219, 0.3); color: #8895a7;">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <small>
                        Your registration will be reviewed by our team. You will receive an email and SMS notification once your account is activated.
                    </small>
                </div>
                
                <!-- Buttons -->
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <button type="submit" class="btn btn-primary w-100" @if($branches->isEmpty()) disabled @endif>
                            <i class="bi bi-person-plus-fill me-2"></i>
                            Register
                        </button>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="{{ route('login') }}" class="btn btn-secondary w-100">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back to Login
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-4">
            <small style="color: #8895a7;">
                © {{ date('Y') }} {{ BrandingHelper::getPlatformName() }}. All rights reserved.
            </small>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            // You can add visual feedback here if needed
        });
        
        function calculatePasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            return strength;
        }
        
        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const branchSelect = document.getElementById('branch_id');
            if (branchSelect && !branchSelect.value) {
                e.preventDefault();
                alert('Please select a branch.');
                return false;
            }

            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirmation').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    </script>
</body>
</html>
