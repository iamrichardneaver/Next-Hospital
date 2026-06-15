@extends('layouts.app')

@section('title', 'Invoice Details - ' . $billing->invoice_number)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1" style="color: #1e3a5f;">Invoice Details</h1><p class="text-secondary mb-0">View invoice information</p></div>
        <div>
            <a href="{{ route('billing.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            <button class="btn btn-info" onclick="printInvoice()"><i class="bi bi-printer"></i> Print</button>
            @can('edit_invoices')
            <a href="{{ route('billing.edit', $billing) }}" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>
            @endcan
            @can('delete_invoices')
            <button class="btn btn-danger" onclick="deleteInvoice({{ $billing->id }})"><i class="bi bi-trash"></i> Delete</button>
            @endcan
        </div>
    </div>

    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Invoice Information</h5>
                            <p class="mb-1"><strong>Invoice #:</strong> {{ $billing->invoice_number }}</p>
                            <p class="mb-1"><strong>Date:</strong> {{ $billing->invoice_date->format('M d, Y') }}</p>
                            @if($billing->due_date)
                            <p class="mb-1"><strong>Due Date:</strong> {{ $billing->due_date->format('M d, Y') }}</p>
                            @endif
                            <p class="mb-1"><strong>Status:</strong> 
                                <span class="badge {{ $billing->getStatusBadgeClass() }}">
                                    {{ ucfirst($billing->status) }}
                                </span>
                            </p>
                            @if($billing->payment_method)
                            <p class="mb-1"><strong>Payment Method:</strong> {{ \App\Enums\PaymentMethod::labelFor($billing->payment_method) }}</p>
                            @endif
                        </div>
                        <div class="col-md-6 text-end">
                            <h5>Patient Information</h5>
                            @if($billing->patient)
                                <p class="mb-1"><strong>Name:</strong> {{ $billing->patient->first_name }} {{ $billing->patient->last_name }}</p>
                                <p class="mb-1"><strong>ID:</strong> {{ $billing->patient->patient_number ?? 'N/A' }}</p>
                                @if($billing->patient->phone)
                                <p class="mb-1"><strong>Phone:</strong> {{ $billing->patient->phone }}</p>
                                @endif
                            @else
                                <p class="mb-1 text-danger"><strong>Patient Not Found</strong></p>
                                <p class="mb-1"><strong>Patient ID:</strong> {{ $billing->patient_id ?? 'N/A' }}</p>
                            @endif
                            @if($billing->branch)
                            <p class="mb-1"><strong>Branch:</strong> {{ $billing->branch->name }}</p>
                            @endif
                        </div>
                    </div>

                    @php
                        $items = is_string($billing->items) ? json_decode($billing->items, true) : $billing->items;
                        $items = $items ?: [];
                        
                        // Recalculate totals to ensure accuracy
                        $calculatedSubtotal = 0;
                        foreach ($items as &$item) {
                            // Calculate correct total: quantity * unit_price
                            $quantity = floatval($item['quantity'] ?? 1);
                            $unitPrice = floatval($item['unit_price'] ?? 0);
                            $item['total'] = $quantity * $unitPrice;
                            $calculatedSubtotal += $item['total'];
                        }
                    @endphp
                    @if(is_array($items) && count($items) > 0)
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr><th>Description</th><th width="100">Qty</th><th width="150">Unit Price</th><th width="150">Total</th></tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            <tr>
                                <td>{{ $item['description'] ?? 'N/A' }}</td>
                                <td>{{ $item['quantity'] ?? 1 }}</td>
                                <td>GH₵ {{ number_format($item['unit_price'] ?? 0, 2) }}</td>
                                <td>GH₵ {{ number_format($item['total'] ?? 0, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No items found for this invoice.
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6 offset-md-6">
                            <table class="table">
                                <tr><th>Subtotal:</th><td class="text-end">GH₵ {{ number_format($calculatedSubtotal, 2) }}</td></tr>
                                @if($billing->tax_amount > 0)
                                <tr><th>Tax:</th><td class="text-end">GH₵ {{ number_format($billing->tax_amount, 2) }}</td></tr>
                                @endif
                                @if($billing->discount_amount > 0)
                                <tr><th>Discount:</th><td class="text-end text-success">-GH₵ {{ number_format($billing->discount_amount, 2) }}</td></tr>
                                @endif
                                @php
                                    $calculatedTotal = $calculatedSubtotal + $billing->tax_amount - $billing->discount_amount;
                                @endphp
                                <tr class="table-primary"><th>Total:</th><th class="text-end">GH₵ {{ number_format($calculatedTotal, 2) }}</th></tr>
                            </table>
                        </div>
                    </div>

                    @if($billing->notes)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6>Notes:</h6>
                            <p class="text-muted">{{ $billing->notes }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Format Modal -->
<div class="modal fade" id="printModal" tabindex="-1" aria-labelledby="printModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printModalLabel">Print Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="printFormat" class="form-label">Print Format</label>
                    <select class="form-select" id="printFormat">
                        <option value="a4">A4 Paper (Standard)</option>
                        <option value="thermal_80mm">Thermal 80mm (Receipt)</option>
                        <option value="thermal_58mm">Thermal 58mm (Small Receipt)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="printType" class="form-label">Document Type</label>
                    <select class="form-select" id="printType">
                        <option value="invoice">Invoice</option>
                        <option value="receipt">Receipt</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="executePrint()">Print</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Delete Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this invoice? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Warning:</strong> Deleting this invoice will also remove all associated payment records.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Invoice</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentInvoiceId = {{ $billing->id }};

function printInvoice() {
    const printModal = new bootstrap.Modal(document.getElementById('printModal'));
    printModal.show();
}

function executePrint() {
    const format = document.getElementById('printFormat').value;
    const type = document.getElementById('printType').value;
    
    const printUrl = `{{ route('billing.print', ':id') }}`.replace(':id', currentInvoiceId) + 
                     `?format=${format}&type=${type}`;
    
    // Open in new window for printing
    const printWindow = window.open(printUrl, '_blank');
    
    if (printWindow) {
        printWindow.onload = function() {
            printWindow.print();
        };
    }
    
    // Close modal
    const printModal = bootstrap.Modal.getInstance(document.getElementById('printModal'));
    printModal.hide();
}

function deleteInvoice(invoiceId) {
    currentInvoiceId = invoiceId;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `{{ route('billing.index') }}/${currentInvoiceId}`;
    
    const methodField = document.createElement('input');
    methodField.type = 'hidden';
    methodField.name = '_method';
    methodField.value = 'DELETE';
    
    const tokenField = document.createElement('input');
    tokenField.type = 'hidden';
    tokenField.name = '_token';
    tokenField.value = '{{ csrf_token() }}';
    
    form.appendChild(methodField);
    form.appendChild(tokenField);
    document.body.appendChild(form);
    
    form.submit();
});
</script>
@endpush
