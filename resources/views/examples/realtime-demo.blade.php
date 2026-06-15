@extends('layouts.app')

@section('title', 'Real-Time Data Demo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Real-Time Data Demo
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        This page demonstrates the real-time data refresh system. 
                        Data will automatically update without page reload.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Real-Time Counters -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="card-title">Total Patients</h6>
                    @realtimeCounter(['module' => 'patients', 'class' => 'fs-3 text-primary'])
                        0
                    @endrealtimeCounter
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="card-title">Queue Items</h6>
                    @realtimeCounter(['module' => 'queue', 'class' => 'fs-3 text-warning'])
                        0
                    @endrealtimeCounter
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="card-title">Lab Results</h6>
                    @realtimeCounter(['module' => 'lab_results', 'class' => 'fs-3 text-success'])
                        0
                    @endrealtimeCounter
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="card-title">Emergency Alerts</h6>
                    @realtimeCounter(['module' => 'emergency_alerts', 'class' => 'fs-3 text-danger'])
                        0
                    @endrealtimeCounter
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Real-Time Table -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Patients (Real-Time)</h5>
                </div>
                <div class="card-body">
                    @realtimeTable([
                        'module' => 'patients',
                        'columns' => [
                            ['key' => 'patient_id', 'label' => 'Patient ID'],
                            ['key' => 'first_name', 'label' => 'First Name'],
                            ['key' => 'last_name', 'label' => 'Last Name'],
                            ['key' => 'gender', 'label' => 'Gender'],
                            ['key' => 'created_at', 'label' => 'Created', 'formatter' => 'datetime']
                        ],
                        'filters' => ['status' => 'active']
                    ])
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                <i class="bi bi-hourglass-split me-2"></i>
                                Loading data...
                            </td>
                        </tr>
                    @endrealtimeTable
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Real-Time Queue -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">OPD Queue (Real-Time)</h5>
                </div>
                <div class="card-body">
                    @realtimeTable([
                        'module' => 'queue',
                        'columns' => [
                            ['key' => 'patient_name', 'label' => 'Patient'],
                            ['key' => 'queue_type', 'label' => 'Type'],
                            ['key' => 'status', 'label' => 'Status', 'formatter' => 'badge'],
                            ['key' => 'created_at', 'label' => 'Time', 'formatter' => 'datetime']
                        ],
                        'filters' => ['queue_type' => 'opd']
                    ])
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                <i class="bi bi-hourglass-split me-2"></i>
                                Loading queue data...
                            </td>
                        </tr>
                    @endrealtimeTable
                </div>
            </div>
        </div>

        <!-- Real-Time Lab Results -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Lab Results (Real-Time)</h5>
                </div>
                <div class="card-body">
                    @realtimeTable([
                        'module' => 'lab_results',
                        'columns' => [
                            ['key' => 'patient_name', 'label' => 'Patient'],
                            ['key' => 'test_name', 'label' => 'Test'],
                            ['key' => 'status', 'label' => 'Status', 'formatter' => 'badge'],
                            ['key' => 'created_at', 'label' => 'Date', 'formatter' => 'date']
                        ],
                        'filters' => ['status' => 'completed']
                    ])
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                <i class="bi bi-hourglass-split me-2"></i>
                                Loading lab results...
                            </td>
                        </tr>
                    @endrealtimeTable
                </div>
            </div>
        </div>
    </div>

    <!-- Real-Time Status Indicators -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Real-Time Status</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div id="connection-status" class="badge bg-success">
                                        <i class="bi bi-wifi me-1"></i>
                                        Connected
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">WebSocket Connection</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div id="polling-status" class="badge bg-info">
                                        <i class="bi bi-arrow-clockwise me-1"></i>
                                        Active
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Data Polling</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div id="last-update" class="badge bg-secondary">
                                        <i class="bi bi-clock me-1"></i>
                                        Never
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Last Update</small>
                                </div>
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
document.addEventListener('DOMContentLoaded', function() {
    // Update connection status
    function updateConnectionStatus() {
        const statusElement = document.getElementById('connection-status');
        const pollingElement = document.getElementById('polling-status');
        
        if (window.realTimeDataService) {
            // WebSocket status
            if (window.realTimeDataService.wsConnection) {
                const isConnected = window.realTimeDataService.wsConnection.readyState === WebSocket.OPEN;
                statusElement.className = isConnected ? 'badge bg-success' : 'badge bg-danger';
                statusElement.innerHTML = isConnected ? 
                    '<i class="bi bi-wifi me-1"></i>Connected' : 
                    '<i class="bi bi-wifi-off me-1"></i>Disconnected';
            }
            
            // Polling status
            const isPolling = window.realTimeDataService.pollingIntervals.size > 0;
            pollingElement.className = isPolling ? 'badge bg-info' : 'badge bg-warning';
            pollingElement.innerHTML = isPolling ? 
                '<i class="bi bi-arrow-clockwise me-1"></i>Active' : 
                '<i class="bi bi-pause-circle me-1"></i>Paused';
        }
    }

    // Update last update time
    function updateLastUpdateTime() {
        const lastUpdateElement = document.getElementById('last-update');
        lastUpdateElement.innerHTML = '<i class="bi bi-clock me-1"></i>' + new Date().toLocaleTimeString();
    }

    // Listen for real-time events
    window.addEventListener('realtime:data-changed', function(event) {
        updateLastUpdateTime();
        console.log('Real-time data changed:', event.detail);
    });

    window.addEventListener('realtime:module-updated', function(event) {
        updateLastUpdateTime();
        console.log('Module updated:', event.detail);
    });

    // Update status every 5 seconds
    setInterval(updateConnectionStatus, 5000);
    updateConnectionStatus();
});
</script>
@endpush

@push('styles')
<style>
.data-updated {
    animation: dataUpdatePulse 1s ease-in-out;
}

@keyframes dataUpdatePulse {
    0% { background-color: transparent; }
    50% { background-color: rgba(13, 110, 253, 0.1); }
    100% { background-color: transparent; }
}

.realtime-table-container .table tbody tr {
    transition: all 0.3s ease;
}

.realtime-table-container .table tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}

.counter-updated {
    animation: counterPulse 0.5s ease-in-out;
}

@keyframes counterPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
</style>
@endpush
@endsection
