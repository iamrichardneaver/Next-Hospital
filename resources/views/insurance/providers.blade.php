@extends('layouts.app')

@section('title', 'Insurance Providers')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Insurance Providers</h1>
            <p class="text-secondary mb-0">Manage insurance provider information and settings</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProviderModal">
            <i class="bi bi-plus-circle"></i> Add Provider
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3>{{ $statistics['total_providers'] }}</h3>
                    <small>Total Providers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3>{{ $statistics['active_providers'] }}</h3>
                    <small>Active Providers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3>{{ $statistics['total_policies'] }}</h3>
                    <small>Total Policies</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3>{{ $statistics['total_claims'] }}</h3>
                    <small>Total Claims</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Providers Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Insurance Providers</h5>
                <div class="d-flex gap-2">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search providers..." style="width: 250px;">
                    <button class="btn btn-outline-primary btn-sm" onclick="filterProviders()">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="providersTable">
                    <thead class="table-light">
                        <tr>
                            <th>Provider Name</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Contact</th>
                            <th>Default Coverage</th>
                            <th>Features</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($providers as $provider)
                        <tr>
                            <td>
                                <div>
                                    <strong>{{ $provider->name }}</strong>
                                    @if($provider->website)
                                        <br><small class="text-muted">{{ $provider->website }}</small>
                                    @endif
                                </div>
                            </td>
                            <td><span class="badge bg-secondary">{{ $provider->code }}</span></td>
                            <td>
                                <span class="badge bg-{{ $provider->type === 'government' ? 'success' : ($provider->type === 'private' ? 'primary' : 'info') }}">
                                    {{ ucfirst($provider->type) }}
                                </span>
                            </td>
                            <td>
                                @if($provider->contact_person)
                                    <div>
                                        <strong>{{ $provider->contact_person }}</strong>
                                        @if($provider->phone)
                                            <br><small class="text-muted">{{ $provider->phone }}</small>
                                        @endif
                                        @if($provider->email)
                                            <br><small class="text-muted">{{ $provider->email }}</small>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">No contact info</span>
                                @endif
                            </td>
                            <td>
                                @if($provider->default_coverage_percentage)
                                    <div>
                                        <strong>{{ $provider->default_coverage_percentage }}%</strong> coverage
                                        @if($provider->default_co_pay_percentage)
                                            <br><small class="text-muted">{{ $provider->default_co_pay_percentage }}% co-pay</small>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @if($provider->supports_electronic_claims)
                                        <span class="badge bg-success badge-sm">Electronic Claims</span>
                                    @endif
                                    @if($provider->supports_real_time_verification)
                                        <span class="badge bg-info badge-sm">Real-time Verification</span>
                                    @endif
                                    @if($provider->requires_pre_authorization)
                                        <span class="badge bg-warning badge-sm">Pre-auth Required</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $provider->is_active ? 'success' : 'secondary' }}">
                                    {{ $provider->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="position-static">
                                <div class="dropdown position-static">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        @can('view_insurance_providers')
                                        <li><a class="dropdown-item" href="#" onclick="viewProvider({{ $provider->id }})">
                                            <i class="bi bi-eye"></i> View Details
                                        </a></li>
                                        @endcan
                                        @can('edit_insurance_providers')
                                        <li><a class="dropdown-item" href="{{ route('insurance.providers.edit', $provider) }}">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="toggleProviderStatus({{ $provider->id }}, {{ $provider->is_active ? 'false' : 'true' }})">
                                            <i class="bi bi-{{ $provider->is_active ? 'pause' : 'play' }}"></i> 
                                            {{ $provider->is_active ? 'Deactivate' : 'Activate' }}
                                        </a></li>
                                        @endcan
                                        @can('delete_insurance_providers')
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteProvider({{ $provider->id }})">
                                            <i class="bi bi-trash"></i> Delete
                                        </a></li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $providers->links() }}
        </div>
    </div>
</div>

<!-- Add Provider Modal -->
<div class="modal fade" id="addProviderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Insurance Provider</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('insurance.providers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Provider Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Provider Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="code" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Provider Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="private">Private</option>
                                    <option value="public">Public</option>
                                    <option value="corporate">Corporate</option>
                                    <option value="government">Government</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="contact_person">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Website</label>
                        <input type="url" class="form-control" name="website">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Default Coverage %</label>
                                <input type="number" class="form-control" name="default_coverage_percentage" min="0" max="100" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Default Co-pay %</label>
                                <input type="number" class="form-control" name="default_co_pay_percentage" min="0" max="100" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="requires_pre_authorization" value="1">
                                <label class="form-check-label">Requires Pre-authorization</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="supports_electronic_claims" value="1">
                                <label class="form-check-label">Electronic Claims</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="supports_real_time_verification" value="1">
                                <label class="form-check-label">Real-time Verification</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Provider</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function filterProviders() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const table = document.getElementById('providersTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    }
}

document.getElementById('searchInput').addEventListener('keyup', filterProviders);

function viewProvider(id) {
    window.location.href = '{{ route('insurance.providers') }}?highlight=' + id;
}

function editProvider(id) {
    window.location.href = '{{ route('insurance.providers') }}?edit=' + id;
}

function toggleProviderStatus(id, status) {
    if (confirm('Are you sure you want to change the provider status?')) {
        window.location.href = '{{ route('insurance.providers') }}?toggle=' + id;
    }
}

function deleteProvider(id) {
    if (confirm('Are you sure you want to delete this provider? This action cannot be undone.')) {
        window.location.href = '{{ route('insurance.index') }}';
    }
}
</script>
@endpush
