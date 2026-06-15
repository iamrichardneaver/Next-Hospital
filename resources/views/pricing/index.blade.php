@extends('layouts.app')

@section('title', 'Service Pricing Management')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Pricing Management</h1>
            <p class="text-muted small mb-0">Manage all service pricing and appointment fees</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pricing.price-list') }}" class="btn btn-outline-primary">
                <i class="bi bi-list-ul"></i> View Price List
            </a>
            @include('components.export-dropdown', [
                'exportRoute' => route('pricing.export'),
                'permission' => 'view_service_pricing',
                'params' => request()->only(['service_type', 'branch_id']),
            ])
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-clipboard-data"></i>
                </div>
                <div class="stat-label">Total Services</div>
                <div class="stat-value">{{ number_format($stats['total_services']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Active Services</div>
                <div class="stat-value">{{ number_format($stats['active_services']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-label">Appointment Fees</div>
                <div class="stat-value">{{ number_format($stats['total_appointment_fees']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-label">Service Categories</div>
                <div class="stat-value">{{ count($stats['service_categories']) }}</div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="pricingTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active text-dark fw-bold" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" 
                    type="button" role="tab" aria-controls="services" aria-selected="true">
                <i class="bi bi-grid-3x3 me-2"></i> Service Pricing
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link text-dark fw-bold" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments" 
                    type="button" role="tab" aria-controls="appointments" aria-selected="false">
                <i class="bi bi-calendar-check me-2"></i> Appointment Fees
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="pricingTabContent">
        <!-- Service Pricing Tab -->
        <div class="tab-pane fade show active" id="services" role="tabpanel" aria-labelledby="services-tab">

            @php
                $overrideCount = $servicePricing->getCollection()->where('pricing_type', 'item_override')->count();
            @endphp
            @if($overrideCount > 0)
            <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
                <i class="bi bi-arrow-repeat fs-5"></i>
                <div>
                    <strong>Legacy item overrides ({{ $overrideCount }} on this page)</strong> — IDs like <code>lab_test_{id}</code> or <code>drug_{id}</code> still replace item prices.
                    To adopt the additive model, add a <strong>Module Fee</strong> for the module and keep the override until you migrate.
                    Run <code>php artisan pricing:migrate-overrides --dry-run</code> for conversion guidance.
                </div>
            </div>
            @endif
            
            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 text-dark"><i class="bi bi-funnel me-2"></i>Filter Services</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('pricing.index') }}" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" 
                                       name="search" 
                                       class="form-control" 
                                       placeholder="Search services..." 
                                       value="{{ request('search') }}" 
                                       id="searchInput">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Service Type</label>
                                <select name="service_type" class="form-select">
                                    <option value="">All Types</option>
                                    @foreach($serviceTypes as $type)
                                        <option value="{{ $type }}" {{ request('service_type') == $type ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $type)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Branch</label>
                                <select name="branch_id" class="form-select">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Apply Filters
                            </button>
                            <a href="{{ route('pricing.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-end mb-3 gap-2">
                @can('create_service_pricing')
                <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
                    <i class="bi bi-upload"></i> Bulk Import
                </button>
                <a href="{{ route('pricing.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Service
                </a>
                @endcan
            </div>

            <!-- Service Pricing Table -->
    <div class="card">
        <div class="card-header" style="background-color: #1e3a5f;">
            <h5 class="mb-0 text-white">
                <i class="bi bi-grid-3x3 me-2"></i>Service Pricing
                <span class="badge bg-light text-dark ms-2">{{ $servicePricing->total() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Service ID</th>
                            <th>Service Name</th>
                            <th>Type</th>
                            <th>Pricing Type</th>
                            <th>Applies On</th>
                            <th>Modules</th>
                            <th>Branch</th>
                            <th>Base Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($servicePricing as $service)
                        <tr>
                            <td><code class="text-info">{{ $service->service_id }}</code></td>
                            <td>
                                <strong>{{ $service->service_name }}</strong>
                                @if($service->description)
                                <br><small class="text-muted">{{ Str::limit($service->description, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ ucwords(str_replace('_', ' ', $service->service_type)) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $ptype = $service->pricing_type ?? 'standalone';
                                    $ptypeLabel = match($ptype) {
                                        'module_fee' => 'Module Fee',
                                        'item_override' => 'Item Override',
                                        default => 'Standalone',
                                    };
                                    $ptypeClass = match($ptype) {
                                        'module_fee' => 'bg-info',
                                        'item_override' => 'bg-warning text-dark',
                                        default => 'bg-light text-dark',
                                    };
                                @endphp
                                <span class="badge {{ $ptypeClass }}">{{ $ptypeLabel }}</span>
                                @if($ptype === 'item_override')
                                    <br><small class="text-muted">Legacy override — replaces item price</small>
                                @endif
                            </td>
                            <td>
                                @if($service->applies_on)
                                    <span class="badge bg-outline-primary border text-dark">{{ str_replace('_', ' ', ucfirst($service->applies_on)) }}</span>
                                @elseif(($service->pricing_type ?? '') === 'module_fee')
                                    <span class="text-muted small">Default</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($service->module_codes))
                                    @foreach($service->module_codes as $code)
                                        <span class="badge bg-outline-secondary border text-dark">{{ $code }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $service->branch->name ?? 'N/A' }}</td>
                            <td><strong class="text-success">{{ $service->currency }} {{ number_format($service->base_price, 2) }}</strong></td>
                            <td>
                                @if($service->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="position-static">
                                <div class="dropdown position-static">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        @can('view_service_pricing')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('pricing.show', $service->id) }}">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a>
                                        </li>
                                        @endcan
                                        @can('edit_service_pricing')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('pricing.edit', $service->id) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit Service
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('pricing.toggle-active', $service->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item {{ $service->is_active ? 'text-warning' : 'text-success' }}">
                                                    <i class="bi bi-{{ $service->is_active ? 'pause' : 'play' }}-circle me-2"></i>
                                                    {{ $service->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                        @can('delete_service_pricing')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('pricing.destroy', $service->id) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this service? This action cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i>Delete Service
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 mb-3 d-block"></i>
                                No service pricing found. <a href="{{ route('pricing.create') }}">Add one now</a>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($servicePricing->hasPages())
        <div class="card-footer bg-dark border-top border-secondary">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{ $servicePricing->firstItem() }} to {{ $servicePricing->lastItem() }} 
                    of {{ $servicePricing->total() }} entries
                </div>
                <div>
                    {{ $servicePricing->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
        
        </div>
        <!-- End Service Pricing Tab -->
        
        <!-- Appointment Fees Tab -->
        <div class="tab-pane fade" id="appointments" role="tabpanel" aria-labelledby="appointments-tab">
            
            <!-- Action Buttons -->
            <div class="d-flex justify-content-end mb-3 gap-2">
                @can('create_appointment_fee')
                <a href="{{ route('appointment-fees.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Appointment Fee
                </a>
                @endcan
            </div>
            
            <!-- Appointment Fees Table -->
            <div class="card">
                <div class="card-header" style="background-color: #1e3a5f;">
                    <h5 class="mb-0 text-white">
                        <i class="bi bi-calendar-check me-2"></i>Appointment Fees
                        <span class="badge bg-light text-dark ms-2">{{ $appointmentFees->total() }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Category</th>
                                    <th>Doctor</th>
                                    <th>Type</th>
                                    <th>Branch</th>
                                    <th>Base Fee</th>
                                    <th>Total Fee</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($appointmentFees as $fee)
                                <tr>
                                    <td><strong>{{ $fee->fee_category }}</strong></td>
                                    <td>
                                        @if($fee->doctor)
                                            <span class="badge bg-info">{{ $fee->doctor->name }}</span>
                                        @else
                                            <span class="badge bg-secondary">General</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($fee->appointment_type === 'in-person')
                                            <span class="badge bg-primary">In-Person</span>
                                        @else
                                            <span class="badge bg-success">Teleconsultation</span>
                                        @endif
                                    </td>
                                    <td>{{ $fee->branch->name ?? 'N/A' }}</td>
                                    <td><strong class="text-success">{{ $fee->currency }} {{ number_format($fee->base_fee, 2) }}</strong></td>
                                    <td><strong class="text-info">{{ $fee->currency }} {{ number_format($fee->calculateTotalFee(), 2) }}</strong></td>
                                    <td>
                                        @if($fee->isEffective())
                                            <span class="badge bg-success">Active</span>
                                        @elseif($fee->is_active)
                                            <span class="badge bg-warning">Scheduled</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="position-static">
                                        <div class="dropdown position-static">
                                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                                @can('view_appointment_fee')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('appointment-fees.show', $fee->id) }}">
                                                        <i class="bi bi-eye me-2"></i>View Details
                                                    </a>
                                                </li>
                                                @endcan
                                                @can('edit_appointment_fee')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('appointment-fees.edit', $fee->id) }}">
                                                        <i class="bi bi-pencil me-2"></i>Edit Fee
                                                    </a>
                                                </li>
                                                @endcan
                                                @can('delete_appointment_fee')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('appointment-fees.destroy', $fee->id) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to delete this fee?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="bi bi-trash me-2"></i>Delete Fee
                                                        </button>
                                                    </form>
                                                </li>
                                                @endcan
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-1 mb-3 d-block"></i>
                                        No appointment fees found. <a href="{{ route('appointment-fees.create') }}">Add one now</a>.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($appointmentFees->hasPages())
                <div class="card-footer bg-dark border-top border-secondary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $appointmentFees->firstItem() }} to {{ $appointmentFees->lastItem() }} 
                            of {{ $appointmentFees->total() }} entries
                        </div>
                        <div>
                            {{ $appointmentFees->links() }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
            
        </div>
        <!-- End Appointment Fees Tab -->
        
    </div>
    <!-- End Tab Content -->
    
</div>

<!-- Bulk Import Modal -->
<div class="modal fade" id="bulkImportModal" tabindex="-1" aria-labelledby="bulkImportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1e3a5f;">
                <h5 class="modal-title text-white" id="bulkImportModalLabel">
                    <i class="bi bi-upload me-2"></i>Bulk Import Services
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('pricing.bulk-import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                        <select class="form-select" name="branch_id" id="branch_id" required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="file" class="form-label">CSV File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="file" id="file" accept=".csv,.txt" required>
                        <div class="form-text">
                            CSV format with columns: service_id, service_name, service_type, base_price, currency, description, is_active, requires_approval
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <h6 class="alert-heading">CSV Format Example:</h6>
                        <pre class="mb-0 text-small">service_id,service_name,service_type,base_price,currency,description,is_active,requires_approval
CONS_001,General Consultation,consultation,50.00,GHS,Regular consultation with doctor,1,0
LAB_CBC,Complete Blood Count,lab_test,25.00,GHS,Blood test for CBC,1,0</pre>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Import Services
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Live search
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filterForm').submit();
        }, 500);
    });
    
    // Auto-submit on filter change
    document.querySelectorAll('select[name="service_type"], select[name="branch_id"], select[name="is_active"]')
        .forEach(select => {
            select.addEventListener('change', () => {
                document.getElementById('filterForm').submit();
            });
        });
    
    // Initialize Bootstrap dropdowns
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all dropdowns
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
        
        // Handle toggle active form submissions
        document.querySelectorAll('form[action*="toggle-active"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const action = this.action;
                
                fetch(action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => {
                    if (response.ok) {
                        location.reload();
                    } else {
                        alert('Error updating service status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating service status');
                });
            });
        });
    });
</script>
@endpush
@endsection

