@extends('layouts.app')

@section('title', 'Outstanding Invoices')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Outstanding Invoices</h1>
            <p class="text-secondary mb-0">
                @if($debtor->patient)
                    {{ $debtor->patient->first_name }} {{ $debtor->patient->last_name }} - {{ $debtor->patient->patient_number }}
                @else
                    <span class="text-muted">Unknown Patient</span> - {{ $debtor->patient_number_display }}
                @endif
            </p>
        </div>
        <div>
            <a href="{{ route('debtors.show', $debtor) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Back to Debtor Details
            </a>
            @can('send_payment_reminders')
            <button type="button" class="btn btn-primary" onclick="sendReminder()">
                <i class="bi bi-envelope me-2"></i>
                Send Reminder
            </button>
            @endcan
        </div>
    </div>

    <!-- Debtor Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-label">Total Outstanding</div>
                <div class="stat-value">₵{{ number_format($debtor->total_outstanding, 2) }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-file-text"></i>
                </div>
                <div class="stat-label">Outstanding Invoices</div>
                <div class="stat-value">{{ $outstandingInvoices->total() }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="stat-label">Overdue Invoices</div>
                <div class="stat-value">{{ $overdueInvoices->count() }}</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-calendar-x"></i>
                </div>
                <div class="stat-label">Days Overdue</div>
                <div class="stat-value">{{ $debtor->days_overdue }}</div>
            </div>
        </div>
    </div>

    <!-- Outstanding Invoices Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0 text-dark">
                <i class="bi bi-file-text me-2"></i>
                Outstanding Invoices
            </h5>
        </div>
        <div class="card-body">
            @if($outstandingInvoices->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Days Outstanding</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($outstandingInvoices as $invoice)
                        <tr class="{{ $invoice->isOverdue() ? 'table-warning' : '' }}">
                            <td><strong class="text-primary">{{ $invoice->invoice_number }}</strong></td>
                            <td>{{ $invoice->invoice_date ? $invoice->invoice_date->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                {{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'N/A' }}
                                @if($invoice->isOverdue())
                                    <span class="badge bg-danger ms-2">Overdue</span>
                                @endif
                            </td>
                            <td>₵{{ number_format($invoice->total_amount, 2) }}</td>
                            <td>₵{{ number_format($invoice->getTotalPaid(), 2) }}</td>
                            <td><strong class="text-danger">₵{{ number_format($invoice->getRemainingBalance(), 2) }}</strong></td>
                            <td>
                                @if($invoice->status == 'paid')
                                    <span class="badge bg-success">{{ ucfirst($invoice->status) }}</span>
                                @elseif($invoice->status == 'partial')
                                    <span class="badge bg-warning">{{ ucfirst($invoice->status) }}</span>
                                @elseif($invoice->status == 'overdue')
                                    <span class="badge bg-danger">{{ ucfirst($invoice->status) }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($invoice->isOverdue() && $invoice->due_date)
                                    <span class="text-danger fw-bold">
                                        {{ now()->diffInDays($invoice->due_date) }} days
                                    </span>
                                @elseif($invoice->created_at)
                                    <span class="text-muted">
                                        {{ $invoice->created_at->diffInDays(now()) }} days
                                    </span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('billing.show', $invoice) }}" class="btn btn-outline-primary" title="View Invoice">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('create_payments')
                                    <button type="button" class="btn btn-outline-success" onclick="recordPayment({{ $invoice->id }})" title="Record Payment">
                                        <i class="bi bi-cash-coin"></i>
                                    </button>
                                    @endcan
                                    <a href="{{ route('billing.download', $invoice) }}" class="btn btn-outline-secondary" target="_blank" title="Download PDF">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="5" class="text-end"><strong>Total Outstanding:</strong></td>
                            <td><strong class="text-danger">₵{{ number_format($outstandingInvoices->sum(fn($inv) => $inv->getRemainingBalance()), 2) }}</strong></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Pagination -->
            @if($outstandingInvoices->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $outstandingInvoices->links() }}
            </div>
            @endif
            @else
            <div class="text-center py-5">
                <div class="text-muted">
                    <i class="bi bi-check-circle" style="font-size: 3rem; color: #28a745;"></i>
                    <h5 class="mt-3">No Outstanding Invoices</h5>
                    <p class="mb-0">This debtor has no outstanding invoices at the moment.</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Overdue Invoices Section -->
    @if($overdueInvoices->count() > 0)
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Overdue Invoices ({{ $overdueInvoices->count() }})
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Attention:</strong> These invoices are past their due date and require immediate action.
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Days Overdue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overdueInvoices as $invoice)
                        <tr class="table-danger">
                            <td><strong class="text-primary">{{ $invoice->invoice_number }}</strong></td>
                            <td>{{ $invoice->invoice_date ? $invoice->invoice_date->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'N/A' }}</td>
                            <td>₵{{ number_format($invoice->total_amount, 2) }}</td>
                            <td>₵{{ number_format($invoice->getTotalPaid(), 2) }}</td>
                            <td><strong class="text-danger">₵{{ number_format($invoice->getRemainingBalance(), 2) }}</strong></td>
                            <td>
                                <span class="badge bg-danger">
                                    {{ $invoice->due_date ? now()->diffInDays($invoice->due_date) . ' days' : 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('billing.show', $invoice) }}" class="btn btn-outline-primary" title="View Invoice">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('create_payments')
                                    <button type="button" class="btn btn-outline-success" onclick="recordPayment({{ $invoice->id }})" title="Record Payment">
                                        <i class="bi bi-cash-coin"></i>
                                    </button>
                                    @endcan
                                    <a href="{{ route('billing.download', $invoice) }}" class="btn btn-outline-secondary" target="_blank" title="Download PDF">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="5" class="text-end"><strong>Total Overdue:</strong></td>
                            <td><strong class="text-danger">₵{{ number_format($overdueInvoices->sum(fn($inv) => $inv->getRemainingBalance()), 2) }}</strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="paymentForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                    </div>
                    @include('partials.payment-method-fields', ['idPrefix' => 'debtor', 'showPaystack' => true])
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function recordPayment(invoiceId) {
    const form = document.getElementById('paymentForm');
    form.action = `{{ url('billing') }}/${invoiceId}/record-payment`;
    const pmRoot = document.querySelector('#recordPaymentModal .payment-method-fields');
    if (pmRoot) {
        const amountInput = document.getElementById('amount');
        pmRoot.dataset.amountDue = amountInput?.value || '0';
        amountInput?.addEventListener('input', () => { pmRoot.dataset.amountDue = amountInput.value || '0'; });
    }
    const modal = new bootstrap.Modal(document.getElementById('recordPaymentModal'));
    modal.show();
}

document.getElementById('paymentForm')?.addEventListener('submit', function (e) {
    const pmRoot = document.querySelector('#recordPaymentModal .payment-method-fields');
    const amount = parseFloat(document.getElementById('amount')?.value || '0');
    if (pmRoot && !pmRoot._validatePaymentMethod(amount)) {
        e.preventDefault();
        return;
    }
    if (pmRoot) {
        const extras = pmRoot._getPaymentExtras();
        ['momo_phone', 'momo_network', 'momo_reference', 'amount_tendered'].forEach((field) => {
            let input = this.querySelector(`[name="${field}"]`);
            if (!input && extras[field] !== undefined) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = field;
                this.appendChild(input);
            }
            if (input && extras[field] !== undefined) {
                input.value = extras[field];
            }
        });
        if (extras.momo_reference) {
            let ref = this.querySelector('[name="reference_number"]');
            if (!ref) {
                ref = document.createElement('input');
                ref.type = 'hidden';
                ref.name = 'reference_number';
                this.appendChild(ref);
            }
            ref.value = extras.momo_reference;
        }
    }
});

function sendReminder() {
    if (confirm('Are you sure you want to send a payment reminder to this debtor?')) {
        fetch(`/debtors/{{ $debtor->id }}/send-reminder`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Payment reminder sent successfully!');
            } else {
                alert('Failed to send payment reminder: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while sending payment reminder');
        });
    }
}
</script>
@endpush

