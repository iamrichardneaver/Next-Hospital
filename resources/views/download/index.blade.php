<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Download {{ $platformName }} App</title>
    @if($branding->favicon_url)
    <link rel="icon" type="image/x-icon" href="{{ $branding->favicon_url }}">
    @endif
    <style>
        :root {
            --primary-color: {{ $primaryColor }};
            --secondary-color: {{ $secondaryColor }};
            --accent-color: {{ $accentColor }};
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f1419 0%, #1a2332 50%, #0f1419 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #fff;
        }

        .container {
            max-width: 900px;
            width: 100%;
            background: rgba(26, 35, 50, 0.8);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header {
            background: linear-gradient(135deg, {{ $secondaryColor }} 0%, {{ $primaryColor }} 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .logo {
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .logo img {
            max-height: 80px;
            max-width: 200px;
            object-fit: contain;
        }

        .logo-text {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
            font-weight: 700;
        }

        .header p {
            font-size: 18px;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 50px 30px;
        }

        .intro {
            text-align: center;
            margin-bottom: 50px;
        }

        .intro h2 {
            font-size: 28px;
            margin-bottom: 15px;
            color: var(--accent-color);
        }

        .intro p {
            font-size: 16px;
            line-height: 1.6;
            color: #cbd5e1;
            max-width: 600px;
            margin: 0 auto;
        }

        .download-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .download-card {
            background: linear-gradient(145deg, #1e293b, #0f172a);
            border-radius: 15px;
            padding: 35px 25px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(96, 165, 250, 0.2);
            position: relative;
            overflow: hidden;
        }

        .download-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, {{ $primaryColor }}, {{ $accentColor }});
        }

        .download-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(59, 130, 246, 0.3);
            border-color: rgba(96, 165, 250, 0.5);
        }

        .card-icon {
            font-size: 64px;
            margin-bottom: 20px;
            display: inline-block;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .download-card h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #fff;
        }

        .download-card p {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
            color: white;
            padding: 15px 35px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
            border: none;
            cursor: pointer;
        }

        .download-btn:hover {
            background: linear-gradient(135deg, {{ $secondaryColor }} 0%, {{ $primaryColor }} 100%);
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(59, 130, 246, 0.4);
        }

        .download-btn:active {
            transform: translateY(0);
        }

        .download-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .version-badge {
            display: inline-block;
            background: rgba(96, 165, 250, 0.15);
            color: var(--accent-color);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }

        .features {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 15px;
            padding: 35px 25px;
            margin-top: 40px;
        }

        .features h3 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 30px;
            color: var(--accent-color);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .feature-icon {
            font-size: 28px;
            min-width: 40px;
        }

        .feature-text h4 {
            font-size: 16px;
            margin-bottom: 5px;
            color: #e2e8f0;
        }

        .feature-text p {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .footer {
            text-align: center;
            padding: 30px;
            background: rgba(15, 23, 42, 0.8);
            color: #94a3b8;
            font-size: 14px;
        }

        .footer a {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer a:hover {
            color: #93c5fd;
        }

        .badge {
            display: inline-block;
            background: rgba(96, 165, 250, 0.2);
            color: var(--accent-color);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 28px;
            }

            .header p {
                font-size: 16px;
            }

            .intro h2 {
                font-size: 24px;
            }

            .download-section {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 40px 20px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .container {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $platformName }} Logo">
                @else
                    <div class="logo-text">🏥</div>
                @endif
            </div>
            <h1>{{ $platformName }}</h1>
            <p>{{ $businessName ? $businessName : 'Healthcare Management at Your Fingertips' }}</p>
        </div>

        <div class="content">
            <div class="intro">
                <h2>Download Our App</h2>
                <p>{{ $appDescription }}</p>
            </div>

            <div class="download-section">
                <div class="download-card">
                    <div class="card-icon">📱</div>
                    <span class="badge">Android App</span>
                    <h3>Mobile Application</h3>
                    <p>Full-featured native Android app with offline support, push notifications, and optimized performance for healthcare professionals.</p>
                    <!-- Debug Info -->
                    <p style="color: #94a3b8; font-size: 10px; margin-bottom: 10px;">
                        Debug: APK Exists = {{ $apkExists ? 'YES' : 'NO' }} | 
                        Path = {{ public_path('nexthospital-app.apk') }} |
                        File Exists = {{ file_exists(public_path('nexthospital-app.apk')) ? 'YES' : 'NO' }}
                    </p>
                    
                    @if($apkExists)
                        <a href="{{ asset('nexthospital-app.apk') }}" class="download-btn" download="{{ $hospitalBranding['name'] ?? 'Hospital' }}-App.apk">
                            <span>⬇</span>
                            <span>Download APK</span>
                        </a>
                        <div class="version-badge">Version {{ $appVersion }} • {{ $apkSize ?? '' }}</div>
                    @else
                        <button class="download-btn disabled" disabled>
                            <span>⚠</span>
                            <span>APK Not Available</span>
                        </button>
                        <p style="color: #ef4444; font-size: 12px; margin-top: 10px;">Build the mobile app first</p>
                    @endif
                </div>

                <div class="download-card">
                    <div class="card-icon">🌐</div>
                    <span class="badge">Progressive Web App</span>
                    <h3>PWA Application</h3>
                    <p>Install our Progressive Web App for a native-like experience that works across all devices and platforms with automatic updates.</p>
                    <a href="{{ url('../pwa-app/index.html') }}" class="download-btn" target="_blank">
                        <span>🚀</span>
                        <span>Launch PWA</span>
                    </a>
                </div>
            </div>

            <div class="features">
                <h3>✨ Key Features</h3>
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">👥</div>
                        <div class="feature-text">
                            <h4>Patient Management</h4>
                            <p>Complete patient records and history</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">📅</div>
                        <div class="feature-text">
                            <h4>Appointments</h4>
                            <p>Schedule and manage appointments</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">🧪</div>
                        <div class="feature-text">
                            <h4>Laboratory</h4>
                            <p>Lab tests and results management</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">💊</div>
                        <div class="feature-text">
                            <h4>Pharmacy</h4>
                            <p>Prescription and inventory tracking</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">💰</div>
                        <div class="feature-text">
                            <h4>Billing</h4>
                            <p>Invoicing and payment processing</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">📊</div>
                        <div class="feature-text">
                            <h4>Reports</h4>
                            <p>Analytics and insights dashboard</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">🔒</div>
                        <div class="feature-text">
                            <h4>Secure</h4>
                            <p>HIPAA compliant and encrypted</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">📡</div>
                        <div class="feature-text">
                            <h4>Offline Support</h4>
                            <p>Works without internet connection</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $businessName ?? $platformName }}. All rights reserved.</p>
            @if($businessEmail)
            <p>For support, contact: <a href="mailto:{{ $businessEmail }}">{{ $businessEmail }}</a></p>
            @endif
            @if($businessPhone)
            <p>Phone: <a href="tel:{{ $businessPhone }}">{{ $businessPhone }}</a></p>
            @endif
            @if($businessWebsite)
            <p>Website: <a href="{{ $businessWebsite }}" target="_blank">{{ $businessWebsite }}</a></p>
            @endif
        </div>
    </div>
</body>
</html>

