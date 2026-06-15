@extends('layouts.app')

@section('title', 'Delivery Management')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1" style="color: #1e3a5f;">
                    <i class="bi bi-truck me-2"></i>Delivery Management
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('ecommerce.dashboard') }}">E-Commerce</a></li>
                        <li class="breadcrumb-item active">Deliveries</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('ecommerce.riders') }}" class="btn btn-info">
                    <i class="bi bi-motorcycle"></i> Manage Riders
                </a>
                <a href="{{ route('ecommerce.dashboard') }}" class="btn btn-secondary">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-2 mb-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="bi bi-truck fs-1 text-primary mb-2"></i>
                    <div class="fs-5 fw-bold text-dark">{{ number_format($statistics['total_deliveries']) }}</div>
                    <div class="text-muted small">Total Deliveries</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="bi bi-hourglass-split fs-1 text-warning mb-2"></i>
                    <div class="fs-5 fw-bold text-dark">{{ number_format($statistics['pending_deliveries']) }}</div>
                    <div class="text-muted small">Pending</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="bi bi-person-check fs-1 text-info mb-2"></i>
                    <div class="fs-5 fw-bold text-dark">{{ number_format($statistics['assigned_deliveries']) }}</div>
                    <div class="text-muted small">Assigned</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="bi bi-arrows-move fs-1 text-primary mb-2"></i>
                    <div class="fs-5 fw-bold text-dark">{{ number_format($statistics['in_transit_deliveries']) }}</div>
                    <div class="text-muted small">In Transit</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle fs-1 text-success mb-2"></i>
                    <div class="fs-5 fw-bold text-dark">{{ number_format($statistics['delivered']) }}</div>
                    <div class="text-muted small">Delivered</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-circle fs-1 text-danger mb-2"></i>
                    <div class="fs-5 fw-bold text-dark">{{ number_format($statistics['failed_deliveries']) }}</div>
                    <div class="text-muted small">Failed</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0 text-dark"><i class="bi bi-funnel me-2"></i>Filter Deliveries</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Order number, patient name, address..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>Assigned</option>
                        <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rider</label>
                    <select name="rider_id" class="form-select">
                        <option value="">All Riders</option>
                        @foreach($riders as $rider)
                            <option value="{{ $rider->id }}" {{ request('rider_id') == $rider->id ? 'selected' : '' }}>
                                {{ $rider->user->name }} ({{ $rider->rider_number }})
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

    {{-- Deliveries Table --}}
    <div class="card">
        <div class="card-header" style="background-color: #1e3a5f;">
            <h5 class="mb-0 text-white">
                <i class="bi bi-list-ul me-2"></i>Deliveries List
                <span class="badge bg-light text-dark ms-2">{{ $deliveries->total() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Order #</th>
                            <th>Patient</th>
                            <th>Delivery Address</th>
                            <th>Phone</th>
                            <th>Rider</th>
                            <th>Status</th>
                            <th>Est. Delivery</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deliveries as $delivery)
                        <tr>
                            <td><span class="badge bg-secondary">#{{ $delivery->id }}</span></td>
                            <td>
                                <a href="{{ route('ecommerce.orders.show', $delivery->order->id) }}" class="text-primary fw-bold text-decoration-none">
                                    {{ $delivery->order->order_number }}
                                </a>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle text-muted me-2"></i>
                                    <span>{{ $delivery->order->patient->first_name }} {{ $delivery->order->patient->last_name }}</span>
                                </div>
                            </td>
                            <td>
                                <small class="text-muted">{{ Str::limit($delivery->delivery_address, 40) }}</small>
                            </td>
                            <td>
                                <i class="bi bi-telephone text-muted me-1"></i>
                                {{ $delivery->delivery_phone }}
                            </td>
                            <td>
                                @if($delivery->rider)
                                    <span class="badge bg-info">
                                        <i class="bi bi-motorcycle me-1"></i>
                                        {{ $delivery->rider->user->name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-dash-circle me-1"></i>
                                        Not Assigned
                                    </span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'assigned' => 'info',
                                        'in_transit' => 'primary',
                                        'delivered' => 'success',
                                        'failed' => 'danger',
                                        'cancelled' => 'secondary',
                                    ];
                                    $statusIcons = [
                                        'pending' => 'hourglass-split',
                                        'assigned' => 'person-check',
                                        'in_transit' => 'truck',
                                        'delivered' => 'check-circle',
                                        'failed' => 'x-circle',
                                        'cancelled' => 'slash-circle',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$delivery->status] ?? 'secondary' }}">
                                    <i class="bi bi-{{ $statusIcons[$delivery->status] ?? 'circle' }} me-1"></i>
                                    {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                                </span>
                            </td>
                            <td>
                                @if($delivery->estimated_delivery)
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        {{ $delivery->estimated_delivery->format('M d, Y H:i') }}
                                    </small>
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('ecommerce.orders.show', $delivery->order->id) }}">
                                                <i class="bi bi-eye me-2"></i>View Order
                                            </a>
                                        </li>
                                        
                                        @if($delivery->status === 'pending')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="showAssignRiderModal({{ $delivery->id }})">
                                                    <i class="bi bi-person-plus me-2"></i>Assign Rider
                                                </a>
                                            </li>
                                        @endif

                                        @if($delivery->status === 'assigned')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="updateDeliveryStatus({{ $delivery->id }}, 'in_transit')">
                                                    <i class="bi bi-truck me-2 text-primary"></i>Mark In Transit
                                                </a>
                                            </li>
                                        @endif

                                        @if($delivery->status === 'in_transit')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="updateDeliveryStatus({{ $delivery->id }}, 'delivered')">
                                                    <i class="bi bi-check-circle me-2 text-success"></i>Mark Delivered
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" onclick="updateDeliveryStatus({{ $delivery->id }}, 'failed')">
                                                    <i class="bi bi-x-circle me-2"></i>Mark Failed
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <p class="mb-0">No deliveries found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($deliveries->hasPages())
        <div class="card-footer">
            {{ $deliveries->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Assign Rider Modal --}}
<div class="modal fade" id="assignRiderModal" tabindex="-1" aria-labelledby="assignRiderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1e3a5f;">
                <h5 class="modal-title text-white" id="assignRiderModalLabel">
                    <i class="bi bi-person-plus me-2"></i>Assign Delivery Rider
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignRiderForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Rider <span class="text-danger">*</span></label>
                        <select name="rider_id" class="form-select" required>
                            <option value="">Choose a rider...</option>
                            @foreach($riders as $rider)
                                <option value="{{ $rider->id }}">
                                    {{ $rider->user->name }} - {{ $rider->rider_number }}
                                    <span class="badge bg-{{ $rider->status === 'available' ? 'success' : 'warning' }}">
                                        ({{ ucfirst($rider->status) }})
                                    </span>
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Choose an available rider for this delivery</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estimated Delivery Time</label>
                        <input type="datetime-local" name="estimated_delivery" class="form-control" 
                               value="{{ now()->addHours(2)->format('Y-m-d\TH:i') }}">
                        <small class="text-muted">When should this delivery reach the customer?</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2" 
                                  placeholder="Special instructions for the rider..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Assign Rider
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Update Status Form (Hidden) --}}
<form id="statusUpdateForm" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="status" id="statusInput">
</form>

@endsection

@push('scripts')
<script>
function showAssignRiderModal(deliveryId) {
    const form = document.getElementById('assignRiderForm');
    form.action = '{{ route("ecommerce.deliveries.assign", ":id") }}'.replace(':id', deliveryId);
    const modal = new bootstrap.Modal(document.getElementById('assignRiderModal'));
    modal.show();
}

function updateDeliveryStatus(deliveryId, status) {
    const statusLabels = {
        'assigned': 'Assigned',
        'in_transit': 'In Transit',
        'delivered': 'Delivered',
        'failed': 'Failed',
        'cancelled': 'Cancelled'
    };
    
    let message = 'Are you sure you want to mark this delivery as "' + statusLabels[status] + '"?';
    
    if (confirm(message)) {
        const form = document.getElementById('statusUpdateForm');
        form.action = '{{ route("ecommerce.deliveries.status", ":id") }}'.replace(':id', deliveryId);
        document.getElementById('statusInput').value = status;
        form.submit();
    }
}
</script>
@endpush
