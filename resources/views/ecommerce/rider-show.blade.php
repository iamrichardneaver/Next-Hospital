@extends('layouts.app')

@section('title', 'Rider Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-motorcycle me-2"></i>Rider Details
        </h1>
        <div>
            <a href="{{ route('ecommerce.riders.edit', $rider->id) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('ecommerce.riders') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Rider Information -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Rider Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Rider Number:</th>
                            <td><strong>{{ $rider->rider_number }}</strong></td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>{{ $rider->user->name }}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>{{ $rider->user->email }}</td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td>{{ $rider->phone }}</td>
                        </tr>
                        <tr>
                            <th>Emergency Contact:</th>
                            <td>{{ $rider->emergency_contact ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Branch:</th>
                            <td>{{ $rider->branch->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Vehicle Type:</th>
                            <td>{{ ucfirst($rider->vehicle_type) ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Vehicle Number:</th>
                            <td>{{ $rider->vehicle_number ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>License Number:</th>
                            <td>{{ $rider->license_number ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge badge-{{
                                    $rider->status === 'active' ? 'success' :
                                    ($rider->status === 'on_delivery' ? 'info' :
                                    ($rider->status === 'off_duty' ? 'warning' : 'secondary'))
                                }}">
                                    {{ ucfirst(str_replace('_', ' ', $rider->status)) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Performance Stats -->
        <div class="col-md-4">
            <div class="card shadow mb-4 border-left-success">
                <div class="card-body">
                    <h6 class="font-weight-bold text-success">Performance Statistics</h6>
                    <hr>
                    <div class="mb-3">
                        <p class="mb-1"><strong>Total Deliveries:</strong></p>
                        <h3 class="mb-0">{{ number_format($statistics['total_deliveries']) }}</h3>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1"><strong>Successful Deliveries:</strong></p>
                        <h4 class="mb-0 text-success">{{ number_format($statistics['successful_deliveries']) }}</h4>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1"><strong>Failed Deliveries:</strong></p>
                        <h4 class="mb-0 text-danger">{{ number_format($statistics['failed_deliveries']) }}</h4>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1"><strong>Success Rate:</strong></p>
                        <h4 class="mb-0">
                            <span class="badge badge-{{ $statistics['success_rate'] >= 90 ? 'success' : ($statistics['success_rate'] >= 70 ? 'warning' : 'danger') }}">
                                {{ $statistics['success_rate'] }}%
                            </span>
                        </h4>
                    </div>
                    <div>
                        <p class="mb-1"><strong>Average Rating:</strong></p>
                        <h4 class="mb-0">
                            <i class="fas fa-star text-warning"></i> {{ number_format($statistics['average_rating'], 2) }}
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Deliveries -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Deliveries (Last 20)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Patient</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Assigned</th>
                            <th>Delivered</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentDeliveries as $delivery)
                        <tr>
                            <td>
                                <a href="{{ route('ecommerce.orders.show', $delivery->order->id) }}">
                                    {{ $delivery->order->order_number }}
                                </a>
                            </td>
                            <td>{{ $delivery->order->patient->first_name }} {{ $delivery->order->patient->last_name }}</td>
                            <td>{{ Str::limit($delivery->delivery_address, 40) }}</td>
                            <td>
                                <span class="badge badge-{{
                                    $delivery->status === 'delivered' ? 'success' :
                                    ($delivery->status === 'failed' ? 'danger' : 'info')
                                }}">
                                    {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                                </span>
                            </td>
                            <td>{{ $delivery->assigned_at ? $delivery->assigned_at->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $delivery->delivered_at ? $delivery->delivered_at->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No delivery history available</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
