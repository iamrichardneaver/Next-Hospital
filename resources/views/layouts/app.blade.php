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
    
    @php
        // Always fetch fresh branding data (no cache)
        $branding = \App\Helpers\BrandingHelper::getBranding();
    @endphp
    
    <title>@yield('title', 'Dashboard') - {{ $hospitalBranding['name'] ?? $branding->platform_name ?? config('app.name', 'Hospital') }}</title>
    
    @if($branding->favicon_url)
        <link rel="icon" type="image/x-icon" href="{{ $branding->favicon_url }}">
    @endif
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- CKEditor 5 -->
    <script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
    
    <!-- Custom Dark Blue-Black Theme -->
    <link href="{{ asset('assets/css/theme.css') }}" rel="stylesheet">
    
    @stack('styles')
</head>
<body>
    
    <!-- Sidebar -->
    @include('layouts.sidebar')
    
    <!-- Emergency Alert Notifications -->
    @include('components.emergency-alert-notification')
    
    <!-- Main Content -->
    <div class="main-content">
        @if(config('app.demo_mode'))
            <div class="w-100" id="demo-trial-banner" style="display: none;">
                <div class="alert alert-warning d-flex align-items-center justify-content-between mb-0" role="alert"
                     style="border-radius: 0; background: rgba(255, 193, 7, 0.14); border: 0; border-bottom: 1px solid rgba(255, 193, 7, 0.28); color: #f3f6ff; padding: 12px 30px;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div class="small">
                            <span class="fw-semibold">Demo Trial Version</span>
                            <span class="opacity-75">— data resets to the default database every</span>
                            <span class="fw-semibold">{{ config('app.demo_reset_hours') }}</span>
                            <span class="opacity-75">hours.</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-light" id="dismiss-demo-trial-banner" aria-label="Dismiss demo banner">
                        Dismiss
                    </button>
                </div>
            </div>

            @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        try {
                            const bannerKey = 'nh_demo_trial_banner_dismissed';
                            const banner = document.getElementById('demo-trial-banner');
                            const dismissBtn = document.getElementById('dismiss-demo-trial-banner');
                            if (!banner) return;

                            const dismissed = localStorage.getItem(bannerKey) === '1';
                            banner.style.display = dismissed ? 'none' : 'block';

                            if (dismissBtn) {
                                dismissBtn.addEventListener('click', function () {
                                    localStorage.setItem(bannerKey, '1');
                                    banner.style.display = 'none';
                                });
                            }
                        } catch (e) {
                            // If storage is blocked, just show the banner.
                            const banner = document.getElementById('demo-trial-banner');
                            if (banner) banner.style.display = 'block';
                        }
                    });
                </script>
            @endpush
        @endif

        <!-- Header -->
        @include('layouts.header')
        
        <!-- Page Content -->
        <main class="content">
            <!-- Alerts -->
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
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('low_stock_alerts') && count(session('low_stock_alerts')) > 0)
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Low stock after dispensing:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach(session('low_stock_alerts') as $alert)
                        <li>{{ $alert['drug'] }} — {{ $alert['current_stock'] }} remaining (reorder at {{ $alert['reorder_level'] }})</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('portal_password'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-key-fill me-2"></i>
                    <strong>Portal credentials generated.</strong>
                    Copy now — they will not be shown again after you leave this page.
                    <div class="mt-2 small">
                        @if(session('portal_email'))
                            <div><strong>Email:</strong> {{ session('portal_email') }}</div>
                        @endif
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <strong>Password:</strong>
                            <code id="layout-portal-password-masked">••••••••</code>
                            <code id="layout-portal-password-plain" class="d-none">{{ session('portal_password') }}</code>
                            <button type="button" class="btn btn-sm btn-light border" id="layout-toggle-portal-password" title="Show/hide password">
                                <i class="bi bi-eye" id="layout-toggle-portal-password-icon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <!-- Hint Guide Component -->
            @if(\App\Services\HintGuideService::shouldShowHints())
                <x-hint-guide :page="\App\Services\HintGuideService::getCurrentPage()" />
            @endif
            
            <!-- Page Content -->
            @yield('content')
        </main>
        
        <!-- Footer -->
        @include('layouts.footer')
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Axios for AJAX -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <!-- jQuery (optional, for legacy support) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Moment.js for date formatting -->
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    
    <!-- App Configuration (Must be loaded first) -->
    <script src="{{ asset('assets/js/app-config.js') }}"></script>
    
    <!-- Unified Voice Configuration (Must be loaded before audio services) -->
    <script src="{{ asset('assets/js/voice-config.js') }}"></script>
    
    <!-- Workflow Audio Notifications (Web Only) -->
    <script src="{{ asset('assets/js/workflow-notifications.js') }}"></script>
    
    <!-- Real-Time Data Service -->
    <script src="{{ asset('assets/js/realtime-data-service.js') }}"></script>
    
    <!-- Queue Audio Service -->
    <script src="{{ asset('assets/js/queue-audio.js') }}"></script>
    
    <!-- Custom JS -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
    
    <!-- Workflow Navigation System -->
    <script src="{{ asset('assets/js/workflow-navigation.js') }}"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('layout-toggle-portal-password');
        const masked = document.getElementById('layout-portal-password-masked');
        const plain = document.getElementById('layout-portal-password-plain');
        const icon = document.getElementById('layout-toggle-portal-password-icon');
        if (!toggleBtn || !masked || !plain) return;
        toggleBtn.addEventListener('click', function () {
            const showing = !plain.classList.contains('d-none');
            if (showing) {
                plain.classList.add('d-none');
                masked.classList.remove('d-none');
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                plain.classList.remove('d-none');
                masked.classList.add('d-none');
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        });
    });
    </script>

    @stack('scripts')
</body>
</html>
