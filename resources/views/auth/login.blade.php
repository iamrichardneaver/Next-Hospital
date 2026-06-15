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
    
    <!-- Prevent caching to ensure fresh branding -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <title>Login - {{ BrandingHelper::getPlatformName() }}</title>
    
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
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(15, 28, 46, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo img {
            max-height: 80px;
            max-width: 200px;
            object-fit: contain;
            margin-bottom: 15px;
        }
        
        .login-logo i {
            font-size: 60px;
            color: var(--accent);
            margin-bottom: 15px;
        }
        
        .login-logo h2 {
            color: white;
            margin-top: 15px;
            font-weight: 700;
        }
        
        .login-logo p {
            color: #8895a7;
            margin-top: 5px;
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
        
        .form-label {
            color: #8895a7;
            font-weight: 500;
            margin-bottom: 8px;
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
        
        .forgot-password {
            color: var(--accent);
            text-decoration: none;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <div class="login-logo">
                @php
                    $logoUrl = $branding->logo_url;
                    $hasLogo = !empty($branding->getRawOriginal('logo_path')) && !empty($logoUrl);
                @endphp
                @if($hasLogo)
                    <img id="login-logo-img"
                         src="{{ $logoUrl }}"
                         alt="{{ $branding->platform_name ?? 'Logo' }}"
                         style="max-height: 60px; max-width: 200px; object-fit: contain; display: block; margin: 0 auto 15px auto;"
                         onerror="this.style.display='none'; document.getElementById('login-logo-icon').style.display='block';">
                    <i id="login-logo-icon" class="bi bi-hospital" style="display: none;"></i>
                @else
                    <i id="login-logo-icon" class="bi bi-hospital"></i>
                @endif
                <h2>{{ $branding->platform_name ?? 'Hospital' }}</h2>
                @if($branding->business_name)
                    <p>{{ $branding->business_name }}</p>
                @else
                    <p>Healthcare Management System</p>
                @endif
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
            
            <!-- Login Form -->
            <form action="{{ route('login.submit') }}" method="POST">
                @csrf
                
                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               placeholder="Enter your email" 
                               required 
                               autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password" 
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember" style="color: #8895a7;">
                            Remember Me
                        </label>
                    </div>
                    <a href="{{ route('password.request') }}" class="forgot-password small">
                        Forgot Password?
                    </a>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Sign In
                </button>

                {{-- Demo login hidden — uncomment when needed --}}
                {{-- @if(config('app.demo_mode'))
                    <div class="mt-3 p-3 rounded-3" style="background: rgba(10, 15, 26, 0.55); border: 1px solid rgba(255, 255, 255, 0.08);">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <small class="fw-semibold" style="color: #e6eefc;">
                                <i class="bi bi-lightning-charge-fill me-1" style="color: var(--accent);"></i>
                                Demo login (click to auto-fill)
                            </small>
                            <small style="color: #8895a7;">Password: <span class="fw-semibold">password123</span></small>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-light demo-credential" data-email="admin@nexthospital.com" data-password="password123">
                                <i class="bi bi-shield-lock me-1"></i> Super Admin
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light demo-credential" data-email="doctor@nexthospital.com" data-password="password123">
                                <i class="bi bi-person-badge me-1"></i> Doctor
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light demo-credential" data-email="nurse@nexthospital.com" data-password="password123">
                                <i class="bi bi-heart-pulse me-1"></i> Nurse
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light demo-credential" data-email="pharmacist@nexthospital.com" data-password="password123">
                                <i class="bi bi-capsule-pill me-1"></i> Pharmacist
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light demo-credential" data-email="receptionist@nexthospital.com" data-password="password123">
                                <i class="bi bi-person-lines-fill me-1"></i> Receptionist
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light demo-credential" data-email="accountant@nexthospital.com" data-password="password123">
                                <i class="bi bi-calculator me-1"></i> Accountant
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light demo-credential" data-email="lab@nexthospital.com" data-password="password123">
                                <i class="bi bi-eyedropper me-1"></i> Lab Tech
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light demo-credential" data-email="lab.scientist@nexthospital.com" data-password="password123">
                                <i class="bi bi-microscope me-1"></i> Lab Scientist
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light demo-credential" data-email="patient@nexthospital.com" data-password="password123">
                                <i class="bi bi-person me-1"></i> Patient
                            </button>
                        </div>
                    </div>
                @endif --}}
            </form>
            
            <!-- Additional Links -->
            <div class="text-center mt-4">
                <small style="color: #8895a7;">
                    Don't have an account? 
                    <a href="{{ route('register') }}" class="text-decoration-none" style="color: var(--accent);">
                        Create one now!
                    </a>
                </small>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-4">
            <small style="color: #8895a7;">
                © {{ date('Y') }} {{ $hospitalBranding['name'] ?? 'Hospital' }}. All rights reserved.
            </small>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Logo Loading Handler -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoImg = document.getElementById('login-logo-img');
            const logoIcon = document.getElementById('login-logo-icon');
            
            if (logoImg) {
                // Force reload to bypass cache
                const originalSrc = logoImg.src;
                logoImg.src = '';
                logoImg.src = originalSrc;
                
                // Handle image load error
                logoImg.onerror = function() {
                    console.log('Logo image failed to load:', originalSrc);
                    if (logoIcon) {
                        logoImg.style.display = 'none';
                        logoIcon.style.display = 'block';
                    }
                };
                
                // Handle image load success
                logoImg.onload = function() {
                    console.log('Logo image loaded successfully');
                    if (logoIcon) {
                        logoIcon.style.display = 'none';
                    }
                    logoImg.style.display = 'block';
                };
            }

            /* Demo credential auto-fill — uncomment when demo login is restored
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            document.querySelectorAll('.demo-credential').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (!emailInput || !passwordInput) return;
                    emailInput.value = btn.getAttribute('data-email') || '';
                    passwordInput.value = btn.getAttribute('data-password') || '';
                    emailInput.dispatchEvent(new Event('input', { bubbles: true }));
                    passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
                    passwordInput.focus();
                });
            });
            */
        });
    </script>
</body>
</html>
