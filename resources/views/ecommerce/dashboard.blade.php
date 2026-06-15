@extends('layouts.app')

@section('title', 'E-Commerce Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">E-Commerce Dashboard</h1>
            <p class="text-secondary mb-0">Overview of store, orders, deliveries, and riders</p>
        </div>
        <div>
            <a href="{{ route('ecommerce.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Item
            </a>
            <a href="{{ route('ecommerce.orders') }}" class="btn btn-info">
                <i class="bi bi-cart-check"></i> View Orders
            </a>
        </div>
    </div>

    <!-- Statistics Cards Row 1 - Store Items -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stat-label">Total Store Items</div>
                <div class="stat-value">{{ number_format($statistics['total_items']) }}</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-label">Active Items</div>
                <div class="stat-value">{{ number_format($statistics['active_items']) }}</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-label">Low Stock Items</div>
                <div class="stat-value">{{ number_format($statistics['low_stock']) }}</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-label">Out of Stock</div>
                <div class="stat-value">{{ number_format($statistics['out_of_stock']) }}</div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Row 2 - Orders -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-cart"></i>
                </div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value">{{ number_format($statistics['total_orders']) }}</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-label">Pending Orders</div>
                <div class="stat-value">{{ number_format($statistics['pending_orders']) }}</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div class="stat-label">Processing Orders</div>
                <div class="stat-value">{{ number_format($statistics['processing_orders']) }}</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-check"></i>
                </div>
                <div class="stat-label">Delivered Orders</div>
                <div class="stat-value">{{ number_format($statistics['delivered_orders']) }}</div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Row 3 - Deliveries & Riders -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-truck"></i>
                </div>
                <div class="stat-label">Total Deliveries</div>
                <div class="stat-value">{{ number_format($statistics['total_deliveries']) }}</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-label">Pending Deliveries</div>
                <div class="stat-value">{{ number_format($statistics['pending_deliveries']) }}</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="stat-label">Active Riders</div>
                <div class="stat-value">{{ number_format($statistics['active_riders']) }}</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-bicycle"></i>
                </div>
                <div class="stat-label">Riders On Delivery</div>
                <div class="stat-value">{{ number_format($statistics['riders_on_delivery']) }}</div>
            </div>
        </div>
    </div>

    <!-- Recent Orders and Pending Deliveries -->
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                    <a href="{{ route('ecommerce.orders') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Patient</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('ecommerce.orders.show', $order->id) }}">
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td>{{ $order->patient->first_name }} {{ $order->patient->last_name }}</td>
                                    <td>GH₵ {{ number_format($order->total_amount, 2) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $order->status === 'delivered' ? 'success' : ($order->status === 'cancelled' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No recent orders</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Deliveries -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-warning">Pending Deliveries</h6>
                    <a href="{{ route('ecommerce.deliveries') }}" class="btn btn-sm btn-warning">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Patient</th>
                                    <th>Rider</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingDeliveries as $delivery)
                                <tr>
                                    <td>
                                        <a href="{{ route('ecommerce.orders.show', $delivery->order->id) }}">
                                            {{ $delivery->order->order_number }}
                                        </a>
                                    </td>
                                    <td>{{ $delivery->order->patient->first_name }} {{ $delivery->order->patient->last_name }}</td>
                                    <td>
                                        @if($delivery->rider)
                                            {{ $delivery->rider->user->name }}
                                        @else
                                            <span class="text-muted">Not Assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $delivery->status === 'delivered' ? 'success' : 'info' }}">
                                            {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('ecommerce.deliveries') }}" class="btn btn-xs btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No pending deliveries</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection