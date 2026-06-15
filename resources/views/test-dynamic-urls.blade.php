@extends('layouts.app')

@section('title', 'Dynamic URL Test')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-link-45deg me-2"></i>
                        Dynamic URL Configuration Test
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Configuration Details</h6>
                            <div id="configDetails" class="bg-light p-3 rounded">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Loading configuration...
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>URL Tests</h6>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" onclick="testApiUrl()">
                                    <i class="bi bi-cloud me-1"></i>
                                    Test API URL
                                </button>
                                <button class="btn btn-success" onclick="testAssetUrl()">
                                    <i class="bi bi-file-earmark me-1"></i>
                                    Test Asset URL
                                </button>
                                <button class="btn btn-info" onclick="testRouteUrl()">
                                    <i class="bi bi-arrow-right me-1"></i>
                                    Test Route URL
                                </button>
                                <button class="btn btn-warning" onclick="testWebUrl()">
                                    <i class="bi bi-globe me-1"></i>
                                    Test Web URL
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6>Test Results</h6>
                            <div id="testResults" class="bg-light p-3 rounded" style="min-height: 200px;">
                                <p class="text-muted">Click the buttons above to test URL generation...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Wait for app config to be available
    const checkConfig = () => {
        if (window.appConfig) {
            displayConfigDetails();
        } else {
            setTimeout(checkConfig, 100);
        }
    };
    checkConfig();
});

function displayConfigDetails() {
    const config = window.appConfig.getDebugInfo();
    const configHtml = `
        <div class="row">
            <div class="col-6">
                <strong>Base URL:</strong><br>
                <code>${config.baseUrl}</code>
            </div>
            <div class="col-6">
                <strong>App Path:</strong><br>
                <code>${config.appPath || 'None'}</code>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-6">
                <strong>API URL:</strong><br>
                <code>${config.apiUrl}</code>
            </div>
            <div class="col-6">
                <strong>Asset URL:</strong><br>
                <code>${config.assetUrl}</code>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-6">
                <strong>Environment:</strong><br>
                <span class="badge bg-${config.environment === 'production' ? 'success' : 'warning'}">${config.environment}</span>
            </div>
            <div class="col-6">
                <strong>Hostname:</strong><br>
                <code>${config.hostname}</code>
            </div>
        </div>
    `;
    document.getElementById('configDetails').innerHTML = configHtml;
}

function testApiUrl() {
    const url = window.appConfig.api('test-endpoint');
    addTestResult('API URL Test', url, 'primary');
}

function testAssetUrl() {
    const url = window.appConfig.asset('css/theme.css');
    addTestResult('Asset URL Test', url, 'success');
}

function testRouteUrl() {
    const url = window.appConfig.route('dashboard');
    addTestResult('Route URL Test', url, 'info');
}

function testWebUrl() {
    const url = window.appConfig.web('login');
    addTestResult('Web URL Test', url, 'warning');
}

function addTestResult(testName, url, type) {
    const resultsDiv = document.getElementById('testResults');
    const timestamp = new Date().toLocaleTimeString();
    const resultHtml = `
        <div class="alert alert-${type} alert-dismissible fade show mb-2">
            <strong>${testName}</strong> (${timestamp})
            <br>
            <code>${url}</code>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    resultsDiv.innerHTML = resultHtml + resultsDiv.innerHTML;
}
</script>
@endpush
