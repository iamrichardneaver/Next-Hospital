@extends('layouts.app')

@section('title', 'Dispensing History')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-clock-history"></i> Dispensing History</h1>
            <p class="text-secondary mb-0">View all medication dispensing records</p>
        </div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('pharmacy.history.export'),
                'permission' => 'dispense_drugs',
                'params' => request()->only(['date_from', 'date_to', 'patient_id']),
            ])
            <a href="{{ route('pharmacy.dispensing') }}" class="btn btn-primary">
                <i class="bi bi-capsule-pill"></i> Dispensing Workflow
            </a>
            <a href="{{ route('pharmacy.prescriptions') }}" class="btn btn-info">
                <i class="bi bi-prescription"></i> Prescriptions
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-capsule-pill"></i>
                </div>
                <div class="stat-label">Total Dispensed</div>
                <div class="stat-value">{{ number_format($statistics['total_dispensed']) }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-label">Dispensed Today</div>
                <div class="stat-value">{{ number_format($statistics['dispensed_today']) }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-calendar-week"></i>
                </div>
                <div class="stat-label">This Week</div>
                <div class="stat-value">{{ number_format($statistics['dispensed_this_week']) }}</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <label for="patient_id" class="form-label">Patient</label>
                    <select class="form-select" id="patient_id" name="patient_id">
                        <option value="">All Patients</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}" {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                                {{ $patient->first_name }} {{ $patient->last_name }} ({{ $patient->patient_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('pharmacy.history') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Dispensing History Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Dispensing Records</h5>
        </div>
        <div class="card-body">
            @if($orders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Patient</th>
                                <th>Drug</th>
                                <th>Quantity Dispensed</th>
                                <th>Dispensed By</th>
                                <th>Prescription #</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $order->dispensed_at->format('M d, Y') }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $order->dispensed_at->format('H:i A') }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        @if($order->prescription?->patient)
                                            <strong>{{ $order->prescription->patient->first_name }} {{ $order->prescription->patient->last_name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $order->prescription->patient->patient_number }}</small>
                                        @else
                                            <strong class="text-muted">Unknown Patient</strong>
                                        @endif
                                    </div>
                                </td>
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
                                    @if($order->quantity > $order->quantity_dispensed)
                                        <br><small class="text-muted">of {{ $order->quantity }} total</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $order->dispenser->first_name ?? 'Unknown' }} {{ $order->dispenser->last_name ?? '' }}
                                </td>
                                <td>
                                    <a href="{{ route('pharmacy.prescriptions.show', $order->prescription) }}" 
                                       class="text-primary text-decoration-none">
                                        {{ $order->prescription->prescription_number }}
                                    </a>
                                </td>
                                <td>
                                    @if($order->notes)
                                        <span class="text-muted">{{ Str::limit($order->notes, 50) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $orders->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No dispensing records found</h5>
                    <p class="text-muted">No dispensing records match your current filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    padding: 1.5rem;
    color: white;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.stat-card.success {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card.info {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-card.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    opacity: 0.8;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
}
</style>
@endsection
