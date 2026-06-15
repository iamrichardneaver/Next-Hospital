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
    
    <title>Forgot Password - {{ BrandingHelper::getPlatformName() }}</title>
    
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
        
        .back-to-login {
            color: var(--accent);
            text-decoration: none;
        }
        
        .back-to-login:hover {
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
        
        .info-text {
            color: #8895a7;
            font-size: 14px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <div class="login-logo">
                @if(BrandingHelper::hasLogo())
                    <img src="{{ BrandingHelper::getLogoUrl() }}" alt="{{ BrandingHelper::getPlatformName() }}" 
                         style="max-height: 80px; max-width: 200px; object-fit: contain;">
                @else
                    <i class="bi bi-key"></i>
                @endif
                <h2>Forgot Password?</h2>
                <p class="info-text">Enter your email address and we'll send you instructions to reset your password.</p>
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
            
            @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <!-- Forgot Password Form -->
            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                
                <!-- Email -->
                <div class="mb-4">
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
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-envelope me-2"></i>
                    Send Reset Link
                </button>
            </form>
            
            <!-- Additional Links -->
            <div class="text-center mt-4">
                <small style="color: #8895a7;">
                    Remember your password? 
                    <a href="{{ route('login') }}" class="text-decoration-none back-to-login">
                        <i class="bi bi-arrow-left me-1"></i>
                        Back to Login
                    </a>
                </small>
            </div>
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
</body>
</html>
