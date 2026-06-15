<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Next Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 60px 40px;
            text-align: center;
            max-width: 600px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .error-icon {
            font-size: 8rem;
            color: #e74c3c;
            margin-bottom: 30px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }
        
        .error-title {
            font-size: 3.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .error-subtitle {
            font-size: 1.5rem;
            color: #7f8c8d;
            margin-bottom: 30px;
            font-weight: 300;
        }
        
        .error-description {
            font-size: 1.1rem;
            color: #5a6c7d;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .btn-custom {
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary-custom {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
            color: white;
        }
        
        .btn-secondary-custom {
            background: linear-gradient(45deg, #95a5a6, #7f8c8d);
            color: white;
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
        }
        
        .btn-secondary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(149, 165, 166, 0.4);
            color: white;
        }
        
        .btn-success-custom {
            background: linear-gradient(45deg, #27ae60, #229954);
            color: white;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
            color: white;
        }
        
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 80%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }
        
        .search-suggestions {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
            text-align: left;
        }
        
        .suggestion-item {
            padding: 10px 15px;
            margin: 5px 0;
            background: white;
            border-radius: 10px;
            border-left: 4px solid #3498db;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .suggestion-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .hospital-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #3498db, #2980b9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>
    
    <div class="error-container">
        <div class="error-card">
            <div class="hospital-logo">
                <i class="bi bi-hospital"></i>
            </div>
            
            <div class="error-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            
            <h1 class="error-title">404</h1>
            <h2 class="error-subtitle">Page Not Found</h2>
            <p class="error-description">
                Oops! The page you're looking for seems to have wandered off. 
                Don't worry, even the best hospitals sometimes misplace things. 
                Let's get you back on track!
            </p>
            
            <div class="d-flex flex-wrap justify-content-center">
                <button onclick="goBack()" class="btn-custom btn-primary-custom">
                    <i class="bi bi-arrow-left me-2"></i>Go Back
                </button>
                
                <a href="{{ url('/') }}" class="btn-custom btn-success-custom">
                    <i class="bi bi-house me-2"></i>Dashboard
                </a>
                
                <a href="{{ url('/billing') }}" class="btn-custom btn-secondary-custom">
                    <i class="bi bi-receipt me-2"></i>Billing
                </a>
            </div>
            
            <div class="search-suggestions">
                <h5 class="mb-3">
                    <i class="bi bi-lightbulb me-2"></i>Quick Links
                </h5>
                <div class="suggestion-item" onclick="window.location.href='{{ url('/patients') }}'">
                    <i class="bi bi-people me-2"></i>Patient Management
                </div>
                <div class="suggestion-item" onclick="window.location.href='{{ url('/appointments') }}'">
                    <i class="bi bi-calendar-check me-2"></i>Appointments
                </div>
                <div class="suggestion-item" onclick="window.location.href='{{ url('/lab') }}'">
                    <i class="bi bi-flask me-2"></i>Laboratory
                </div>
                <div class="suggestion-item" onclick="window.location.href='{{ url('/pharmacy') }}'">
                    <i class="bi bi-capsule me-2"></i>Pharmacy
                </div>
                <div class="suggestion-item" onclick="window.location.href='{{ url('/radiology') }}'">
                    <i class="bi bi-camera me-2"></i>Radiology
                </div>
            </div>
            
            <div class="mt-4">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    If you believe this is an error, please contact the system administrator.
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '{{ url("/") }}';
            }
        }
        
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.error-card');
            
            // Add hover effect to suggestion items
            const suggestions = document.querySelectorAll('.suggestion-item');
            suggestions.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px) scale(1.02)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0) scale(1)';
                });
            });
            
            // Add click animation to buttons
            const buttons = document.querySelectorAll('.btn-custom');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 150);
                });
            });
        });
    </script>
</body>
</html>
