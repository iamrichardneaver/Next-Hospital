@extends('layouts.app')

@section('title', 'Cashier Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-cash-coin"></i> Cashier Dashboard
            </h1>
            <p class="text-secondary mb-0">Centralized Payment Processing Center</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" onclick="refreshDashboard()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <button class="btn btn-outline-info" onclick="viewPaymentHistory()">
                <i class="bi bi-clock-history"></i> Payment History
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-label">Patients Served Today</div>
                <div class="stat-value">{{ number_format($statistics['total_patients_served']) }}</div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-label">Pending Payments</div>
                <div class="stat-value">{{ number_format($statistics['pending_payments']) }}</div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-label">Collected Today</div>
                <div class="stat-value">GH₵{{ number_format($statistics['total_collected'], 2) }}</div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-calendar-month"></i>
                </div>
                <div class="stat-label">Monthly Revenue</div>
                <div class="stat-value">GH₵{{ number_format($statistics['monthly_revenue'], 2) }}</div>
                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                    <i class="bi bi-info-circle"></i> {{ now()->format('F Y') }}
                </small>
            </div>
        </div>
    </div>
    
    <!-- Secondary Stats Row -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 text-danger">
                                <i class="bi bi-exclamation-triangle-fill"></i> Outstanding Amount
                            </h6>
                            <p class="text-muted mb-0 small">Total unpaid bills across all patients</p>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-0 text-danger">GH₵{{ number_format($statistics['outstanding_amount'], 2) }}</h3>
                            <button class="btn btn-sm btn-outline-danger mt-2" onclick="viewOutstandingDebts()">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Patient Search & Payment Processing -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-search"></i> Patient Search & Payment Processing
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Patient Search -->
                    <div class="mb-4">
                        <label for="patientSearch" class="form-label">Search Patient</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   id="patientSearch" 
                                   placeholder="Search by Patient ID, Name, Phone, or NHIS Number..."
                                   autocomplete="off">
                    <button class="btn btn-primary" type="button" onclick="searchPatient()">
                        <i class="bi bi-search"></i> Search
                    </button>
                        </div>
                        <div id="searchResults" class="mt-3" style="display: none;">
                            <!-- Search results will be populated here -->
                        </div>
                    </div>

                    <!-- Selected Patient Info -->
                    <div id="selectedPatientInfo" class="mb-4" style="display: none;">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Selected Patient</h6>
                            </div>
                            <div class="card-body">
                                <div id="patientDetails">
                                    <!-- Patient details will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Charges -->
                    <div id="pendingChargesSection" style="display: none;">
                        <h6 class="mb-3">Pending Charges</h6>
                        <div id="pendingChargesList">
                            <!-- Pending charges will be populated here -->
                        </div>
                        
                        <div id="invoicePaymentSection" class="mt-4" style="display: none;">
                            <h6 class="mb-3">Outstanding Invoices</h6>
                            <div id="unpaidInvoicesList"></div>
                        </div>

                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Payment Summary</h6>
                                <div>
                                    <strong>Total Amount: <span id="totalAmount">GH₵0.00</span></strong>
                                    <div id="balanceInfo" class="small text-muted" style="display: none;"></div>
                                </div>
                            </div>
                            <div id="paymentPolicyAlert" class="alert alert-info py-2 small" style="display: none;"></div>
                            
                            <form id="paymentForm">
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        @include('partials.payment-method-fields', ['idPrefix' => 'cashier', 'showPaystack' => true])
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="paymentNotes" class="form-label">Notes (Optional)</label>
                                        <input type="text" class="form-control" id="paymentNotes" name="notes" placeholder="Payment notes...">
                                    </div>
                                </div>
                                
                                <div id="partialPaymentRow" class="row" style="display: none;">
                                    <div class="col-md-6 mb-3">
                                        <label for="partialPaymentAmount" class="form-label">Payment Amount (partial allowed for IPD)</label>
                                        <input type="number" class="form-control" id="partialPaymentAmount" min="0.01" step="0.01" placeholder="Enter amount to pay">
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-success" id="processPaymentBtn" onclick="processPayment()">
                                        <i class="bi bi-credit-card"></i> Process Payment
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="clearSelection()">
                                        <i class="bi bi-x-circle"></i> Clear Selection
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Payments & Quick Actions -->
        <div class="col-lg-4">
            <!-- Recent Payments -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0 text-dark">
                        <i class="bi bi-clock-history"></i> Recent Payments
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentPayments as $payment)
                        @php
                            $patientName = $payment->patient?->full_name
                                ?? $payment->invoice?->patient?->full_name
                                ?? 'Unknown Patient';
                            $invoiceNumber = $payment->invoice?->invoice_number ?? 'N/A';
                        @endphp
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">{{ $patientName }}</h6>
                                    <p class="mb-1 text-muted small">{{ $invoiceNumber }}</p>
                                    <small class="text-muted">{{ $payment->created_at->format('M d, Y H:i') }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success">GH₵{{ number_format($payment->amount, 2) }}</span>
                                    <br>
                                    <small class="text-muted">{{ \App\Enums\PaymentMethod::labelFor($payment->payment_method) }}</small>
                                    <br>
                                    <a href="{{ route('cashier.generate-receipt', $payment->id) }}" class="small" target="_blank" title="View receipt">
                                        <i class="bi bi-receipt"></i> Receipt
                                    </a>
                                    @if($payment->status === 'completed')
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-2" data-bs-toggle="modal" data-bs-target="#refundModal{{ $payment->id }}">
                                        Refund
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if($payment->status === 'completed')
                        <div class="modal fade" id="refundModal{{ $payment->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('cashier.refund-payment', $payment) }}" method="POST">
                                        @csrf
                                        <div class="modal-header"><h5 class="modal-title">Refund Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <p>Refund payment of <strong>GH₵{{ number_format($payment->amount, 2) }}</strong> for {{ $patientName }}?</p>
                                            <div class="mb-3">
                                                <label class="form-label">Refund Amount (leave blank for full)</label>
                                                <input type="number" step="0.01" max="{{ $payment->amount }}" name="amount" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Reason</label>
                                                <textarea name="reason" class="form-control" rows="2" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Process Refund</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif
                        @empty
                        <div class="list-group-item text-center py-4">
                            <i class="bi bi-receipt text-secondary" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2 mb-0">No recent payments</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0 text-dark">
                        <i class="bi bi-lightning"></i> Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="viewAllPendingPayments()">
                            <i class="bi bi-list-check"></i> View All Pending Payments
                        </button>
                        <button class="btn btn-outline-info" onclick="generateDailyReport()">
                            <i class="bi bi-file-text"></i> Generate Daily Report
                        </button>
                        <button class="btn btn-outline-warning" onclick="viewOutstandingDebts()">
                            <i class="bi bi-exclamation-triangle"></i> Outstanding Debts
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Success Modal -->
<div class="modal fade" id="paymentSuccessModal" tabindex="-1" aria-labelledby="paymentSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="paymentSuccessModalLabel">
                    <i class="bi bi-check-circle"></i> Payment Successful
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Payment Processed Successfully!</h5>
                    <p class="text-muted">Invoice #: <span id="successInvoiceNumber"></span></p>
                    <p class="text-muted">Amount: <span id="successAmount"></span></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printReceipt()">
                    <i class="bi bi-printer"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment History Modal -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1" aria-labelledby="paymentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentHistoryModalLabel">
                    <i class="bi bi-clock-history"></i> Payment History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="paymentHistoryFilters" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small mb-0">From</label>
                        <input type="date" class="form-control form-control-sm" id="historyStartDate">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">To</label>
                        <input type="date" class="form-control form-control-sm" id="historyEndDate">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Method</label>
                        <select class="form-select form-select-sm" id="historyPaymentMethod">
                            <option value="">All methods</option>
                            <option value="cash">Cash</option>
                            <option value="paystack">Paystack</option>
                            <option value="mobile_money_offline">Mobile Money (Offline)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Patient</label>
                        <input type="text" class="form-control form-control-sm" id="historyPatientSearch" placeholder="Name, ID, phone...">
                    </div>
                    @if(isset($branches) && $branches->count() > 1)
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Branch</label>
                        <select class="form-select form-select-sm" id="historyBranchId">
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ ($branchId ?? null) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-12 d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-primary" onclick="loadPaymentHistory(1)">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetPaymentHistoryFilters()">
                            Reset
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="exportPaymentHistory()">
                            <i class="bi bi-download"></i> Export CSV
                        </button>
                    </div>
                </div>
                <div id="paymentHistoryTotals" class="mb-3" style="display: none;"></div>
                <div id="paymentHistoryContent">
                    <div class="text-center">
                        <i class="bi bi-hourglass-split"></i> Loading payment history...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Payments Modal -->
<div class="modal fade" id="pendingPaymentsModal" tabindex="-1" aria-labelledby="pendingPaymentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pendingPaymentsModalLabel">
                    <i class="bi bi-list-check"></i> All Pending Payments
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="pendingPaymentsContent">
                    <div class="text-center">
                        <i class="bi bi-hourglass-split"></i> Loading pending payments...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daily Report Modal -->
<div class="modal fade" id="dailyReportModal" tabindex="-1" aria-labelledby="dailyReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dailyReportModalLabel">
                    <i class="bi bi-file-text"></i> Daily Report
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="reportDate" class="form-label">
                        <i class="bi bi-calendar-date"></i> Select Date
                        <small class="text-muted">(Report will update automatically when date changes)</small>
                    </label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="reportDate" value="{{ now()->toDateString() }}">
                        <button class="btn btn-outline-secondary" type="button" onclick="setToday()">
                            <i class="bi bi-calendar-check"></i> Today
                        </button>
                    </div>
                </div>
                <div id="dailyReportContent">
                    <div class="text-center">
                        <i class="bi bi-hourglass-split"></i> Loading daily report...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="loadDailyReport()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh Report
                </button>
                <button type="button" class="btn btn-success" onclick="exportDailyReport()">
                    <i class="bi bi-download"></i> Export PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Outstanding Debts Modal -->
<div class="modal fade" id="outstandingDebtsModal" tabindex="-1" aria-labelledby="outstandingDebtsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="outstandingDebtsModalLabel">
                    <i class="bi bi-exclamation-triangle"></i> Outstanding Debts
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="outstandingDebtsContent">
                    <div class="text-center">
                        <i class="bi bi-hourglass-split"></i> Loading outstanding debts...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let selectedPatient = null;
let currentPaymentPolicy = null;
let currentPaymentSummary = null;
let unpaidInvoices = [];
let selectedCharges = [];
let loadedPendingCharges = [];
let currentPaymentId = null;
let searchTimeout = null;
let currentSearchRequest = null;

// Dynamic search with debouncing - triggered as user types
function handleSearchInput() {
    const searchInput = document.getElementById('patientSearch');
    const searchTerm = searchInput.value.trim();
    const searchResults = document.getElementById('searchResults');
    
    // Cancel previous timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    // Cancel previous request if still running
    if (currentSearchRequest) {
        currentSearchRequest.abort();
        currentSearchRequest = null;
    }
    
    // Hide results if search is cleared or too short
    if (searchTerm.length === 0) {
        searchResults.style.display = 'none';
        searchResults.innerHTML = '';
        return;
    }
    
    if (searchTerm.length < 2) {
        searchResults.style.display = 'block';
        searchResults.innerHTML = '<div class="alert alert-info"><i class="bi bi-info-circle"></i> Type at least 2 characters to search...</div>';
        return;
    }
    
    // Show loading immediately
    searchResults.style.display = 'block';
    searchResults.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> <span class="ms-2">Searching...</span></div>';
    
    // Debounce: wait 500ms after user stops typing
    searchTimeout = setTimeout(() => {
        performSearch(searchTerm);
    }, 500);
}

// Perform the actual search
function performSearch(searchTerm) {
    const searchResults = document.getElementById('searchResults');
    
    // Show loading state
    searchResults.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> <span class="ms-2">Searching for patients...</span></div>';
    
    // Create AbortController for this request
    const controller = new AbortController();
    currentSearchRequest = controller;
    
    fetch(`{{ route('cashier.search-patient') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ search_term: searchTerm }),
        signal: controller.signal
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            throw new Error('Server returned non-JSON response. Please check your session.');
        }
    })
    .then(data => {
        currentSearchRequest = null;
        
        // Validate response structure
        if (!data || typeof data !== 'object') {
            throw new Error('Invalid response structure from server');
        }
        
        if (data.success === true) {
            if (data.patients && Array.isArray(data.patients)) {
                displaySearchResults(data.patients);
            } else {
                searchResults.innerHTML = '<div class="alert alert-info"><i class="bi bi-inbox"></i> No patients found matching your search.</div>';
            }
        } else {
            const errorMsg = data.message || 'Search failed. Please try again.';
            searchResults.innerHTML = `<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> ${errorMsg}</div>`;
        }
    })
    .catch(error => {
        currentSearchRequest = null;
        
        // Ignore aborted requests
        if (error.name === 'AbortError') {
            return;
        }
        
        console.error('Search error:', error);
        
        let errorMessage = 'Search failed. Please try again.';
        
        if (error.message.includes('session') || error.message.includes('401')) {
            errorMessage = 'Session expired. Please refresh the page and log in again.';
        } else if (error.message.includes('403')) {
            errorMessage = 'You do not have permission to perform this action.';
        } else if (error.message.includes('Network')) {
            errorMessage = 'Network error. Please check your connection.';
        }
        
        searchResults.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> ${errorMessage}</div>`;
    });
}

// Legacy function for button click (now just calls handleSearchInput)
function searchPatient() {
    handleSearchInput();
}

// Display search results
function displaySearchResults(patients) {
    const searchResults = document.getElementById('searchResults');
    
    if (!searchResults) {
        return;
    }
    
    try {
        // Validate patients data
        if (!patients || !Array.isArray(patients)) {
            searchResults.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Invalid patient data received.</div>';
            return;
        }
        
        if (patients.length === 0) {
            searchResults.innerHTML = '<div class="alert alert-info"><i class="bi bi-inbox"></i> No patients found matching your search.</div>';
            return;
        }
        
        // Build results list
        let html = '<div class="list-group shadow-sm">';
        html += '<div class="list-group-item bg-light"><small class="text-muted"><i class="bi bi-info-circle"></i> Click on a patient to view their charges and process payment</small></div>';
        
        patients.forEach((patient) => {
            // Safely access patient properties with defaults
            const patientId = patient?.id || 0;
            const patientName = patient?.name || 'Unknown';
            const patientNumber = patient?.patient_number || 'N/A';
            const patientPhone = patient?.phone || 'N/A';
            const lastVisit = patient?.last_visit || 'N/A';
            const hasDebt = patient?.debt_info?.has_debt || false;
            const debtAmount = patient?.debt_info?.total_outstanding || 0;
            
            // Build debt badge if applicable
            let debtBadge = '';
            if (hasDebt && debtAmount > 0) {
                debtBadge = `<span class="badge bg-danger ms-2" title="Outstanding Balance">GH₵ ${parseFloat(debtAmount).toFixed(2)}</span>`;
            }
            
            html += `
                <div class="list-group-item list-group-item-action" onclick="selectPatient(${patientId})" style="cursor: pointer;" title="Click to select ${patientName}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <i class="bi bi-person-circle text-primary"></i> ${patientName}
                                ${debtBadge}
                            </h6>
                            <p class="mb-1 text-muted small">
                                <i class="bi bi-credit-card-2-front"></i> ID: <strong>${patientNumber}</strong>
                            </p>
                            <small class="text-muted">
                                <i class="bi bi-telephone"></i> ${patientPhone}
                            </small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">
                                <i class="bi bi-clock-history"></i> Last Visit:<br>
                                <strong>${lastVisit}</strong>
                            </small>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        html += `<div class="text-center mt-2"><small class="text-muted">${patients.length} patient${patients.length !== 1 ? 's' : ''} found</small></div>`;
        
        searchResults.innerHTML = html;
        
    } catch (error) {
        console.error('Error displaying search results:', error);
        searchResults.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Error displaying results. Please try again.</div>';
    }
}

// Select patient
function selectPatient(patientId) {
    // Validate patient ID
    if (!patientId || isNaN(patientId)) {
        console.error('Invalid patient ID:', patientId);
        alert('Invalid patient ID. Please try searching again.');
        return;
    }
    
    // Hide search results when patient is selected
    const searchResults = document.getElementById('searchResults');
    searchResults.style.display = 'none';
    
    // Show loading
    const patientInfo = document.getElementById('selectedPatientInfo');
    const pendingCharges = document.getElementById('pendingChargesSection');
    patientInfo.style.display = 'block';
    pendingCharges.style.display = 'block';
    
    document.getElementById('patientDetails').innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> <span class="ms-2">Loading patient details...</span></div>';
    document.getElementById('pendingChargesList').innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> <span class="ms-2">Loading pending charges...</span></div>';
    
    // Construct URL properly
    const baseUrl = '{{ url("cashier/patient") }}';
    const url = `${baseUrl}/${patientId}/charges`;
    console.log('Making request to:', url);
    console.log('Patient ID:', patientId);
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Get patient charges response status:', response.status);
        console.log('Response content-type:', response.headers.get('content-type'));
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            console.error('Non-JSON response received');
            throw new Error('Server returned non-JSON response. You may need to log in again.');
        }
    })
    .then(data => {
        console.log('Patient charges data:', data);
        
        if (data.success) {
            selectedPatient = data.patient;
            currentPaymentPolicy = data.payment_policy || null;
            currentPaymentSummary = data.payment_summary || null;
            unpaidInvoices = data.unpaid_invoices || [];
            displayPatientDetails(data.patient);
            displayUnpaidInvoices(unpaidInvoices);
            updatePaymentPolicyUI();
            
            // Handle pending charges - ensure it's always an array
            loadedPendingCharges = data.pending_charges || [];
            console.log('Pending charges:', loadedPendingCharges, 'Length:', loadedPendingCharges.length);
            displayPendingCharges(loadedPendingCharges);
        } else {
            const errorMsg = data.message || 'Failed to load patient details.';
            document.getElementById('patientDetails').innerHTML = `<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> ${errorMsg}</div>`;
            document.getElementById('pendingChargesList').innerHTML = '';
        }
    })
    .catch(error => {
        console.error('Error loading patient charges:', error);
        
        let errorMessage = 'Failed to load patient details. Please try again.';
        
        if (error.message.includes('401')) {
            errorMessage = 'Your session has expired. Please refresh the page and log in again.';
        } else if (error.message.includes('403')) {
            errorMessage = 'You do not have permission to view this patient\'s details.';
        } else if (error.message.includes('404')) {
            errorMessage = 'Patient not found. Please verify the patient ID and try again.';
        } else if (error.message.includes('500')) {
            errorMessage = 'Server error occurred. Please try again or contact support.';
        }
        
        document.getElementById('patientDetails').innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> ${errorMessage}</div>`;
        document.getElementById('pendingChargesList').innerHTML = '';
    });
}

// Display patient details
function displayPatientDetails(patient) {
    const contextBadge = currentPaymentPolicy?.context === 'IPD'
        ? '<span class="badge bg-success ms-2">IPD — Partial payment allowed</span>'
        : '<span class="badge bg-primary ms-2">OPD — Full payment required</span>';

    const balanceLine = currentPaymentSummary && currentPaymentSummary.amount_due > 0
        ? `<p class="mb-1 text-danger"><strong>Outstanding Balance:</strong> GH₵${parseFloat(currentPaymentSummary.amount_due).toFixed(2)}</p>`
        : '';

    const html = `
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Name:</strong> ${patient.name} ${contextBadge}</p>
                <p class="mb-1"><strong>ID:</strong> ${patient.patient_number}</p>
                <p class="mb-1"><strong>Phone:</strong> ${patient.phone || 'N/A'}</p>
                ${balanceLine}
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Email:</strong> ${patient.email || 'N/A'}</p>
                <p class="mb-1"><strong>DOB:</strong> ${patient.date_of_birth || 'N/A'}</p>
                <p class="mb-1"><strong>Gender:</strong> ${patient.gender || 'N/A'}</p>
            </div>
        </div>
    `;
    document.getElementById('patientDetails').innerHTML = html;
}

function updatePaymentPolicyUI() {
    const alertEl = document.getElementById('paymentPolicyAlert');
    const partialRow = document.getElementById('partialPaymentRow');
    const processBtn = document.getElementById('processPaymentBtn');
    const balanceInfo = document.getElementById('balanceInfo');

    if (!currentPaymentPolicy) {
        alertEl.style.display = 'none';
        partialRow.style.display = 'none';
        return;
    }

    alertEl.style.display = 'block';
    alertEl.className = currentPaymentPolicy.context === 'IPD'
        ? 'alert alert-success py-2 small'
        : 'alert alert-warning py-2 small';
    alertEl.innerHTML = `<i class="bi bi-info-circle"></i> ${currentPaymentPolicy.policy_message}`;

    if (currentPaymentPolicy.allows_partial_payment) {
        partialRow.style.display = 'flex';
        processBtn.innerHTML = '<i class="bi bi-credit-card"></i> Pay Selected Charges';
    } else {
        partialRow.style.display = 'none';
        processBtn.innerHTML = '<i class="bi bi-credit-card"></i> Pay Full Amount';
    }

    if (currentPaymentSummary && currentPaymentSummary.amount_due > 0) {
        balanceInfo.style.display = 'block';
        balanceInfo.textContent = `Running balance: GH₵${parseFloat(currentPaymentSummary.amount_due).toFixed(2)}`;
    } else {
        balanceInfo.style.display = 'none';
    }
}

function displayUnpaidInvoices(invoices) {
    const section = document.getElementById('invoicePaymentSection');
    const list = document.getElementById('unpaidInvoicesList');

    if (!invoices || invoices.length === 0) {
        section.style.display = 'none';
        list.innerHTML = '';
        return;
    }

    section.style.display = 'block';
    let html = '<div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th>Invoice</th><th>Balance</th><th>Action</th></tr></thead><tbody>';

    invoices.forEach(inv => {
        const allowsPartial = currentPaymentPolicy?.allows_partial_payment;
        html += `<tr>
            <td><strong>${inv.invoice_number}</strong><br><small class="text-muted">${inv.payment_status}</small></td>
            <td class="text-danger fw-bold">GH₵${parseFloat(inv.balance_amount).toFixed(2)}</td>
            <td>
                ${allowsPartial ? `<div class="input-group input-group-sm">
                    <input type="number" class="form-control" id="inv-amount-${inv.id}" min="0.01" max="${inv.balance_amount}" step="0.01" value="${inv.balance_amount}" placeholder="Amount">
                    <button class="btn btn-outline-success" onclick="payInvoicePartial(${inv.id})">Pay</button>
                </div><small class="text-muted">Partial or full</small>` :
                `<button class="btn btn-sm btn-success" onclick="payInvoiceFull(${inv.id}, ${inv.balance_amount})">Pay Full GH₵${parseFloat(inv.balance_amount).toFixed(2)}</button>`}
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    list.innerHTML = html;
}

function payInvoiceFull(invoiceId, amount) {
    payInvoicePartial(invoiceId, amount);
}

function getCashierPaymentRoot() {
    return document.querySelector('#pendingChargesSection .payment-method-fields');
}

function payInvoicePartial(invoiceId, fixedAmount) {
    const amount = fixedAmount ?? parseFloat(document.getElementById(`inv-amount-${invoiceId}`)?.value);
    const pmRoot = getCashierPaymentRoot();

    if (!amount || amount <= 0) {
        alert('Enter a valid payment amount');
        return;
    }
    if (!pmRoot || !pmRoot._validatePaymentMethod(amount)) {
        return;
    }
    const extras = pmRoot._getPaymentExtras();
    if (extras.payment_method === 'paystack') {
        alert('Use Process Payment with selected charges for Paystack. For invoice-only payment, choose Cash or Offline MoMo.');
        return;
    }

    fetch(`{{ route('cashier.pay-invoice') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            invoice_id: invoiceId,
            amount: amount,
            payment_method: extras.payment_method,
            notes: document.getElementById('paymentNotes').value,
            amount_tendered: extras.amount_tendered,
            momo_phone: extras.momo_phone,
            momo_network: extras.momo_network,
            momo_reference: extras.momo_reference,
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(`Payment recorded. Balance: GH₵${parseFloat(data.balance_amount).toFixed(2)}`);
            selectPatient(selectedPatient.id);
        } else {
            alert(data.message || 'Payment failed');
        }
    })
    .catch(() => alert('Payment failed'));
}

// Display pending charges
function displayPendingCharges(charges) {
    const pendingChargesList = document.getElementById('pendingChargesList');
    const totalAmountElement = document.getElementById('totalAmount');
    
    // Validate charges is an array
    if (!charges || !Array.isArray(charges)) {
        console.warn('Invalid charges data:', charges);
        charges = [];
    }
    
    if (charges.length === 0) {
        pendingChargesList.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No pending charges found for this patient.
                <br><small class="text-muted">This patient has no unpaid bills at the moment. You can create a manual invoice if needed.</small>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary" onclick="createManualInvoice()">
                    <i class="bi bi-file-earmark-plus"></i> Create Manual Invoice
                </button>
            </div>`;
        if (totalAmountElement) {
            totalAmountElement.textContent = 'GH₵ 0.00';
        }
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-sm table-hover">';
    html += '<thead class="table-light"><tr><th>Service</th><th>Description</th><th>Amount</th><th>Date</th><th><input type="checkbox" id="selectAllCharges" onchange="toggleAllCharges(this)"> Select</th></tr></thead><tbody>';
    
    let totalAmount = 0;
    charges.forEach(charge => {
        totalAmount += parseFloat(charge.amount || 0);
        const chargeDate = charge.date ? new Date(charge.date).toLocaleDateString() : 'N/A';
        const chargeType = charge.type ? charge.type.replace('_', ' ').toUpperCase() : 'CHARGE';
        const lineKey = charge.line_id || `${charge.type}_${charge.id}`;
        const componentLabel = charge.charge_component === 'admin_fee'
            ? '<span class="badge bg-info ms-1">Service Fee</span>'
            : (charge.charge_component === 'module_price' ? '<span class="badge bg-secondary ms-1">Item Price</span>' : '');

        let pricingDetail = '';
        const copay = parseFloat(charge.patient_copay ?? charge.final_amount ?? charge.amount ?? 0);
        const insurance = parseFloat(charge.insurance_coverage ?? 0);
        const discount = parseFloat(charge.discount_amount ?? 0);
        if (insurance > 0 || discount > 0) {
            pricingDetail = '<br><small class="text-muted">';
            if (discount > 0) pricingDetail += `Discount: GH₵${discount.toFixed(2)} · `;
            if (insurance > 0) pricingDetail += `Insurance: GH₵${insurance.toFixed(2)} · `;
            pricingDetail += `Your Portion: GH₵${copay.toFixed(2)}</small>`;
        }
        
        html += `
            <tr>
                <td><span class="badge bg-primary">${chargeType}</span>${componentLabel}</td>
                <td>${charge.description || 'N/A'}${pricingDetail}</td>
                <td><strong>GH₵ ${copay.toFixed(2)}</strong></td>
                <td>${chargeDate}</td>
                <td>
                    <div class="form-check">
                        <input class="form-check-input charge-checkbox" type="checkbox" 
                               value="${lineKey}" 
                               data-charge-id="${charge.id}"
                               data-type="${charge.type || ''}" 
                               data-amount="${charge.amount || 0}"
                               onchange="updateTotal()">
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    pendingChargesList.innerHTML = html;
    
    if (totalAmountElement) {
        totalAmountElement.textContent = `GH₵ ${totalAmount.toFixed(2)}`;
    }
}

// Toggle all charge checkboxes
function toggleAllCharges(checkbox) {
    const checkboxes = document.querySelectorAll('.charge-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateTotal();
}

// Update total when charges are selected
function updateTotal() {
    const checkboxes = document.querySelectorAll('.charge-checkbox:checked');
    let total = 0;
    selectedCharges = [];
    
    checkboxes.forEach(checkbox => {
        const amount = parseFloat(checkbox.dataset.amount);
        const lineKey = checkbox.value;
        const source = loadedPendingCharges.find(c => (c.line_id || `${c.type}_${c.id}`) === lineKey) || {};
        total += amount;
        selectedCharges.push({
            id: source.id || checkbox.dataset.chargeId,
            line_id: lineKey,
            type: source.type || checkbox.dataset.type,
            amount: amount,
            description: source.description || null,
            charge_component: source.charge_component || null,
        });
    });
    
    document.getElementById('totalAmount').textContent = `GH₵${total.toFixed(2)}`;
}

// Process payment
function processPayment() {
    if (!selectedPatient) {
        alert('Please select a patient first');
        return;
    }
    
    if (selectedCharges.length === 0) {
        alert('Please select at least one charge to pay');
        return;
    }
    
    const pmRoot = getCashierPaymentRoot();
    const totalAmount = selectedCharges.reduce((sum, charge) => sum + charge.amount, 0);

    if (!pmRoot || !pmRoot._validatePaymentMethod(totalAmount)) {
        return;
    }

    const extras = pmRoot._getPaymentExtras();
    const paymentMethod = extras.payment_method;
    
    // Validate amount
    if (totalAmount <= 0) {
        alert('Invalid payment amount. Please select valid charges.');
        return;
    }
    
    // Confirmation for large amounts
    if (totalAmount > 1000) {
        const confirmed = confirm(`You are about to process a payment of GH₵${totalAmount.toFixed(2)}. Do you want to continue?`);
        if (!confirmed) {
            return;
        }
    }
    
    const notes = document.getElementById('paymentNotes').value;
    
    // Handle Paystack separately
    if (paymentMethod === 'paystack') {
        processPaystackPayment(totalAmount, notes);
        return;
    }
    
    // Show loading
    const processBtn = document.querySelector('button[onclick="processPayment()"]');
    const originalText = processBtn.innerHTML;
    processBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
    processBtn.disabled = true;
    
    fetch(`{{ route('cashier.process-payment') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            patient_id: selectedPatient.id,
            charges: selectedCharges,
            payment_method: paymentMethod,
            total_amount: totalAmount,
            notes: notes,
            amount_tendered: extras.amount_tendered,
            momo_phone: extras.momo_phone,
            momo_network: extras.momo_network,
            momo_reference: extras.momo_reference,
        })
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            throw new Error('Authentication required or server error');
        }
    })
    .then(data => {
        if (data.success) {
            currentPaymentId = data.payment_id;
            document.getElementById('successInvoiceNumber').textContent = data.invoice_number;
            document.getElementById('successAmount').textContent = `GH₵${totalAmount.toFixed(2)}`;
            
            const modal = new bootstrap.Modal(document.getElementById('paymentSuccessModal'));
            modal.show();
            
            // Clear form
            clearSelection();
        } else {
            const errorMsg = data.message || 'Payment processing failed';
            if (data.payment_policy_violation) {
                alert(errorMsg);
            } else {
                alert(`Payment processing failed: ${errorMsg}`);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (error.message.includes('Authentication required')) {
            alert('Your session has expired. Please refresh the page and log in again.');
        } else if (error.message.includes('Network')) {
            alert('Network error. Please check your connection and try again.');
        } else {
            alert('Payment processing failed. Please try again.');
        }
    })
    .finally(() => {
        processBtn.innerHTML = originalText;
        processBtn.disabled = false;
    });
}

// Clear selection
function clearSelection() {
    selectedPatient = null;
    selectedCharges = [];
    document.getElementById('selectedPatientInfo').style.display = 'none';
    document.getElementById('pendingChargesSection').style.display = 'none';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('patientSearch').value = '';
    document.getElementById('paymentForm').reset();
}

// Print receipt
function printReceipt() {
    console.log('Print receipt called. Current Payment ID:', currentPaymentId);
    
    if (!currentPaymentId) {
        alert('No payment ID available. Please process a payment first.');
        return;
    }
    
    // Use the named route with proper Laravel URL helper
    const receiptUrl = '{{ route("cashier.generate-receipt", ["payment" => ":paymentId"]) }}'.replace(':paymentId', currentPaymentId);
    console.log('Opening receipt URL:', receiptUrl);
    
    window.open(receiptUrl, '_blank');
}

// Other functions
function refreshDashboard() {
    location.reload();
}

function viewPaymentHistory() {
    const modal = new bootstrap.Modal(document.getElementById('paymentHistoryModal'));
    modal.show();
    if (!document.getElementById('historyStartDate').value) {
        resetPaymentHistoryFilters();
    } else {
        loadPaymentHistory(1);
    }
}

function viewAllPendingPayments() {
    // Open pending payments modal
    const modal = new bootstrap.Modal(document.getElementById('pendingPaymentsModal'));
    modal.show();
    loadPendingPayments();
}

function generateDailyReport() {
    // Open daily report modal
    const modal = new bootstrap.Modal(document.getElementById('dailyReportModal'));
    modal.show();
    loadDailyReport();
}

function viewOutstandingDebts() {
    // Open outstanding debts modal
    const modal = new bootstrap.Modal(document.getElementById('outstandingDebtsModal'));
    modal.show();
    loadOutstandingDebts();
}

// Dynamic search as user types
document.getElementById('patientSearch').addEventListener('input', function(e) {
    handleSearchInput();
});

// Also trigger search on Enter key
document.getElementById('patientSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        // Clear timeout and search immediately
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        const searchTerm = this.value.trim();
        if (searchTerm.length >= 2) {
            performSearch(searchTerm);
        }
    }
});

// Auto-refresh daily report when date changes
document.getElementById('reportDate').addEventListener('change', function(e) {
    loadDailyReport();
});

let currentHistoryPage = 1;

function getPaymentHistoryFilters() {
    const filters = {
        start_date: document.getElementById('historyStartDate')?.value || '',
        end_date: document.getElementById('historyEndDate')?.value || '',
        payment_method: document.getElementById('historyPaymentMethod')?.value || '',
        patient_search: document.getElementById('historyPatientSearch')?.value?.trim() || '',
    };
    const branchEl = document.getElementById('historyBranchId');
    if (branchEl && branchEl.value) {
        filters.branch_id = branchEl.value;
    }
    return filters;
}

function buildHistoryQueryString(page = 1) {
    const filters = getPaymentHistoryFilters();
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value) params.set(key, value);
    });
    params.set('page', page);
    return params.toString();
}

function resetPaymentHistoryFilters() {
    document.getElementById('historyStartDate').value = '';
    document.getElementById('historyEndDate').value = '';
    document.getElementById('historyPaymentMethod').value = '';
    document.getElementById('historyPatientSearch').value = '';
    loadPaymentHistory(1);
}

function exportPaymentHistory() {
    const qs = buildHistoryQueryString(1);
    window.open(`{{ route('cashier.history') }}?${qs}&export=csv`, '_blank');
}

// Load payment history
function loadPaymentHistory(page = 1) {
    currentHistoryPage = page;
    document.getElementById('paymentHistoryContent').innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Loading payment history...</div>';

    fetch(`{{ route('cashier.history') }}?${buildHistoryQueryString(page)}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }
        throw new Error('Authentication required or server error');
    })
    .then(data => {
        if (data.success) {
            displayPaymentHistoryTotals(data.totals_by_method || [], data.grand_total || 0);
            displayPaymentHistory(data.payments);
        } else {
            document.getElementById('paymentHistoryContent').innerHTML = '<div class="alert alert-danger">Failed to load payment history.</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('paymentHistoryContent').innerHTML = error.message.includes('Authentication')
            ? '<div class="alert alert-warning">Please log in to view payment history.</div>'
            : '<div class="alert alert-danger">Failed to load payment history.</div>';
    });
}

function displayPaymentHistoryTotals(totalsByMethod, grandTotal) {
    const el = document.getElementById('paymentHistoryTotals');
    if (!totalsByMethod.length) {
        el.style.display = 'none';
        el.innerHTML = '';
        return;
    }

    let html = '<div class="row g-2">';
    totalsByMethod.forEach(row => {
        html += `<div class="col-md-3"><div class="card border-0 bg-light"><div class="card-body py-2 px-3">
            <small class="text-muted d-block">${row.label || row.payment_method}</small>
            <strong>GH₵${parseFloat(row.total).toFixed(2)}</strong>
            <small class="text-muted"> (${row.count})</small>
        </div></div></div>`;
    });
    html += `<div class="col-md-3"><div class="card border-0 bg-success-subtle"><div class="card-body py-2 px-3">
        <small class="text-muted d-block">Grand Total</small>
        <strong class="text-success">GH₵${parseFloat(grandTotal).toFixed(2)}</strong>
    </div></div></div>`;
    html += '</div>';
    el.innerHTML = html;
    el.style.display = 'block';
}

function paymentPatientName(payment) {
    return payment.patient?.full_name
        || payment.invoice?.patient?.full_name
        || 'Unknown Patient';
}

function paymentMethodLabel(method) {
    const labels = {
        cash: 'Cash',
        paystack: 'Paystack',
        mobile_money_offline: 'Mobile Money (Offline)',
        momo: 'Mobile Money',
        card: 'Card',
        bank_transfer: 'Bank Transfer',
        insurance: 'Insurance',
    };
    return labels[method] || (method ? method.replace(/_/g, ' ') : 'Unknown');
}

// Display payment history
function displayPaymentHistory(payments) {
    if (!payments || !payments.data || payments.data.length === 0) {
        document.getElementById('paymentHistoryContent').innerHTML = '<div class="alert alert-info">No payment history found for the selected filters.</div>';
        return;
    }

    const receiptBase = '{{ url("cashier/payment") }}';
    let html = '<div class="table-responsive"><table class="table table-striped table-sm">';
    html += '<thead><tr><th>Date</th><th>Patient</th><th>Invoice</th><th>Amount</th><th>Method</th><th>Status</th><th>Receipt</th></tr></thead><tbody>';

    payments.data.forEach(payment => {
        const dateStr = payment.payment_date || payment.created_at;
        html += `
            <tr>
                <td>${new Date(dateStr).toLocaleDateString()}</td>
                <td>${paymentPatientName(payment)}</td>
                <td>${payment.invoice?.invoice_number || 'N/A'}</td>
                <td><strong>GH₵${parseFloat(payment.amount).toFixed(2)}</strong></td>
                <td><span class="badge bg-info">${paymentMethodLabel(payment.payment_method)}</span></td>
                <td><span class="badge bg-${payment.status === 'completed' ? 'success' : 'secondary'}">${payment.status || 'completed'}</span></td>
                <td><a href="${receiptBase}/${payment.id}/receipt" class="btn btn-sm btn-outline-primary" target="_blank"><i class="bi bi-receipt"></i></a></td>
            </tr>
        `;
    });

    html += '</tbody></table></div>';

    // Pagination
    if (payments.last_page > 1) {
        html += '<nav class="mt-3"><ul class="pagination pagination-sm justify-content-center mb-0">';
        if (payments.prev_page_url) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="loadPaymentHistory(${payments.current_page - 1}); return false;">Previous</a></li>`;
        }
        html += `<li class="page-item disabled"><span class="page-link">Page ${payments.current_page} of ${payments.last_page}</span></li>`;
        if (payments.next_page_url) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="loadPaymentHistory(${payments.current_page + 1}); return false;">Next</a></li>`;
        }
        html += '</ul></nav>';
    }

    html += `<div class="text-muted small mt-2 text-center">Showing ${payments.data.length} of ${payments.total} payments</div>`;

    document.getElementById('paymentHistoryContent').innerHTML = html;
}

// Load pending payments
function loadPendingPayments() {
    fetch(`{{ route('cashier.pending-payments') }}`)
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            throw new Error('Authentication required or server error');
        }
    })
    .then(data => {
        if (data.success) {
            displayPendingPayments(data);
        } else {
            document.getElementById('pendingPaymentsContent').innerHTML = '<div class="alert alert-danger">Failed to load pending payments.</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (error.message.includes('Authentication required')) {
            document.getElementById('pendingPaymentsContent').innerHTML = '<div class="alert alert-warning">Please log in to view pending payments.</div>';
        } else {
            document.getElementById('pendingPaymentsContent').innerHTML = '<div class="alert alert-danger">Failed to load pending payments.</div>';
        }
    });
}

// Display pending payments
function displayPendingPayments(data) {
    if (data.patients_with_charges.length === 0) {
        document.getElementById('pendingPaymentsContent').innerHTML = '<div class="alert alert-info">No pending payments found.</div>';
        return;
    }
    
    let html = '<div class="row mb-3">';
    html += `<div class="col-md-6"><div class="card bg-warning text-white"><div class="card-body"><h5>Total Pending Amount</h5><h3>GH₵${parseFloat(data.total_pending_amount).toFixed(2)}</h3></div></div></div>`;
    html += `<div class="col-md-6"><div class="card bg-info text-white"><div class="card-body"><h5>Patients with Charges</h5><h3>${data.total_patients}</h3></div></div></div>`;
    html += '</div>';
    
    html += '<div class="table-responsive"><table class="table table-striped">';
    html += '<thead><tr><th>Patient</th><th>Charges Count</th><th>Total Amount</th><th>Actions</th></tr></thead><tbody>';
    
    data.patients_with_charges.forEach(patient => {
        html += `
            <tr>
                <td>
                    <div>
                        <strong>${patient.patient.name}</strong><br>
                        <small class="text-muted">${patient.patient.patient_number} | ${patient.patient.phone || 'N/A'}</small>
                    </div>
                </td>
                <td><span class="badge bg-primary">${patient.charges_count}</span></td>
                <td><strong>GH₵${parseFloat(patient.total_amount).toFixed(2)}</strong></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="selectPatient(${patient.patient.id})">
                        <i class="bi bi-eye"></i> View Details
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    
    document.getElementById('pendingPaymentsContent').innerHTML = html;
}

// Load daily report
function loadDailyReport() {
    const date = document.getElementById('reportDate').value;
    
    // Show loading indicator
    document.getElementById('dailyReportContent').innerHTML = '<div class="text-center"><i class="bi bi-hourglass-split"></i> Loading daily report...</div>';
    
    fetch(`{{ route('cashier.daily-report') }}?date=${date}`)
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            throw new Error('Authentication required or server error');
        }
    })
    .then(data => {
        if (data.success) {
            displayDailyReport(data);
        } else {
            document.getElementById('dailyReportContent').innerHTML = '<div class="alert alert-danger">Failed to load daily report.</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (error.message.includes('Authentication required')) {
            document.getElementById('dailyReportContent').innerHTML = '<div class="alert alert-warning">Please log in to view daily report.</div>';
        } else {
            document.getElementById('dailyReportContent').innerHTML = '<div class="alert alert-danger">Failed to load daily report.</div>';
        }
    });
}

// Display daily report
function displayDailyReport(data) {
    const stats = data.statistics;
    
    let html = '<div class="row mb-4">';
    html += `<div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body text-center"><h5>Patients Served</h5><h3>${stats.total_patients_served}</h3></div></div></div>`;
    html += `<div class="col-md-3"><div class="card bg-success text-white"><div class="card-body text-center"><h5>Total Payments</h5><h3>${stats.total_payments}</h3></div></div></div>`;
    html += `<div class="col-md-3"><div class="card bg-info text-white"><div class="card-body text-center"><h5>Amount Collected</h5><h3>GH₵${parseFloat(stats.total_collected).toFixed(2)}</h3></div></div></div>`;
    html += `<div class="col-md-3"><div class="card bg-warning text-white"><div class="card-body text-center"><h5>Pending Amount</h5><h3>GH₵${parseFloat(stats.outstanding_amount).toFixed(2)}</h3></div></div></div>`;
    html += '</div>';
    
    // Payment breakdown
    if (data.payment_breakdown.length > 0) {
        html += '<h6>Payment Breakdown by Method</h6>';
        html += '<div class="table-responsive mb-4"><table class="table table-sm">';
        html += '<thead><tr><th>Payment Method</th><th>Count</th><th>Total Amount</th></tr></thead><tbody>';
        
        data.payment_breakdown.forEach(breakdown => {
            html += `
                <tr>
                    <td><span class="badge bg-secondary">${paymentMethodLabel(breakdown.payment_method)}</span></td>
                    <td>${breakdown.count}</td>
                    <td>GH₵${parseFloat(breakdown.total).toFixed(2)}</td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
    }
    
    // Recent payments
    if (data.recent_payments.length > 0) {
        html += '<h6>Recent Payments</h6>';
        html += '<div class="table-responsive"><table class="table table-sm">';
        html += '<thead><tr><th>Time</th><th>Patient</th><th>Amount</th><th>Method</th></tr></thead><tbody>';
        
        data.recent_payments.forEach(payment => {
            const patientName = payment.patient?.full_name
                || payment.invoice?.patient?.full_name
                || 'Unknown Patient';
            html += `
                <tr>
                    <td>${new Date(payment.created_at).toLocaleTimeString()}</td>
                    <td>${patientName}</td>
                    <td>GH₵${parseFloat(payment.amount).toFixed(2)}</td>
                    <td><span class="badge bg-info">${paymentMethodLabel(payment.payment_method)}</span></td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
    }
    
    document.getElementById('dailyReportContent').innerHTML = html;
}

// Load outstanding debts
function loadOutstandingDebts() {
    fetch(`{{ route('cashier.outstanding-debts') }}`)
    .then(response => {
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // If not JSON, likely a redirect or error page
            throw new Error('Authentication required or server error');
        }
    })
    .then(data => {
        if (data.success) {
            displayOutstandingDebts(data);
        } else {
            document.getElementById('outstandingDebtsContent').innerHTML = '<div class="alert alert-danger">Failed to load outstanding debts.</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (error.message.includes('Authentication required')) {
            document.getElementById('outstandingDebtsContent').innerHTML = '<div class="alert alert-warning">Please log in to view outstanding debts.</div>';
        } else {
            document.getElementById('outstandingDebtsContent').innerHTML = '<div class="alert alert-danger">Failed to load outstanding debts. Please try again.</div>';
        }
    });
}

// Display outstanding debts
function displayOutstandingDebts(data) {
    const summary = data.debt_summary;
    
    let html = '<div class="row mb-4">';
    html += `<div class="col-md-3"><div class="card bg-danger text-white"><div class="card-body text-center"><h5>Total Debtors</h5><h3>${summary.total_debtors}</h3></div></div></div>`;
    html += `<div class="col-md-3"><div class="card bg-warning text-white"><div class="card-body text-center"><h5>Total Outstanding</h5><h3>GH₵${parseFloat(summary.total_outstanding || 0).toFixed(2)}</h3><small>Invoices: GH₵${parseFloat(summary.total_outstanding_invoices || 0).toFixed(2)}<br>Pending: GH₵${parseFloat(summary.total_pending_charges || 0).toFixed(2)}</small></div></div></div>`;
    html += `<div class="col-md-3"><div class="card bg-dark text-white"><div class="card-body text-center"><h5>Overdue Amount</h5><h3>GH₵${parseFloat(summary.total_overdue || 0).toFixed(2)}</h3></div></div></div>`;
    html += `<div class="col-md-3"><div class="card bg-info text-white"><div class="card-body text-center"><h5>Average Debt</h5><h3>GH₵${parseFloat(summary.average_debt || 0).toFixed(2)}</h3></div></div></div>`;
    html += '</div>';
    
    // Debtors list with invoice breakdown
    if (data.debtors.length > 0) {
        html += '<h6 class="mt-4 mb-3"><i class="bi bi-people"></i> Debtors List (Patients with Unpaid Invoices)</h6>';
        
        data.debtors.forEach(debtor => {
            const statusClass = debtor.debt_status === 'overdue' ? 'danger' : (debtor.debt_status === 'critical' ? 'dark' : 'warning');
            const statusBadgeClass = debtor.debt_status === 'overdue' ? 'bg-danger' : (debtor.debt_status === 'critical' ? 'bg-dark' : 'bg-warning');
            
            html += `
                <div class="card border-${statusClass} mb-3">
                    <div class="card-header bg-${statusClass} text-white d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="fs-5" style="cursor: pointer;" onclick="collectPaymentForDebtor(${debtor.patient.id}, '${debtor.patient.name.replace(/'/g, "\\'")}')">${debtor.patient.name}</strong>
                            <br><small>${debtor.patient.patient_number} | ${debtor.patient.phone || 'N/A'}</small>
                        </div>
                        <div class="text-end">
                            <div class="fs-4 fw-bold">GH₵${parseFloat(debtor.total_outstanding || 0).toFixed(2)}</div>
                            <small>Outstanding</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <small class="text-muted">Status</small><br>
                                <span class="badge ${statusBadgeClass}">${debtor.debt_status.toUpperCase()}</span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Days Overdue</small><br>
                                <strong>${Math.max(0, parseInt(debtor.days_overdue) || 0)} days</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Last Payment</small><br>
                                <strong>${debtor.last_payment_date ? new Date(debtor.last_payment_date).toLocaleDateString() : 'Never'}</strong>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-success w-100" onclick="collectPaymentForDebtor(${debtor.patient.id}, '${debtor.patient.name.replace(/'/g, "\\'")}')">
                                    <i class="bi bi-cash-coin"></i> Collect Payment
                                </button>
                            </div>
                        </div>
                        
                        <!-- Invoice breakdown -->
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Status</th>
                                        <th>Total Amount</th>
                                        <th>Paid</th>
                                        <th>Balance</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>`;
            
            debtor.invoices.forEach(invoice => {
                const invoiceStatusClass = invoice.status === 'overdue' ? 'bg-danger' : 
                                          invoice.status === 'partial' ? 'bg-warning' : 'bg-secondary';
                const paymentStatusBadge = invoice.total_paid > 0 ? 
                    `<span class="badge bg-info">Partial</span>` : 
                    `<span class="badge bg-secondary">Unpaid</span>`;
                
                html += `
                    <tr>
                        <td><strong>${invoice.invoice_number}</strong></td>
                        <td>
                            <span class="badge ${invoiceStatusClass}">${invoice.status.toUpperCase()}</span>
                            ${invoice.total_paid > 0 ? paymentStatusBadge : ''}
                        </td>
                        <td>GH₵${parseFloat(invoice.total_amount).toFixed(2)}</td>
                        <td class="text-success"><strong>GH₵${parseFloat(invoice.total_paid).toFixed(2)}</strong></td>
                        <td class="text-danger"><strong>GH₵${parseFloat(invoice.balance).toFixed(2)}</strong></td>
                        <td>
                            ${invoice.due_date ? new Date(invoice.due_date).toLocaleDateString() : 'N/A'}
                            ${invoice.is_overdue ? '<span class="badge bg-danger ms-1">OVERDUE</span>' : ''}
                        </td>
                    </tr>
                `;
            });
            
            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        html += '<div class="alert alert-info"><i class="bi bi-info-circle"></i> No debtors with outstanding invoices found.</div>';
    }
    
    // Pending charges section (services not yet invoiced)
    if (data.pending_charges && data.pending_charges.length > 0) {
        html += '<h6 class="mt-4 mb-3"><i class="bi bi-clock-history"></i> Pending Charges (Not Yet Invoiced)</h6>';
        html += '<div class="alert alert-warning"><i class="bi bi-info-circle"></i> These are services that have been completed but not yet invoiced. They will be included in the outstanding amount.</div>';
        
        data.pending_charges.forEach(patientCharges => {
            html += `
                <div class="card border-warning mb-3">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="fs-5" style="cursor: pointer;" onclick="collectPaymentForDebtor(${patientCharges.patient.id}, '${patientCharges.patient.name.replace(/'/g, "\\'")}')">${patientCharges.patient.name}</strong>
                            <br><small>${patientCharges.patient.patient_number} | ${patientCharges.patient.phone || 'N/A'}</small>
                        </div>
                        <div class="text-end">
                            <div class="fs-4 fw-bold">GH₵${parseFloat(patientCharges.total_amount || 0).toFixed(2)}</div>
                            <small>Pending Charges</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>`;
            
            patientCharges.charges.forEach(charge => {
                html += `
                    <tr>
                        <td><span class="badge bg-info">${charge.type.toUpperCase()}</span></td>
                        <td>${charge.description}</td>
                        <td class="text-end"><strong>GH₵${parseFloat(charge.amount).toFixed(2)}</strong></td>
                        <td>${new Date(charge.date).toLocaleDateString()}</td>
                    </tr>
                `;
            });
            
            html += `
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-success w-100" onclick="collectPaymentForDebtor(${patientCharges.patient.id}, '${patientCharges.patient.name.replace(/'/g, "\\'")}')">
                                <i class="bi bi-cash-coin"></i> Process Payment for Pending Charges
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    // All unpaid invoices section
    if (data.unpaid_invoices && data.unpaid_invoices.length > 0) {
        html += '<h6 class="mt-4 mb-3"><i class="bi bi-file-text"></i> All Unpaid/Partial Invoices</h6>';
        html += '<div class="table-responsive"><table class="table table-hover table-striped">';
        html += '<thead class="table-dark"><tr><th>Invoice</th><th>Patient</th><th>Payment Status</th><th>Total Amount</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Action</th></tr></thead><tbody>';
        
        data.unpaid_invoices.forEach(invoice => {
            const paymentStatusBadge = invoice.payment_status === 'Partial' ? 
                '<span class="badge bg-warning">Partial</span>' : 
                '<span class="badge bg-secondary">Unpaid</span>';
            
            const statusBadge = invoice.is_overdue ? 
                '<span class="badge bg-danger ms-1">OVERDUE</span>' : 
                (invoice.status === 'pending' ? '<span class="badge bg-info ms-1">PENDING</span>' : '');
            
            html += `
                <tr class="${invoice.is_overdue ? 'table-danger' : ''}">
                    <td><strong>${invoice.invoice_number}</strong></td>
                    <td>
                        <strong class="text-primary" style="cursor: pointer;" onclick="collectPaymentForDebtor(${invoice.patient.id}, '${invoice.patient.name.replace(/'/g, "\\'")}')">${invoice.patient.name}</strong><br>
                        <small class="text-muted">${invoice.patient.phone || 'N/A'}</small>
                    </td>
                    <td>${paymentStatusBadge} ${statusBadge}</td>
                    <td>GH₵${parseFloat(invoice.total_amount || 0).toFixed(2)}</td>
                    <td class="text-success"><strong>GH₵${parseFloat(invoice.total_paid || 0).toFixed(2)}</strong></td>
                    <td class="text-danger"><strong>GH₵${parseFloat(invoice.balance || 0).toFixed(2)}</strong></td>
                    <td>
                        ${invoice.due_date ? new Date(invoice.due_date).toLocaleDateString() : 'N/A'}
                        ${invoice.is_overdue ? `<br><small class="text-danger">${invoice.days_overdue} days overdue</small>` : ''}
                    </td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="collectPaymentForDebtor(${invoice.patient.id}, '${invoice.patient.name.replace(/'/g, "\\'")}')">
                            <i class="bi bi-cash-coin"></i> Pay
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
    }
    
    document.getElementById('outstandingDebtsContent').innerHTML = html;
}

// Export daily report
function exportDailyReport() {
    const date = document.getElementById('reportDate').value;
    window.open(`{{ route('cashier.daily-report') }}?date=${date}&export=pdf`, '_blank');
}

// Set date to today
function setToday() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('reportDate').value = today;
    loadDailyReport();
}

// Create manual invoice for patient (opens billing create form with patient pre-selected)
function createManualInvoice() {
    if (!selectedPatient) {
        alert('Please select a patient first');
        return;
    }
    
    window.location.href = '{{ route("billing.create") }}?patient_id=' + selectedPatient.id;
}

// Collect payment for debtor from outstanding debts modal
function collectPaymentForDebtor(patientId, patientName) {
    // Close the outstanding debts modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('outstandingDebtsModal'));
    if (modal) {
        modal.hide();
    }
    
    // Clear search box
    document.getElementById('patientSearch').value = '';
    
    // Load patient directly by ID instead of searching
    selectPatient(patientId);
    
    // Show a helpful message
    setTimeout(() => {
        const patientDetails = document.getElementById('patientDetails');
        if (patientDetails) {
            patientDetails.innerHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> 
                    <strong>Loading payment details for ${patientName}</strong><br>
                    <small>Please wait while we load their charges...</small>
                </div>
            `;
        }
    }, 100);
}

// Process Paystack Payment for Cashier
async function processPaystackPayment(amount, notes) {
    try {
        const processBtn = document.querySelector('button[onclick="processPayment()"]');
        const originalText = processBtn.innerHTML;
        processBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Initializing Paystack...';
        processBtn.disabled = true;

        // Generate payment reference
        const reference = 'CASHIER-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        const email = selectedPatient.email || 'cashier@hospital.com';

        // Initialize Paystack payment
        const initResponse = await fetch('/api/billing/payments/paystack/initialize', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Authorization': 'Bearer ' + (localStorage.getItem('auth_token') || '')
            },
            body: JSON.stringify({
                amount: amount,
                email: email,
                reference: reference,
                payment_type: 'invoice',
                metadata: {
                    patient_id: selectedPatient.id,
                    patient_name: selectedPatient.full_name,
                    charges: selectedCharges,
                    notes: notes,
                    source: 'cashier_dashboard'
                }
            })
        });

        const initData = await initResponse.json();

        if (!initData.success) {
            throw new Error(initData.message || 'Failed to initialize payment');
        }

        // Open Paystack in new window
        const paystackWindow = window.open(
            initData.data.authorization_url,
            'PaystackPayment',
            'width=600,height=700,scrollbars=yes,resizable=yes'
        );

        // Monitor payment window
        processBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Waiting for payment...';

        const checkPaymentInterval = setInterval(async () => {
            // Check if window is closed
            if (paystackWindow.closed) {
                clearInterval(checkPaymentInterval);

                // Verify payment
                try {
                    const verifyResponse = await fetch('/api/billing/payments/paystack/verify', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Authorization': 'Bearer ' + (localStorage.getItem('auth_token') || '')
                        },
                        body: JSON.stringify({ reference: reference })
                    });

                    const verifyData = await verifyResponse.json();

                    if (verifyData.success && verifyData.data.status === 'success') {
                        // Record payment on server
                        const recordResponse = await fetch(`{{ route('cashier.process-payment') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                patient_id: selectedPatient.id,
                                charges: selectedCharges,
                                payment_method: 'paystack',
                                total_amount: amount,
                                notes: notes,
                                payment_reference: reference,
                                transaction_id: verifyData.data.id
                            })
                        });

                        const recordData = await recordResponse.json();

                        if (recordData.success) {
                            currentPaymentId = recordData.payment_id;
                            document.getElementById('successInvoiceNumber').textContent = recordData.invoice_number;
                            document.getElementById('successAmount').textContent = `GH₵${amount.toFixed(2)}`;
                            
                            const modal = new bootstrap.Modal(document.getElementById('paymentSuccessModal'));
                            modal.show();
                            
                            clearSelection();
                            alert('✅ Payment successful via Paystack!');
                        } else {
                            alert('Payment verified but failed to record: ' + recordData.message);
                        }
                    } else {
                        alert('⚠️ Payment was not completed or cancelled.');
                    }
                } catch (error) {
                    console.error('Error verifying payment:', error);
                    alert('Error verifying payment. Please check payment status manually.');
                }

                processBtn.innerHTML = originalText;
                processBtn.disabled = false;
            }
        }, 1000);

    } catch (error) {
        console.error('Paystack payment error:', error);
        alert('❌ Paystack payment failed: ' + error.message);
        
        const processBtn = document.querySelector('button[onclick="processPayment()"]');
        processBtn.innerHTML = '<i class="bi bi-credit-card"></i> Process Payment';
        processBtn.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('tab') === 'history' || window.location.hash === '#payment-history') {
        if (typeof viewPaymentHistory === 'function') {
            viewPaymentHistory();
        }
    }
});
</script>
@endpush
