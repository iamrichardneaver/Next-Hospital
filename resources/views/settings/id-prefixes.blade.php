@extends('layouts.app')

@section('title', 'ID Prefix Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">ID Prefix Settings</h1>
            <p class="text-secondary mb-0">Manage dynamic ID generation patterns for all system entities</p>
        </div>
        <div>
            @canany(['manage_system_settings', 'manage_settings'])
            <a href="{{ route('id-prefixes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Pattern
            </a>
            @endcanany
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Information Card -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-info-circle"></i> About ID Prefix System</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-dark">How it works:</h6>
                    <ul class="mb-0">
                        <li>All IDs are generated dynamically using configurable patterns</li>
                        <li>Admins can customize prefixes, separators, and date formats</li>
                        <li>Patterns are locked once records exist to maintain data integrity</li>
                        <li>Sequences auto-increment for unique identification</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-dark">Available placeholders:</h6>
                    <ul class="mb-0">
                        <li><code>{company_prefix}</code> - Company identifier</li>
                        <li><code>{module_prefix}</code> - Module identifier</li>
                        <li><code>{year}</code> - Current year (YYYY)</li>
                        <li><code>{month}</code> - Current month (MM)</li>
                        <li><code>{day}</code> - Current day (DD)</li>
                        <li><code>{sequence}</code> - Auto-incrementing number</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ID Prefix Settings Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark">ID Prefix Settings</h5>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="showAll()">All</button>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="showActive()">Active</button>
                <button type="button" class="btn btn-outline-warning btn-sm" onclick="showInactive()">Inactive</button>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="showLocked()">Locked</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="prefixTable">
                    <thead class="table-light">
                        <tr>
                            <th>Entity Type</th>
                            <th>Pattern</th>
                            <th>Example ID</th>
                            <th>Status</th>
                            <th>Sequence</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settings as $setting)
                        <tr data-status="{{ $setting->is_active ? 'active' : 'inactive' }}" data-locked="{{ $setting->is_locked ? 'locked' : 'unlocked' }}">
                            <td>
                                <strong class="text-primary">{{ $availableTypes[$setting->entity_type] ?? $setting->entity_type }}</strong>
                                <br><small class="text-muted">{{ $setting->entity_type }}</small>
                            </td>
                            <td>
                                <code class="text-info">{{ $setting->pattern }}</code>
                                @if($setting->description)
                                    <br><small class="text-muted">{{ $setting->description }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark" id="example-{{ $setting->entity_type }}">
                                    <i class="bi bi-hourglass-split"></i> Loading...
                                </span>
                            </td>
                            <td>
                                @if($setting->is_locked)
                                    <span class="badge bg-danger">
                                        <i class="bi bi-lock-fill"></i> Locked
                                    </span>
                                @elseif($setting->is_active)
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle-fill"></i> Active
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-pause-circle-fill"></i> Inactive
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted">{{ number_format($setting->current_sequence) }}</span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                            onclick="testId('{{ $setting->entity_type }}')" 
                                            title="Test ID Generation">
                                        <i class="bi bi-play-circle"></i>
                                    </button>
                                    
                                    @canany(['manage_system_settings', 'manage_settings'])
                                    @if(!$setting->is_locked)
                                        <a href="{{ route('id-prefixes.edit', $setting->entity_type) }}" 
                                           class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                    @endcanany
                                    
                                    @if(!$setting->is_locked && $setting->current_sequence > 0)
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="resetSequence('{{ $setting->entity_type }}')" 
                                                title="Reset Sequence">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    @endif
                                    
                                    @if(!$setting->is_locked)
                                        <button type="button" class="btn btn-sm btn-outline-{{ $setting->is_active ? 'warning' : 'success' }}" 
                                                onclick="toggleActive('{{ $setting->entity_type }}')" 
                                                title="{{ $setting->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="bi bi-{{ $setting->is_active ? 'pause' : 'play' }}-circle"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-gear text-secondary" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="text-secondary mt-2 mb-0">No ID prefix settings found</p>
                                <a href="{{ route('id-prefixes.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus"></i> Create First Setting
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Test ID Modal -->
<div class="modal fade" id="testIdModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test ID Generation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="testResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load example IDs for all settings
    @foreach($settings as $setting)
        loadExampleId('{{ $setting->entity_type }}');
    @endforeach
});

function loadExampleId(entityType) {
    fetch(`{{ url('id-prefixes') }}/${entityType}/test`)
        .then(response => response.json())
        .then(data => {
            const element = document.getElementById(`example-${entityType}`);
            if (data.success) {
                element.innerHTML = `<i class="bi bi-tag"></i> ${data.data.test_id}`;
                element.className = 'badge bg-light text-dark';
            } else {
                element.innerHTML = `<i class="bi bi-exclamation-triangle"></i> Error`;
                element.className = 'badge bg-danger';
            }
        })
        .catch(error => {
            const element = document.getElementById(`example-${entityType}`);
            element.innerHTML = `<i class="bi bi-exclamation-triangle"></i> Error`;
            element.className = 'badge bg-danger';
        });
}

function testId(entityType) {
    const modal = new bootstrap.Modal(document.getElementById('testIdModal'));
    const resultDiv = document.getElementById('testResult');
    
    resultDiv.innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split"></i> Testing...</div>';
    modal.show();
    
    fetch(`{{ url('id-prefixes') }}/${entityType}/test`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h6><i class="bi bi-check-circle"></i> Test Successful</h6>
                        <p><strong>Entity Type:</strong> ${data.data.entity_type}</p>
                        <p><strong>Pattern:</strong> <code>${data.data.pattern}</code></p>
                        <p><strong>Generated ID:</strong> <span class="badge bg-primary">${data.data.test_id}</span></p>
                        <p><strong>Status:</strong> ${data.data.is_locked ? '<span class="badge bg-danger">Locked</span>' : '<span class="badge bg-success">Unlocked</span>'}</p>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-exclamation-triangle"></i> Test Failed</h6>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h6><i class="bi bi-exclamation-triangle"></i> Error</h6>
                    <p>Failed to test ID generation</p>
                </div>
            `;
        });
}

function resetSequence(entityType) {
    if (confirm(`Are you sure you want to reset the sequence for ${entityType}? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ url('id-prefixes') }}/${entityType}/reset-sequence`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function toggleActive(entityType) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `{{ url('id-prefixes') }}/${entityType}/toggle-active`;
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);
    
    document.body.appendChild(form);
    form.submit();
}

// Filter functions
function showAll() {
    document.querySelectorAll('#prefixTable tbody tr').forEach(row => {
        row.style.display = '';
    });
}

function showActive() {
    document.querySelectorAll('#prefixTable tbody tr').forEach(row => {
        row.style.display = row.dataset.status === 'active' ? '' : 'none';
    });
}

function showInactive() {
    document.querySelectorAll('#prefixTable tbody tr').forEach(row => {
        row.style.display = row.dataset.status === 'inactive' ? '' : 'none';
    });
}

function showLocked() {
    document.querySelectorAll('#prefixTable tbody tr').forEach(row => {
        row.style.display = row.dataset.locked === 'locked' ? '' : 'none';
    });
}
</script>
@endsection
