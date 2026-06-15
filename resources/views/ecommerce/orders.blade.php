@extends('layouts.app')

@section('title', 'Store Orders')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
        <h1 class="h3 mb-1" style="color: #1e3a5f;">
            <i class="bi bi-shopping-cart me-2"></i>Store Orders
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ecommerce.dashboard') }}">E-Commerce</a></li>
                <li class="breadcrumb-item active">Orders</li>
            </ol>
        </nav>
        </div>
        @include('components.export-dropdown', [
            'exportRoute' => route('ecommerce.orders.export'),
            'permission' => 'view_store_items',
            'params' => request()->only(['search', 'status', 'delivery_method', 'start_date', 'end_date']),
        ])
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-2 mb-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="bi bi-cart fs-1 text-primary mb-2"></i>
                    <div class="fs-5 fw-bold text-dark">{{ number_format($statistics['total_orders']) }}</div>
                    <div class="text-muted small">Total Orders</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="bi bi-clock-history fs-1 text-warning mb-2"></i>
                    <div class="fs-5 fw-bold text-dark">{{ number_format($statistics['pending_orders']) }}</div>
                    <div class="text-muted small">Pending</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="bi bi-gear fs-1 text-info mb-2"></i>
                    <div class="fs-5 fw-bold text-dark">{{ number_format($statistics['processing_orders']) }}</div>
                    <div class="text-muted small">Processing</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="bi bi-truck fs-1 text-primary mb-2"></i>
                    <div class="fs-5 fw-bold text-dark">{{ number_format($statistics['shipped_orders']) }}</div>
                    <div class="text-muted small">Shipped</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle fs-1 text-success mb-2"></i>
                    <div class="fs-5 fw-bold text-dark">{{ number_format($statistics['delivered_orders']) }}</div>
                    <div class="text-muted small">Delivered</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <i class="bi bi-x-circle fs-1 text-danger mb-2"></i>
                    <div class="fs-5 fw-bold text-dark">{{ number_format($statistics['cancelled_orders']) }}</div>
                    <div class="text-muted small">Cancelled</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0 text-dark"><i class="bi bi-funnel me-2"></i>Filter Orders</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Order number, patient name..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="ready" {{ request('status') == 'ready' ? 'selected' : '' }}>Ready</option>
                        <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Delivery Method</label>
                    <select name="delivery_method" class="form-select">
                        <option value="">All Methods</option>
                        <option value="pickup" {{ request('delivery_method') == 'pickup' ? 'selected' : '' }}>Pickup</option>
                        <option value="delivery" {{ request('delivery_method') == 'delivery' ? 'selected' : '' }}>Home Delivery</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Orders Table --}}
    <div class="card" id="ordersTableCard" style="overflow: visible !important;">
        <div class="card-header" style="background-color: #1e3a5f;">
            <h5 class="mb-0 text-white">
                <i class="bi bi-list-ul me-2"></i>Orders List
                <span class="badge bg-light text-dark ms-2">{{ $orders->total() }}</span>
            </h5>
        </div>
        <div class="card-body p-0" style="overflow: visible !important;">
            <div class="table-responsive" id="ordersTableContainer" style="overflow-x: auto; overflow-y: visible !important;">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Order #</th>
                            <th>Patient</th>
                            <th>Branch</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Delivery</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td>
                                <span class="fw-bold text-primary">{{ $order->order_number }}</span>
                                @if($order->order_source)
                                <br>
                                <span class="badge bg-{{ $order->order_source === 'mobile_app' ? 'info' : 'secondary' }} mt-1" title="Order Source">
                                    <i class="bi bi-{{ $order->order_source === 'mobile_app' ? 'phone' : 'globe' }}"></i>
                                    {{ $order->order_source === 'mobile_app' ? 'Mobile App' : 'Web' }}
                                </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle text-muted me-2"></i>
                                    <span>{{ $order->patient->first_name }} {{ $order->patient->last_name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $order->branch->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $order->items->count() }} item(s)</span>
                            </td>
                            <td>
                                <span class="fw-bold text-success">GH₵ {{ number_format($order->total_amount, 2) }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $order->delivery_method === 'delivery' ? 'info' : 'secondary' }}">
                                    <i class="bi bi-{{ $order->delivery_method === 'delivery' ? 'truck' : 'shop' }}"></i>
                                    {{ ucfirst($order->delivery_method) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'processing' => 'primary',
                                        'ready' => 'info',
                                        'shipped' => 'primary',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$order->status] ?? 'secondary' }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $order->created_at->format('M d, Y h:i A') }}</small>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('ecommerce.orders.show', $order->id) }}">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a>
                                        </li>
                                        @if($order->status !== 'delivered' && $order->status !== 'cancelled')
                                            <li><hr class="dropdown-divider"></li>
                                            <li class="px-3 py-1">
                                                <small class="text-muted fw-bold">CHANGE STATUS:</small>
                                            </li>
                                            @if($order->status === 'pending')
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="updateStatus({{ $order->id }}, 'confirmed')">
                                                        <i class="bi bi-check-circle me-2 text-success"></i>Confirm Order
                                                    </a>
                                                </li>
                                            @endif
                                            @if($order->status === 'confirmed')
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="updateStatus({{ $order->id }}, 'processing')">
                                                        <i class="bi bi-arrow-repeat me-2 text-info"></i>Start Processing
                                                    </a>
                                                </li>
                                            @endif
                                            @if($order->status === 'processing')
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="updateStatus({{ $order->id }}, 'ready')">
                                                        <i class="bi bi-box-seam me-2 text-primary"></i>Mark as Ready
                                                    </a>
                                                </li>
                                            @endif
                                            @if($order->status === 'ready')
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="updateStatus({{ $order->id }}, 'shipped')">
                                                        <i class="bi bi-truck me-2 text-primary"></i>Ship Order
                                                    </a>
                                                </li>
                                            @endif
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" onclick="updateStatus({{ $order->id }}, 'cancelled')">
                                                    <i class="bi bi-x-circle me-2"></i>Cancel Order
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
                                <p class="mb-0">No orders found matching your criteria</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
        <div class="card-footer">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Status Update Form (Hidden) --}}
<form id="statusUpdateForm" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="status" id="statusInput">
</form>

@endsection

@push('styles')
<style>
/* Fix dropdown menu clipping in orders table - AGGRESSIVE FIX */
#ordersTableCard {
    overflow: visible !important;
}

#ordersTableCard .card-body {
    overflow: visible !important;
}

#ordersTableContainer {
    overflow-x: auto !important;
    overflow-y: visible !important;
}

/* Remove any overflow restrictions from the table */
#ordersTableContainer table {
    overflow: visible !important;
}

#ordersTableContainer tbody {
    overflow: visible !important;
}

#ordersTableContainer tbody tr {
    overflow: visible !important;
}

#ordersTableContainer tbody tr td {
    overflow: visible !important;
}

/* Ensure dropdowns appear above everything when moved to body */
.dropdown-menu {
    z-index: 9999 !important;
}

.dropdown-menu.show {
    display: block !important;
}

/* For very long dropdown menus */
.dropdown-menu {
    max-height: 400px;
    overflow-y: auto;
}

/* Add shadow to dropdown when outside table */
body > .dropdown-menu {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>
@endpush

@push('scripts')
<script>
// Fix dropdown positioning - Move dropdown menus outside table container
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggles = document.querySelectorAll('#ordersTableContainer .dropdown-toggle');
    
    dropdownToggles.forEach(function(toggle) {
        // When dropdown is about to show
        toggle.addEventListener('show.bs.dropdown', function(event) {
            const dropdownMenu = this.nextElementSibling;
            
            if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                // Get the button's position
                const rect = this.getBoundingClientRect();
                
                // Move dropdown to body
                document.body.appendChild(dropdownMenu);
                
                // Position it absolutely where the button is
                dropdownMenu.style.position = 'fixed';
                dropdownMenu.style.top = (rect.bottom + 2) + 'px';
                dropdownMenu.style.left = (rect.right - dropdownMenu.offsetWidth) + 'px';
                dropdownMenu.style.zIndex = '9999';
            }
        });
        
        // When dropdown is hidden, move it back
        toggle.addEventListener('hidden.bs.dropdown', function(event) {
            const dropdownMenu = document.querySelector('.dropdown-menu.show');
            if (dropdownMenu) {
                this.parentElement.appendChild(dropdownMenu);
                dropdownMenu.style.position = '';
                dropdownMenu.style.top = '';
                dropdownMenu.style.left = '';
            }
        });
        
        // Handle scroll - update position or close if scrolled far
        let scrollableContainer = document.getElementById('ordersTableContainer');
        let updatePosition = function() {
            const dropdownMenu = document.querySelector('.dropdown-menu.show');
            if (dropdownMenu) {
                const rect = toggle.getBoundingClientRect();
                
                // Close dropdown if button scrolled out of view
                if (rect.top < 0 || rect.bottom > window.innerHeight || 
                    rect.left < 0 || rect.right > window.innerWidth) {
                    bootstrap.Dropdown.getInstance(toggle)?.hide();
                } else {
                    // Update position
                    dropdownMenu.style.top = (rect.bottom + 2) + 'px';
                    dropdownMenu.style.left = (rect.right - dropdownMenu.offsetWidth) + 'px';
                }
            }
        };
        
        scrollableContainer.addEventListener('scroll', updatePosition);
        window.addEventListener('scroll', updatePosition);
        window.addEventListener('resize', updatePosition);
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdownMenu = document.querySelector('#ordersTableContainer ~ .dropdown-menu.show');
        if (dropdownMenu && !event.target.closest('.dropdown')) {
            const toggle = document.querySelector('#ordersTableContainer .dropdown-toggle[aria-expanded="true"]');
            if (toggle) {
                bootstrap.Dropdown.getInstance(toggle)?.hide();
            }
        }
    });
});

function updateStatus(orderId, status) {
    const statusLabels = {
        'confirmed': 'Confirmed',
        'processing': 'Processing',
        'ready': 'Ready for Pickup/Delivery',
        'shipped': 'Shipped',
        'delivered': 'Delivered',
        'cancelled': 'Cancelled'
    };
    
    if (confirm('Are you sure you want to update the order status to "' + statusLabels[status] + '"?')) {
        const form = document.getElementById('statusUpdateForm');
        form.action = '{{ route("ecommerce.orders.status", ":id") }}'.replace(':id', orderId);
        document.getElementById('statusInput').value = status;
        form.submit();
    }
}
</script>
@endpush
