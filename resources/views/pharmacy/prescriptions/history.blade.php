@extends('layouts.app')

@section('title', 'Prescription History')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-clock-history"></i> Prescription History #{{ $prescription->prescription_number }}
            </h1>
            <p class="text-secondary mb-0">Dispensing history and tracking</p>
        </div>
        <div>
            <a href="{{ route('pharmacy.prescriptions.show', $prescription) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Prescription
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Prescription Summary -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Prescription Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Prescription Number:</strong><br>
                        <span class="text-primary">{{ $prescription->prescription_number }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Patient:</strong><br>
                        @if($prescription->patient)
                            {{ $prescription->patient->first_name }} {{ $prescription->patient->last_name }}
                        @else
                            <span class="text-muted">Unknown Patient</span>
                        @endif
                    </div>
                    <div class="mb-3">
                        <strong>Doctor:</strong><br>
                        @if($prescription->doctor)
                            Dr. {{ $prescription->doctor->first_name }} {{ $prescription->doctor->last_name }}
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                    <div class="mb-3">
                        <strong>Date:</strong><br>
                        {{ $prescription->prescription_date->format('M d, Y') }}
                    </div>
                    <div class="mb-3">
                        <strong>Status:</strong><br>
                        @php
                            $statusClass = match($prescription->status) {
                                'pending' => 'badge-warning',
                                'dispensed' => 'badge-info',
                                'completed' => 'badge-success',
                                'cancelled' => 'badge-danger',
                                default => 'badge-secondary'
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($prescription->status) }}</span>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary">{{ $prescription->orders->count() }}</h4>
                            <p class="text-muted mb-0">Total Medications</p>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success">{{ $history->count() }}</h4>
                            <p class="text-muted mb-0">Dispensed Items</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-info">{{ $prescription->orders->sum('quantity') }}</h4>
                            <p class="text-muted mb-0">Total Quantity</p>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning">{{ $prescription->orders->sum('quantity_dispensed') }}</h4>
                            <p class="text-muted mb-0">Dispensed Quantity</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dispensing History -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list-check"></i> Dispensing History</h5>
                </div>
                <div class="card-body">
                    @if($history->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Drug</th>
                                        <th>Quantity Dispensed</th>
                                        <th>Dispensed By</th>
                                        <th>Date & Time</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($history as $index => $order)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div>
                                                <strong>{{ $order->drug->name }}</strong>
                                                @if($order->drug->generic_name)
                                                    <br><small class="text-muted">{{ $order->drug->generic_name }}</small>
                                                @endif
                                                <br><small class="text-muted">{{ $order->drug->strength }} {{ $order->drug->unit }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ $order->quantity_dispensed }}</span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $order->dispenser->first_name ?? 'Unknown' }} {{ $order->dispenser->last_name ?? '' }}</strong>
                                                @if($order->dispenser->email)
                                                    <br><small class="text-muted">{{ $order->dispenser->email }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $order->dispensed_at->format('M d, Y') }}</strong>
                                                <br><small class="text-muted">{{ $order->dispensed_at->format('H:i') }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($order->notes)
                                                <span class="text-muted">{{ $order->notes }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <h5 class="mt-3 text-muted">No dispensing history</h5>
                            <p class="text-muted">No medications have been dispensed for this prescription yet.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Timeline View -->
            @if($history->count() > 0)
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-timeline"></i> Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach($history as $index => $order)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $order->drug->name }}</h6>
                                        <p class="text-muted mb-1">
                                            <strong>Quantity:</strong> {{ $order->quantity_dispensed }} 
                                            | <strong>Dispensed by:</strong> {{ $order->dispenser->first_name ?? 'Unknown' }}
                                        </p>
                                        @if($order->notes)
                                        <p class="text-muted mb-0">
                                            <strong>Notes:</strong> {{ $order->notes }}
                                        </p>
                                        @endif
                                    </div>
                                    <small class="text-muted">
                                        {{ $order->dispensed_at->format('M d, Y H:i') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #28a745;
}
</style>
@endsection
