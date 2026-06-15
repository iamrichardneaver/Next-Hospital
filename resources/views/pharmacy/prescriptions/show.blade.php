@extends('layouts.app')

@section('title', 'Prescription Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-prescription"></i> Prescription #{{ $prescription->prescription_number }}
            </h1>
            <p class="text-secondary mb-0">Prescription details and dispensing</p>
        </div>
        <div class="d-flex gap-2">
            @can('edit_drugs')
            <a href="{{ route('pharmacy.prescriptions.edit', $prescription) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
            
            <a href="{{ route('pharmacy.prescriptions.print', $prescription) }}" class="btn btn-outline-info" target="_blank">
                <i class="bi bi-printer"></i> Print
            </a>
            
            <a href="{{ route('pharmacy.prescriptions.history', $prescription) }}" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history"></i> History
            </a>
            
            <a href="{{ route('pharmacy.prescriptions') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Prescriptions
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
        @if(session('payment_required'))
        <div class="mt-2 d-flex flex-wrap gap-2">
            @can('process_payments')
            <a href="{{ session('cashier_url', url('/cashier') . '?patient_id=' . $prescription->patient_id) }}" class="btn btn-sm btn-warning">
                <i class="bi bi-cash-coin"></i> Go to Cashier
            </a>
            @else
            <a href="{{ session('cashier_url', url('/cashier') . '?patient_id=' . $prescription->patient_id) }}" class="btn btn-sm btn-warning" target="_blank">
                <i class="bi bi-cash-coin"></i> Send Patient to Cashier
            </a>
            @endcan
            @canany(['view_invoices', 'manage_billing'])
            <a href="{{ route('billing.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-receipt"></i> View Invoices
            </a>
            @endcanany
        </div>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @php
        $paymentBlocked = ($paymentSummary['payment_required'] ?? false) && !($paymentSummary['can_proceed'] ?? true);
        $amountDue = $paymentSummary['amount_due'] ?? session('amount_due', 0);
        $cashierUrl = $paymentSummary['cashier_url'] ?? url('/cashier') . '?patient_id=' . $prescription->patient_id;
    @endphp

    @if($paymentBlocked)
    <div class="alert alert-warning border-warning shadow-sm" role="alert">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-cash-coin fs-3 text-warning"></i>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">Payment Required Before Dispensing</h5>
                <p class="mb-2">
                    Outpatient policy requires full payment before pharmacy service.
                    <strong class="text-danger">Amount due: GH₵{{ number_format($amountDue, 2) }}</strong>
                </p>
                @if(!empty($chargeBreakdown))
                <div class="mb-3">
                    <small class="text-muted d-block mb-1"><strong>Pending charges breakdown:</strong></small>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($chargeBreakdown as $group)
                        <span class="badge bg-light text-dark border">
                            {{ $group['label'] }}: {{ $group['count'] }} &middot; GH₵{{ number_format($group['amount'], 2) }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
                <div class="d-flex flex-wrap gap-2">
                    @can('process_payments')
                    <a href="{{ $cashierUrl }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-cash-coin"></i> Go to Cashier
                    </a>
                    @else
                    <a href="{{ $cashierUrl }}" class="btn btn-warning btn-sm" target="_blank">
                        <i class="bi bi-cash-coin"></i> Send Patient to Cashier
                    </a>
                    @endcan
                    @canany(['view_invoices', 'manage_billing'])
                    <a href="{{ route('billing.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-receipt"></i> View Invoices
                    </a>
                    @endcanany
                </div>
                <small class="text-muted d-block mt-2">
                    Collect full payment at the cashier, then return here to dispense medications.
                </small>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <!-- Prescription Information -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Prescription Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Prescription Number:</strong><br>
                        <span class="text-primary">{{ $prescription->prescription_number }}</span>
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
                                'active' => 'badge-info',
                                'dispensed' => 'badge-info',
                                'completed' => 'badge-success',
                                'cancelled' => 'badge-danger',
                                default => 'badge-secondary'
                            };
                            $detailedStatus = $prescription->getDetailedStatus();
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($prescription->status) }}</span>
                        
                        <!-- Detailed Status -->
                        <div class="mt-2">
                            <small class="text-muted">
                                <strong>Progress:</strong> {{ $detailedStatus['completion_percentage'] }}% 
                                ({{ $detailedStatus['dispensed_orders'] }}/{{ $detailedStatus['total_orders'] }} dispensed)
                            </small><br>
                            <small class="text-muted">
                                <strong>Urgency:</strong> 
                                <span class="badge bg-{{ $detailedStatus['urgency_level'] == 'urgent' ? 'danger' : ($detailedStatus['urgency_level'] == 'delayed' ? 'warning' : 'success') }}">
                                    {{ ucfirst($detailedStatus['urgency_level']) }}
                                </span>
                            </small>
                        </div>
                    </div>
                    @if($prescription->notes)
                    <div class="mb-3">
                        <strong>Notes:</strong><br>
                        <p class="text-muted">{{ $prescription->notes }}</p>
                    </div>
                    @endif
                    
                    <!-- Prescription Value -->
                    <div class="mb-3">
                        <strong>Total Value:</strong><br>
                        <span class="h5 text-success">GH₵{{ number_format($prescription->getTotalValue(), 2) }}</span>
                    </div>
                    
                    <!-- Prescription Actions -->
                    <div class="mt-3">
                        @if($prescription->status === 'pending' || $prescription->status === 'active')
                            <div class="d-grid gap-2">
                                @if($paymentBlocked ?? false)
                                <button type="button" class="btn btn-success btn-sm" disabled title="Full payment required before dispensing">
                                    <i class="bi bi-capsule-pill"></i> Start Dispensing (Payment Required)
                                </button>
                                @else
                                <a href="{{ route('pharmacy.prescriptions.show', $prescription) }}?dispense=true" 
                                   class="btn btn-success btn-sm">
                                    <i class="bi bi-capsule-pill"></i> Start Dispensing
                                </a>
                                @endif
                                
                                @can('edit_drugs')
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                    <i class="bi bi-x-circle"></i> Cancel Prescription
                                </button>
                                @endcan
                            </div>
                        @elseif($prescription->status === 'dispensed')
                            <div class="d-grid gap-2">
                                <!-- Billing Integration -->
                                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#billingModal">
                                    <i class="bi bi-receipt"></i> Generate Invoice
                                </button>
                                
                                <form method="POST" action="{{ route('pharmacy.prescriptions.complete', $prescription) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" 
                                            onclick="return confirm('Mark this prescription as completed?')">
                                        <i class="bi bi-check-circle"></i> Mark Complete
                                    </button>
                                </form>
                            </div>
                        @elseif($prescription->status === 'completed')
                            <div class="alert alert-success mb-0">
                                <i class="bi bi-check-circle"></i> Prescription completed successfully
                            </div>
                        @elseif($prescription->status === 'cancelled')
                            <div class="alert alert-danger mb-0">
                                <i class="bi bi-x-circle"></i> Prescription has been cancelled
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Patient Information -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Patient Information</h5>
                </div>
                <div class="card-body">
                    @if($prescription->patient)
                        <div class="mb-2">
                            <strong>Name:</strong><br>
                            {{ $prescription->patient->first_name }} {{ $prescription->patient->last_name }}
                        </div>
                        <div class="mb-2">
                            <strong>Patient Number:</strong><br>
                            <span class="text-primary">{{ $prescription->patient->patient_number }}</span>
                        </div>
                        <div class="mb-2">
                            <strong>Phone:</strong><br>
                            {{ $prescription->patient->phone ?? 'N/A' }}
                        </div>
                        <div class="mb-2">
                            <strong>Date of Birth:</strong><br>
                            {{ $prescription->patient->date_of_birth ? $prescription->patient->date_of_birth->format('M d, Y') : 'N/A' }}
                        </div>
                    @else
                        <div class="mb-2">
                            <strong>Name:</strong><br>
                            <span class="text-muted">Unknown Patient</span>
                        </div>
                        <div class="mb-2">
                            <strong>Patient Number:</strong><br>
                            <span class="text-muted">N/A</span>
                        </div>
                        <div class="mb-2">
                            <strong>Phone:</strong><br>
                            <span class="text-muted">N/A</span>
                        </div>
                        <div class="mb-2">
                            <strong>Date of Birth:</strong><br>
                            <span class="text-muted">N/A</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Doctor Information -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person-badge"></i> Doctor Information</h5>
                </div>
                <div class="card-body">
                    @if($prescription->doctor)
                        <div class="mb-2">
                            <strong>Name:</strong><br>
                            {{ $prescription->doctor->first_name }} {{ $prescription->doctor->last_name }}
                        </div>
                        <div class="mb-2">
                            <strong>Email:</strong><br>
                            {{ $prescription->doctor->email }}
                        </div>
                    @else
                        <div class="mb-2">
                            <strong>Name:</strong><br>
                            <span class="text-muted">N/A</span>
                        </div>
                        <div class="mb-2">
                            <strong>Email:</strong><br>
                            <span class="text-muted">N/A</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Medications -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-capsule-pill"></i> Medications</h5>
                    @if(request('dispense') && ($prescription->status === 'pending' || $prescription->status === 'active'))
                    <span class="badge bg-warning">Dispensing Mode</span>
                    @endif
                </div>
                <div class="card-body">
                    @if($prescription->orders->count() > 0)
                        @if(request('dispense') && ($prescription->status === 'pending' || $prescription->status === 'active'))
                            <!-- Dispensing Form -->
                            <form method="POST" action="{{ route('pharmacy.prescriptions.dispense', $prescription) }}">
                                @csrf
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Drug</th>
                                                <th>Quantity</th>
                                                <th>Dispensed</th>
                                                <th>Remaining</th>
                                                <th>Stock Available</th>
                                                <th>Dispense Qty</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($prescription->orders as $order)
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong>{{ $order->drug->name }}</strong>
                                                        @if($order->drug->generic_name)
                                                            <br><small class="text-muted">{{ $order->drug->generic_name }}</small>
                                                        @endif
                                                        <br><small class="text-muted">{{ $order->drug->strength }} {{ $order->drug->unit }}</small>
                                                    </div>
                                                </td>
                                                <td>{{ $order->quantity }}</td>
                                                <td>{{ $order->quantity_dispensed }}</td>
                                                <td>
                                                    <span class="badge bg-info">{{ $order->getRemainingQuantity() }}</span>
                                                </td>
                                                <td>
                                                    @php
                                                        $stock = \App\Models\DrugStock::where('drug_id', $order->drug_id)
                                                            ->where('branch_id', $prescription->branch_id)
                                                            ->first();
                                                        $availableStock = $stock ? $stock->current_stock : 0;
                                                    @endphp
                                                    @if($availableStock > 0)
                                                        <span class="badge bg-success">{{ $availableStock }}</span>
                                                    @else
                                                        <span class="badge bg-danger">Out of Stock</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($order->getRemainingQuantity() > 0)
                                                        <input type="hidden" name="orders[{{ $loop->index }}][order_id]" value="{{ $order->id }}">
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="orders[{{ $loop->index }}][quantity_dispensed]" 
                                                               min="1" max="{{ min($order->getRemainingQuantity(), $availableStock) }}" 
                                                               value="{{ min($order->getRemainingQuantity(), $availableStock) }}" required>
                                                    @else
                                                        <span class="text-success">Fully Dispensed</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($order->getRemainingQuantity() > 0)
                                                        <input type="text" class="form-control form-control-sm" 
                                                               name="orders[{{ $loop->index }}][notes]" 
                                                               placeholder="Dispensing notes...">
                                                    @else
                                                        <small class="text-muted">-</small>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="d-flex justify-content-end mt-3">
                                    @if($paymentBlocked ?? false)
                                    <button type="button" class="btn btn-secondary" disabled title="Full payment required before dispensing">
                                        <i class="bi bi-lock"></i> Dispense Blocked — Payment Required
                                    </button>
                                    @else
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-capsule-pill"></i> Dispense Medications
                                    </button>
                                    @endif
                                </div>
                            </form>
                        @else
                            <!-- View Mode -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Drug</th>
                                            <th>Dosage</th>
                                            <th>Frequency</th>
                                            <th>Duration</th>
                                            <th>Quantity</th>
                                            <th>Dispensed</th>
                                            <th>Remaining</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($prescription->orders as $order)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>{{ $order->drug->name }}</strong>
                                                    @if($order->drug->generic_name)
                                                        <br><small class="text-muted">{{ $order->drug->generic_name }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $order->dosage_instructions ?? 'N/A' }}</td>
                                            <td>{{ $order->frequency ?? 'N/A' }}</td>
                                            <td>{{ $order->duration ?? 'N/A' }}</td>
                                            <td>{{ $order->quantity }}</td>
                                            <td>{{ $order->quantity_dispensed }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ $order->getRemainingQuantity() }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $stock = \App\Models\DrugStock::where('drug_id', $order->drug_id)
                                                        ->where('branch_id', $prescription->branch_id)
                                                        ->first();
                                                    $availableStock = $stock ? $stock->current_stock : 0;
                                                @endphp
                                                @if($availableStock > 0)
                                                    <span class="badge bg-success">{{ $availableStock }}</span>
                                                @else
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $orderStatusClass = match($order->status) {
                                                        'pending' => 'badge-warning',
                                                        'dispensed' => 'badge-success',
                                                        default => 'badge-secondary'
                                                    };
                                                @endphp
                                                <span class="badge {{ $orderStatusClass }}">{{ ucfirst($order->status) }}</span>
                                            </td>
                                            <td>
                                                @if($order->status === 'dispensed' && $order->dispensed_at)
                                                    <small class="text-muted">
                                                        Dispensed by {{ $order->dispenser->first_name ?? 'Unknown' }}<br>
                                                        {{ $order->dispensed_at->format('M d, Y H:i') }}
                                                    </small>
                                                @elseif($order->status === 'pending' && $availableStock > 0)
                                                    <small class="text-success">
                                                        <i class="bi bi-check-circle"></i> Ready to dispense
                                                    </small>
                                                @elseif($order->status === 'pending' && $availableStock == 0)
                                                    <small class="text-danger">
                                                        <i class="bi bi-exclamation-triangle"></i> Out of stock
                                                    </small>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            @if($prescription->status === 'pending' || $prescription->status === 'active')
                            <div class="d-flex justify-content-end mt-3">
                                @if($paymentBlocked ?? false)
                                <button type="button" class="btn btn-secondary" disabled title="Full payment required before dispensing">
                                    <i class="bi bi-lock"></i> Start Dispensing (Payment Required)
                                </button>
                                @else
                                <a href="{{ route('pharmacy.prescriptions.show', $prescription) }}?dispense=true" 
                                   class="btn btn-success">
                                    <i class="bi bi-capsule-pill"></i> Start Dispensing
                                </a>
                                @endif
                            </div>
                            @endif
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <h5 class="mt-3 text-muted">No medications prescribed</h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Prescription Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">Cancel Prescription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('pharmacy.prescriptions.cancel', $prescription) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action cannot be undone. Please provide a reason for cancelling this prescription.
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Cancellation <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" 
                                  placeholder="Please provide a detailed reason for cancelling this prescription..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Cancel Prescription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Prescription Statistics -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Prescription Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-primary">{{ $prescription->orders->count() }}</h4>
                            <p class="text-muted mb-0">Total Medications</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-success">{{ $prescription->orders->where('status', 'dispensed')->count() }}</h4>
                            <p class="text-muted mb-0">Dispensed</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-warning">{{ $prescription->orders->where('status', 'pending')->count() }}</h4>
                            <p class="text-muted mb-0">Pending</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-info">{{ $prescription->orders->sum('quantity') }}</h4>
                            <p class="text-muted mb-0">Total Quantity</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Billing Modal -->
<div class="modal fade" id="billingModal" tabindex="-1" aria-labelledby="billingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="billingModalLabel">Generate Prescription Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('pharmacy.prescriptions.generate-billing', $prescription) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="billing_type" class="form-label">Billing Type</label>
                        <select name="billing_type" id="billing_type" class="form-select" required onchange="toggleInsuranceOptions()">
                            <option value="">Select billing type</option>
                            <option value="cash">Cash Payment</option>
                            <option value="insurance">Insurance Coverage</option>
                        </select>
                    </div>
                    
                    <div id="insurance_options" style="display: none;">
                        <div class="mb-3">
                            <label for="insurance_policy_id" class="form-label">Insurance Policy</label>
                            <select name="insurance_policy_id" id="insurance_policy_id" class="form-select">
                                <option value="">Select insurance policy</option>
                                @php
                                    $patientPolicies = \App\Models\InsurancePolicy::where('patient_id', $prescription->patient_id)
                                        ->where('is_active', true)
                                        ->with('insuranceProvider')
                                        ->get();
                                @endphp
                                @foreach($patientPolicies as $policy)
                                    <option value="{{ $policy->id }}">
                                        {{ $policy->insuranceProvider->name }} - {{ $policy->policy_number }}
                                        (Expires: {{ $policy->end_date->format('M d, Y') }})
                                    </option>
                                @endforeach
                            </select>
                            @if($patientPolicies->isEmpty())
                                <div class="alert alert-warning mt-2">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    No active insurance policies found for this patient.
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Total Prescription Value:</strong> GH₵{{ number_format($prescription->getTotalValue(), 2) }}
                        <br><small class="text-muted">
                            Insurance billing will calculate coverage and co-pay amounts automatically.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-receipt"></i> Generate Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleInsuranceOptions() {
    const billingType = document.getElementById('billing_type').value;
    const insuranceOptions = document.getElementById('insurance_options');
    const insurancePolicy = document.getElementById('insurance_policy_id');
    
    if (billingType === 'insurance') {
        insuranceOptions.style.display = 'block';
        insurancePolicy.required = true;
    } else {
        insuranceOptions.style.display = 'none';
        insurancePolicy.required = false;
    }
}
</script>
@endsection
