@extends('layouts.app')

@section('title', 'Real-Time System Test')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Real-Time System Test
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        This page tests the real-time data system. Check the browser console for logs.
                    </p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Connection Status</h6>
                            <div id="connection-status" class="badge bg-secondary">
                                <i class="bi bi-wifi-off me-1"></i>
                                Disconnected
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Polling Status</h6>
                            <div id="polling-status" class="badge bg-secondary">
                                <i class="bi bi-pause-circle me-1"></i>
                                Inactive
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Test Buttons</h6>
                            <button class="btn btn-primary me-2" onclick="testModuleData()">Test Module Data</button>
                            <button class="btn btn-success me-2" onclick="testActiveModules()">Test Active Modules</button>
                            <button class="btn btn-info me-2" onclick="testPollingInterval()">Test Polling Interval</button>
                            <button class="btn btn-warning" onclick="refreshAllModules()">Refresh All Modules</button>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Console Output</h6>
                            <div id="console-output" class="bg-light text-dark p-3 rounded" style="height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                                <div>Real-Time System Test Console</div>
                                <div>Check browser console for detailed logs</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Override console.log to show in the page
const originalLog = console.log;
const consoleOutput = document.getElementById('console-output');

console.log = function(...args) {
    originalLog.apply(console, args);
    const message = args.join(' ');
    const timestamp = new Date().toLocaleTimeString();
    consoleOutput.innerHTML += `<div>[${timestamp}] ${message}</div>`;
    consoleOutput.scrollTop = consoleOutput.scrollHeight;
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('Real-Time System Test Page Loaded');
    
    // Check if real-time service is available
    if (window.realTimeDataService) {
        console.log('Real-Time Data Service is available');
        updateStatus();
    } else {
        console.log('Real-Time Data Service is not available');
    }
    
    // Update status every 2 seconds
    setInterval(updateStatus, 2000);
});

function updateStatus() {
    const connectionStatus = document.getElementById('connection-status');
    const pollingStatus = document.getElementById('polling-status');
    
    if (window.realTimeDataService) {
        // WebSocket status
        if (window.realTimeDataService.wsConnection) {
            const isConnected = window.realTimeDataService.wsConnection.readyState === WebSocket.OPEN;
            connectionStatus.className = isConnected ? 'badge bg-success' : 'badge bg-danger';
            connectionStatus.innerHTML = isConnected ? 
                '<i class="bi bi-wifi me-1"></i>Connected' : 
                '<i class="bi bi-wifi-off me-1"></i>Disconnected';
        } else {
            connectionStatus.className = 'badge bg-info';
            connectionStatus.innerHTML = '<i class="bi bi-wifi me-1"></i>Polling Only';
        }
        
        // Polling status
        const isPolling = window.realTimeDataService.pollingIntervals.size > 0;
        pollingStatus.className = isPolling ? 'badge bg-success' : 'badge bg-warning';
        pollingStatus.innerHTML = isPolling ? 
            '<i class="bi bi-arrow-clockwise me-1"></i>Active' : 
            '<i class="bi bi-pause-circle me-1"></i>Inactive';
    }
}

function testModuleData() {
    console.log('Testing module data...');
    
    if (window.realTimeDataService) {
        // Test with patients module
        window.realTimeDataService.pollModuleData('patients')
            .then(() => console.log('Module data test completed'))
            .catch(error => console.log('Module data test failed:', error));
    } else {
        console.log('Real-Time Data Service not available');
    }
}

function testActiveModules() {
    console.log('Testing active modules...');
    
    if (window.realTimeDataService) {
        window.realTimeDataService.getActiveModules()
            .then(modules => {
                console.log('Active modules:', modules);
            })
            .catch(error => console.log('Active modules test failed:', error));
    } else {
        console.log('Real-Time Data Service not available');
    }
}

function testPollingInterval() {
    console.log('Testing polling interval...');
    
    if (window.realTimeDataService) {
        window.realTimeDataService.getPollingInterval('patients')
            .then(interval => {
                console.log('Polling interval for patients:', interval + 'ms');
            })
            .catch(error => console.log('Polling interval test failed:', error));
    } else {
        console.log('Real-Time Data Service not available');
    }
}

function refreshAllModules() {
    console.log('Refreshing all modules...');
    
    if (window.realTimeDataService) {
        window.realTimeDataService.refreshAllModules()
            .then(() => console.log('All modules refreshed'))
            .catch(error => console.log('Refresh failed:', error));
    } else {
        console.log('Real-Time Data Service not available');
    }
}

// Listen for real-time events
window.addEventListener('realtime:data-changed', function(event) {
    console.log('Real-time data changed:', event.detail);
});

window.addEventListener('realtime:module-updated', function(event) {
    console.log('Module updated:', event.detail);
});
</script>
@endpush
@endsection
