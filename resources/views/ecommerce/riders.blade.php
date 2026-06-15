@extends('layouts.app')

@section('title', 'Delivery Riders')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1" style="color: #1e3a5f;">
                    <i class="bi bi-motorcycle me-2"></i>Delivery Riders
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('ecommerce.dashboard') }}">E-Commerce</a></li>
                        <li class="breadcrumb-item active">Riders</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('ecommerce.riders.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Rider
                </a>
                <a href="{{ route('ecommerce.deliveries') }}" class="btn btn-info">
                    <i class="bi bi-truck"></i> View Deliveries
                </a>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="bi bi-people fs-1 text-primary mb-2"></i>
                    <div class="fs-4 fw-bold text-dark">{{ number_format($statistics['total_riders']) }}</div>
                    <div class="text-muted small">Total Riders</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle fs-1 text-success mb-2"></i>
                    <div class="fs-4 fw-bold text-dark">{{ number_format($statistics['active_riders']) }}</div>
                    <div class="text-muted small">Active Riders</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="bi bi-truck fs-1 text-info mb-2"></i>
                    <div class="fs-4 fw-bold text-dark">{{ number_format($statistics['on_delivery']) }}</div>
                    <div class="text-muted small">On Delivery</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-secondary">
                <div class="card-body text-center">
                    <i class="bi bi-pause-circle fs-1 text-secondary mb-2"></i>
                    <div class="fs-4 fw-bold text-dark">{{ number_format($statistics['inactive_riders']) }}</div>
                    <div class="text-muted small">Inactive</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0 text-dark"><i class="bi bi-funnel me-2"></i>Filter Riders</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Name, phone, rider number..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="on_delivery" {{ request('status') == 'on_delivery' ? 'selected' : '' }}>On Delivery</option>
                        <option value="off_duty" {{ request('status') == 'off_duty' ? 'selected' : '' }}>Off Duty</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Riders Table --}}
    <div class="card">
        <div class="card-header" style="background-color: #1e3a5f;">
            <h5 class="mb-0 text-white">
                <i class="bi bi-list-ul me-2"></i>Riders List
                <span class="badge bg-light text-dark ms-2">{{ $riders->total() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Rider #</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Branch</th>
                            <th>Vehicle</th>
                            <th>Deliveries</th>
                            <th>Success Rate</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riders as $rider)
                        <tr>
                            <td>
                                <span class="fw-bold text-primary">{{ $rider->rider_number }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle text-muted me-2 fs-4"></i>
                                    <div>
                                        <div class="fw-bold">{{ $rider->user->name }}</div>
                                        <small class="text-muted">{{ $rider->user->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <i class="bi bi-telephone me-1"></i>{{ $rider->phone }}
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $rider->branch->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                @if($rider->vehicle_type)
                                    <div>
                                        <i class="bi bi-{{ $rider->vehicle_type === 'motorcycle' ? 'motorcycle' : 'car-front' }} me-1"></i>
                                        <span class="fw-bold">{{ ucfirst($rider->vehicle_type) }}</span>
                                    </div>
                                    @if($rider->vehicle_number)
                                        <small class="text-muted">{{ $rider->vehicle_number }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">Not specified</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ number_format($rider->total_deliveries) }}</span>
                            </td>
                            <td>
                                @php
                                    $successRate = $rider->total_deliveries > 0 
                                        ? round(($rider->successful_deliveries / $rider->total_deliveries) * 100, 1) 
                                        : 0;
                                @endphp
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                        <div class="progress-bar bg-{{ $successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger') }}" 
                                             role="progressbar" 
                                             style="width: {{ $successRate }}%;">
                                            {{ $successRate }}%
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="bi bi-star{{ $i <= $rider->rating ? '-fill text-warning' : ' text-muted' }}"></i>
                                    @endfor
                                    <span class="ms-1 small text-muted">({{ number_format($rider->rating, 1) }})</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'active' => 'success',
                                        'on_delivery' => 'primary',
                                        'off_duty' => 'warning',
                                        'inactive' => 'secondary',
                                    ];
                                    $statusIcons = [
                                        'active' => 'check-circle',
                                        'on_delivery' => 'truck',
                                        'off_duty' => 'pause-circle',
                                        'inactive' => 'x-circle',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$rider->status] ?? 'secondary' }}">
                                    <i class="bi bi-{{ $statusIcons[$rider->status] ?? 'circle' }} me-1"></i>
                                    {{ ucfirst(str_replace('_', ' ', $rider->status)) }}
                                </span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('ecommerce.riders.show', $rider->id) }}">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('ecommerce.riders.edit', $rider->id) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit Rider
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('ecommerce.riders.destroy', $rider->id) }}" method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to delete this rider? This action cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i>Delete Rider
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <p class="mb-0">No riders found</p>
                                <a href="{{ route('ecommerce.riders.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus-circle"></i> Add First Rider
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($riders->hasPages())
        <div class="card-footer">
            {{ $riders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
