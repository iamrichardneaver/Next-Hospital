@extends('layouts.app')

@section('title', 'Emergency Alerts')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Emergency Alerts</h1>
            <p class="text-secondary mb-0">Critical & Urgent Patient Alerts</p>
        </div>
        <div class="d-flex gap-2">
            @can('create_emergency_alerts')
            <a href="{{ route('emergency-alerts.create') }}" class="btn btn-danger">
                <i class="bi bi-plus-circle"></i> Create Alert
            </a>
            @endcan
            <button class="btn btn-outline-secondary" onclick="refreshAlerts()" title="Refresh Alerts">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1">{{ $statistics['active'] }}</h4>
                            <p class="mb-0">Active Alerts</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-exclamation-triangle-fill" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1">{{ $statistics['acknowledged'] }}</h4>
                            <p class="mb-0">Acknowledged</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check-circle-fill" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1">{{ $statistics['resolved'] }}</h4>
                            <p class="mb-0">Resolved</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check2-all" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1">{{ $statistics['total'] }}</h4>
                            <p class="mb-0">Total Alerts</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-list-ul" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Status Filter</label>
                    <select class="form-select" id="statusFilter" onchange="filterAlerts()">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="acknowledged">Acknowledged</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priority Filter</label>
                    <select class="form-select" id="priorityFilter" onchange="filterAlerts()">
                        <option value="all">All Priority</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search alerts, patients..." onkeyup="searchAlerts()">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                        <i class="bi bi-x-circle"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Alerts Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Emergency Alerts</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Alert ID</th>
                            <th>Patient</th>
                            <th>Alert Type</th>
                            <th>Message</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="alertsTableBody">
                        @forelse($emergencyAlerts as $alert)
                        <tr class="alert-row" data-priority="{{ $alert->priority }}" data-status="{{ $alert->status }}">
                            <td>
                                <span class="badge bg-primary">#{{ $alert->id }}</span>
                            </td>
                            <td>
                                @if($alert->emergencyVisit && $alert->emergencyVisit->patient)
                                    <strong>{{ $alert->emergencyVisit->patient->first_name }} {{ $alert->emergencyVisit->patient->last_name }}</strong><br>
                                    <small class="text-muted">{{ $alert->emergencyVisit->patient->patient_number }}</small>
                                @else
                                    <span class="text-muted">No patient data</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $alert->alert_type)) }}</span>
                            </td>
                            <td>
                                <div class="alert-message" style="max-width: 300px;">
                                    {{ Str::limit($alert->message, 100) }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $alert->priority === 'critical' ? 'danger' : ($alert->priority === 'high' ? 'warning' : ($alert->priority === 'medium' ? 'info' : 'secondary')) }}">
                                    {{ strtoupper($alert->priority) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $alert->status === 'active' ? 'danger' : ($alert->status === 'acknowledged' ? 'warning' : 'success') }}">
                                    {{ ucfirst($alert->status) }}
                                </span>
                            </td>
                            <td>
                                <small>{{ $alert->created_at->diffForHumans() }}</small><br>
                                <small class="text-muted">{{ $alert->created_at->format('M d, Y H:i') }}</small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @can('view_emergency_alerts')
                                    <a href="{{ route('emergency-alerts.show', $alert) }}" class="btn btn-outline-primary" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @endcan
                                    @if($alert->status === 'active')
                                    @can('edit_emergency_alerts')
                                        <form method="POST" action="{{ route('emergency-alerts.acknowledge', $alert) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-warning" title="Acknowledge" onclick="return confirm('Acknowledge this alert?')">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        </form>
                                    @endcan
                                    @endif
                                    @if($alert->status === 'acknowledged')
                                    @can('edit_emergency_alerts')
                                        <button class="btn btn-outline-success" onclick="resolveAlert({{ $alert->id }})" title="Resolve">
                                            <i class="bi bi-check2-all"></i>
                                        </button>
                                    @endcan
                                    @endif
                                    @can('delete_emergency_alerts')
                                    <form method="POST" action="{{ route('emergency-alerts.destroy', $alert) }}" class="d-inline" onsubmit="return confirm('Delete this alert?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-check-circle" style="font-size: 2rem; color: #28a745;"></i>
                                <p class="text-success mt-2">No emergency alerts found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $emergencyAlerts->links() }}
        </div>
    </div>
</div>

<!-- Resolve Alert Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resolve Emergency Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="resolveForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Resolution Notes</label>
                        <textarea class="form-control" name="resolution_notes" rows="3" placeholder="Enter resolution details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Resolve Alert</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function refreshAlerts() {
    window.location.reload();
}

function filterAlerts() {
    const statusFilter = document.getElementById('statusFilter').value;
    const priorityFilter = document.getElementById('priorityFilter').value;
    const rows = document.querySelectorAll('.alert-row');
    
    rows.forEach(row => {
        const status = row.dataset.status;
        const priority = row.dataset.priority;
        
        let showRow = true;
        
        if (statusFilter !== 'all' && status !== statusFilter) {
            showRow = false;
        }
        
        if (priorityFilter !== 'all' && priority !== priorityFilter) {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

function searchAlerts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('.alert-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

function clearFilters() {
    document.getElementById('statusFilter').value = 'all';
    document.getElementById('priorityFilter').value = 'all';
    document.getElementById('searchInput').value = '';
    
    const rows = document.querySelectorAll('.alert-row');
    rows.forEach(row => {
        row.style.display = '';
    });
}

function resolveAlert(alertId) {
    const form = document.getElementById('resolveForm');
    form.action = `/emergency-alerts/${alertId}/resolve`;
    
    const modal = new bootstrap.Modal(document.getElementById('resolveModal'));
    modal.show();
}

// Auto-refresh every 30 seconds for active alerts
setInterval(function() {
    const activeAlerts = document.querySelectorAll('.alert-row[data-status="active"]');
    if (activeAlerts.length > 0) {
        // Only refresh if there are active alerts
        fetch('/api/emergency-alerts/active')
            .then(response => {
                if (!response.ok) {
                    // If unauthorized, user might not be logged in or doesn't have permission
                    if (response.status === 401 || response.status === 403) {
                        console.log('Emergency alerts: User not authenticated or no permission');
                        return;
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data && data.success && data.data) {
                    // Update active alerts count
                    const activeCount = data.data.filter(alert => alert.status === 'active').length;
                    const activeCard = document.querySelector('.card.bg-danger .card-body h4');
                    if (activeCard) {
                        activeCard.textContent = activeCount;
                    }
                }
            })
            .catch(error => console.log('Error refreshing alerts:', error));
    }
}, 30000);
</script>
@endsection
