@extends('layouts.app')

@section('title', 'Service Pricing Details')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Service Pricing Details</h1>
            <p class="text-muted small mb-0">View comprehensive information about this service</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pricing.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            @can('edit_service_pricing')
            <a href="{{ route('pricing.edit', $pricing->id) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
        </div>
    </div>

    <div class="row">
        <!-- Service Information -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark border-bottom border-secondary">
                    <h5 class="mb-0 text-white">Service Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Service ID</label>
                            <p class="form-control-plaintext text-dark"><code>{{ $pricing->service_id }}</code></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Service Name</label>
                            <p class="form-control-plaintext text-dark fw-bold">{{ $pricing->service_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Service Type</label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-info">
                                    {{ ucwords(str_replace('_', ' ', $pricing->service_type)) }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Pricing Type</label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-primary">{{ str_replace('_', ' ', ucfirst($pricing->pricing_type ?? 'standalone')) }}</span>
                                @if($pricing->is_additive)
                                    <span class="badge bg-success ms-1">Additive</span>
                                @endif
                            </p>
                        </div>
                        @if(!empty($pricing->module_codes))
                        <div class="col-12">
                            <label class="form-label text-muted">Assigned Modules</label>
                            <p class="form-control-plaintext">
                                @foreach($pricing->module_codes as $code)
                                    <span class="badge bg-secondary">{{ $code }}</span>
                                @endforeach
                            </p>
                        </div>
                        @endif
                        @if($pricing->applies_on)
                        <div class="col-md-6">
                            <label class="form-label text-muted">Applies On</label>
                            <p class="form-control-plaintext text-dark">{{ str_replace('_', ' ', ucfirst($pricing->applies_on)) }}</p>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <label class="form-label text-muted">Branch</label>
                            <p class="form-control-plaintext text-dark">{{ $pricing->branch->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Base Price</label>
                            <p class="form-control-plaintext fw-bold text-success fs-5">
                                {{ $pricing->currency }} {{ number_format($pricing->base_price, 2) }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Currency</label>
                            <p class="form-control-plaintext text-dark">{{ $pricing->currency }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Status</label>
                            <p class="form-control-plaintext">
                                @if($pricing->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Requires Approval</label>
                            <p class="form-control-plaintext">
                                @if($pricing->requires_approval)
                                    <span class="badge bg-warning">Yes</span>
                                @else
                                    <span class="badge bg-info">No</span>
                                @endif
                            </p>
                        </div>
                        @if($pricing->description)
                        <div class="col-12">
                            <label class="form-label text-muted">Description</label>
                            <p class="form-control-plaintext text-dark">{{ $pricing->description }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Pricing Tiers -->
            @if($pricing->pricing_tiers && count($pricing->pricing_tiers) > 0)
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-dark border-bottom border-secondary">
                    <h5 class="mb-0 text-white">Pricing Tiers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tier Name</th>
                                    <th>Price</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pricing->pricing_tiers as $tier => $tierData)
                                <tr>
                                    <td><span class="badge bg-info">{{ ucfirst($tier) }}</span></td>
                                    <td class="fw-bold text-success">{{ $pricing->currency }} {{ number_format($tierData['price'] ?? $tierData, 2) }}</td>
                                    <td>{{ $tierData['description'] ?? 'Standard pricing tier' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Usage Statistics -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark border-bottom border-secondary">
                    <h5 class="mb-0 text-white">Usage Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="stat-card primary">
                                <div class="stat-icon">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                                <div class="stat-label">Times Used</div>
                                <div class="stat-value">{{ number_format($usageStats['times_used'] ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card success">
                                <div class="stat-icon">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                                <div class="stat-label">Total Revenue</div>
                                <div class="stat-value">{{ $pricing->currency }} {{ number_format($usageStats['total_revenue'] ?? 0, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    @if(isset($usageStats['last_used_at']) && $usageStats['last_used_at'])
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            Last used: {{ \Carbon\Carbon::parse($usageStats['last_used_at'])->diffForHumans() }}
                        </small>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark border-bottom border-secondary">
                    <h5 class="mb-0 text-white">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @can('edit_service_pricing')
                        <form action="{{ route('pricing.toggle-active', $pricing->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-{{ $pricing->is_active ? 'warning' : 'success' }} w-100">
                                <i class="bi bi-{{ $pricing->is_active ? 'pause' : 'play' }}-circle"></i>
                                {{ $pricing->is_active ? 'Deactivate' : 'Activate' }} Service
                            </button>
                        </form>
                        @endcan
                        <a href="{{ route('pricing.price-list', ['service_type' => $pricing->service_type]) }}" 
                           class="btn btn-outline-info">
                            <i class="bi bi-list-ul"></i> View Similar Services
                        </a>
                        @can('delete_service_pricing')
                        <form action="{{ route('pricing.destroy', $pricing->id) }}" method="POST" 
                              onsubmit="return confirm('Are you sure you want to delete this service? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-trash"></i> Delete Service
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Metadata -->
            @if($pricing->metadata && count($pricing->metadata) > 0)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark border-bottom border-secondary">
                    <h5 class="mb-0 text-white">Additional Information</h5>
                </div>
                <div class="card-body">
                    @foreach($pricing->metadata as $key => $value)
                    <div class="mb-2">
                        <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $key)) }}:</small>
                        <p class="text-dark mb-0">{{ is_array($value) ? json_encode($value) : $value }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Audit Information -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-dark border-bottom border-secondary">
            <h5 class="mb-0 text-white">Audit Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-muted">Created By</label>
                    <p class="form-control-plaintext text-dark">
                        {{ $pricing->creator->first_name ?? 'Unknown' }} {{ $pricing->creator->last_name ?? '' }}
                        <br><small class="text-muted">{{ $pricing->created_at->format('M d, Y \a\t g:i A') }}</small>
                    </p>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">Last Updated By</label>
                    <p class="form-control-plaintext text-dark">
                        {{ $pricing->updater->first_name ?? 'Unknown' }} {{ $pricing->updater->last_name ?? '' }}
                        <br><small class="text-muted">{{ $pricing->updated_at->format('M d, Y \a\t g:i A') }}</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
