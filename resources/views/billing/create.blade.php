@extends('layouts.app')

@section('title', 'New Invoice')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">New Invoice</h1>
            <p class="text-secondary mb-0">Create a new billing invoice with service selection or manual fees</p>
        </div>
        <div class="d-flex gap-2">
            @if(!empty($preselectedPatientId))
            <a href="{{ route('cashier.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-cash-coin"></i> Back to Cashier
            </a>
            @endif
            <a href="{{ route('billing.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Invoices
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('billing.store') }}" method="POST" id="invoice-form">
                        @csrf
                        
                        <!-- Patient and Branch Selection -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                                <select class="form-select patient-search-select @error('patient_id') is-invalid @enderror" id="patient_id" name="patient_id" required
                                    data-placeholder="Type to search by name or number...">
                                    <option value=""></option>
                                    @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}" {{ old('patient_id', $preselectedPatientId ?? null) == $patient->id ? 'selected' : '' }}>
                                        {{ $patient->patient_number }} - {{ $patient->first_name }} {{ $patient->last_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                @if($user->isSuperAdmin())
                                    <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
                                        <option value="">Select Branch</option>
                                        @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $defaultBranch ? $defaultBranch->id : '') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                @else
                                    @if($defaultBranch)
                                        <input type="hidden" name="branch_id" value="{{ $defaultBranch->id }}">
                                        <input type="text" class="form-control" value="{{ $defaultBranch->name }}" readonly>
                                        <small class="text-muted">Branch automatically selected based on your access</small>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle"></i> No branch assigned to your account. Please contact administrator.
                                        </div>
                                    @endif
                                @endif
                                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <!-- Invoice Dates -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="invoice_date" class="form-label">Invoice Date</label>
                                <input type="date" class="form-control @error('invoice_date') is-invalid @enderror" 
                                       id="invoice_date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}">
                                @error('invoice_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                       id="due_date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}">
                                @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        
                        <!-- Service Selection -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="bi bi-list-check me-2"></i>Service Selection
                                </h5>
                                
                                <!-- Service Type Tabs -->
                                <ul class="nav nav-tabs" id="serviceTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="lab-tab" data-bs-toggle="tab" data-bs-target="#lab" type="button" role="tab">
                                            <i class="bi bi-flask"></i> Lab Tests
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="pharmacy-tab" data-bs-toggle="tab" data-bs-target="#pharmacy" type="button" role="tab">
                                            <i class="bi bi-capsule"></i> Pharmacy
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="radiology-tab" data-bs-toggle="tab" data-bs-target="#radiology" type="button" role="tab">
                                            <i class="bi bi-camera"></i> Radiology
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="consultation-tab" data-bs-toggle="tab" data-bs-target="#consultation" type="button" role="tab">
                                            <i class="bi bi-person-badge"></i> Consultation
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" type="button" role="tab">
                                            <i class="bi bi-gear"></i> Other Services
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">
                                            <i class="bi bi-pencil"></i> Manual Entry
                                        </button>
                                    </li>
                                </ul>
                                
                                <!-- Service Tab Content -->
                                <div class="tab-content mt-3" id="serviceTabContent">
                                    <!-- Lab Tests -->
                                    <div class="tab-pane fade show active" id="lab" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <input type="text" class="form-control" id="lab-search" placeholder="Search lab tests...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <button type="button" class="btn btn-primary" onclick="loadServices('lab_tests', 'lab-results')">
                                                    <i class="bi bi-search"></i> Search
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary ms-2" onclick="document.getElementById('lab-search').value=''; loadServices('lab_tests', 'lab-results')">
                                                    <i class="bi bi-arrow-clockwise"></i> Clear
                                                </button>
                                            </div>
                                        </div>
                                        <div id="lab-results" class="service-results"></div>
                                    </div>
                                    
                                    <!-- Pharmacy -->
                                    <div class="tab-pane fade" id="pharmacy" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <input type="text" class="form-control" id="pharmacy-search" placeholder="Search drugs...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <button type="button" class="btn btn-primary" onclick="loadServices('drugs', 'pharmacy-results')">
                                                    <i class="bi bi-search"></i> Search
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary ms-2" onclick="document.getElementById('pharmacy-search').value=''; loadServices('drugs', 'pharmacy-results')">
                                                    <i class="bi bi-arrow-clockwise"></i> Clear
                                                </button>
                                            </div>
                                        </div>
                                        <div id="pharmacy-results" class="service-results"></div>
                                    </div>
                                    
                                    <!-- Radiology -->
                                    <div class="tab-pane fade" id="radiology" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <input type="text" class="form-control" id="radiology-search" placeholder="Search radiology services...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <button type="button" class="btn btn-primary" onclick="loadServices('radiology', 'radiology-results')">
                                                    <i class="bi bi-search"></i> Search
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary ms-2" onclick="document.getElementById('radiology-search').value=''; loadServices('radiology', 'radiology-results')">
                                                    <i class="bi bi-arrow-clockwise"></i> Clear
                                                </button>
                                            </div>
                                        </div>
                                        <div id="radiology-results" class="service-results"></div>
                                    </div>
                                    
                                    <!-- Consultation -->
                                    <div class="tab-pane fade" id="consultation" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <input type="text" class="form-control" id="consultation-search" placeholder="Search consultation fees...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <button type="button" class="btn btn-primary" onclick="loadServices('consultation', 'consultation-results')">
                                                    <i class="bi bi-search"></i> Search
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary ms-2" onclick="document.getElementById('consultation-search').value=''; loadServices('consultation', 'consultation-results')">
                                                    <i class="bi bi-arrow-clockwise"></i> Clear
                                                </button>
                                            </div>
                                        </div>
                                        <div id="consultation-results" class="service-results"></div>
                                    </div>
                                    
                                    <!-- Other Services -->
                                    <div class="tab-pane fade" id="services" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <input type="text" class="form-control" id="services-search" placeholder="Search services...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <button type="button" class="btn btn-primary" onclick="loadServices('service_pricing', 'services-results')">
                                                    <i class="bi bi-search"></i> Search
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary ms-2" onclick="document.getElementById('services-search').value=''; loadServices('service_pricing', 'services-results')">
                                                    <i class="bi bi-arrow-clockwise"></i> Clear
                                                </button>
                                            </div>
                                        </div>
                                        <div id="services-results" class="service-results"></div>
                                    </div>
                                    
                                    <!-- Manual Entry -->
                                    <div class="tab-pane fade" id="manual" role="tabpanel">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Use the manual entry section below to add custom items not found in the service catalog.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Selected Services -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="bi bi-cart-check me-2"></i>Selected Services
                                </h5>
                                <div id="selected-services">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        No services selected. Use the service selection tabs above to add services to your invoice.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Manual Invoice Items (Hidden by default) -->
                        <div class="row mb-4" id="manual-invoice-items">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="bi bi-pencil-square me-2"></i>Manual Invoice Items
                                </h5>
                        <div id="invoice-items">
                            <div class="row invoice-item mb-2" data-index="0">
                                <div class="col-md-1">
                                    <label class="form-label text-muted">#</label>
                                    <input type="text" class="form-control item-number" value="1" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted">Description</label>
                                            <input type="text" class="form-control item-description" name="items[0][description]" placeholder="Item description">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted">Qty</label>
                                            <input type="number" class="form-control item-quantity" name="items[0][quantity]" placeholder="Qty" value="1" min="0.01" step="0.01">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted">Unit Price</label>
                                            <input type="number" step="0.01" class="form-control item-unit-price" name="items[0][unit_price]" placeholder="Unit Price" min="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted">Total</label>
                                    <input type="number" step="0.01" class="form-control item-total" name="items[0][total]" placeholder="Total" readonly>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label text-muted">&nbsp;</label>
                                            <button type="button" class="btn btn-danger btn-sm remove-item d-block">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                </div>
                            </div>
                        </div>
                                <button type="button" class="btn btn-sm btn-secondary mb-3" id="add-item">
                                    <i class="bi bi-plus"></i> Add Manual Item
                                </button>
                            </div>
                        </div>

                        <!-- Payment and Notes -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                @include('partials.payment-method-fields', [
                                    'idPrefix' => 'billing_create',
                                    'showPaystack' => true,
                                    'selected' => old('payment_method'),
                                    'required' => false,
                                ])
                                @error('payment_method')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" name="notes" rows="3" placeholder="Additional notes...">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <!-- Tax and Discount -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="tax_amount" class="form-label">Tax Amount</label>
                                <input type="number" step="0.01" class="form-control @error('tax_amount') is-invalid @enderror" 
                                       id="tax_amount" name="tax_amount" placeholder="0.00" min="0" value="{{ old('tax_amount', 0) }}">
                                @error('tax_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="discount_amount" class="form-label">Discount Amount</label>
                                <input type="number" step="0.01" class="form-control @error('discount_amount') is-invalid @enderror" 
                                       id="discount_amount" name="discount_amount" placeholder="0.00" min="0" value="{{ old('discount_amount', 0) }}">
                                @error('discount_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <!-- Invoice Summary -->
                        <div class="row">
                            <div class="col-md-8 offset-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Subtotal:</strong>
                                            <span id="subtotal">GH₵ 0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Tax:</strong>
                                            <span id="tax-display">GH₵ 0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Discount:</strong>
                                            <span id="discount-display" class="text-success">-GH₵ 0.00</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <strong>Total:</strong>
                                            <strong id="total">GH₵ 0.00</strong>
                                        </div>
                                        <input type="hidden" id="subtotal_hidden" name="subtotal" value="0">
                                        <input type="hidden" id="total_hidden" name="total_amount" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('billing.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Invoice
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
.service-card {
    transition: all 0.3s ease;
    border-left: 4px solid #007bff;
}

.service-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-left-color: #28a745;
}

.service-results {
    max-height: 400px;
    overflow-y: auto;
}

.nav-tabs .nav-link {
    border-radius: 0.375rem 0.375rem 0 0;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.nav-tabs .nav-link:hover:not(.active) {
    background-color: #f8f9fa;
}

#selected-services .table {
    font-size: 0.9rem;
}

#selected-services .badge {
    font-size: 0.75rem;
}

#manual-invoice-items {
    transition: all 0.3s ease;
    overflow: hidden;
    max-height: 0;
    margin: 0;
    padding: 0;
}

#manual-invoice-items.show {
    max-height: 1000px;
    margin-bottom: 1.5rem;
    padding: 0;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
let itemIndex = 0;
let selectedServices = [];

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

// Load services dynamically
function loadServices(type, containerId, showLoading = true) {
    // Map service types to their corresponding search input IDs
    const searchInputMap = {
        'lab_tests': 'lab-search',
        'drugs': 'pharmacy-search',
        'radiology': 'radiology-search',
        'consultation': 'consultation-search',
        'service_pricing': 'services-search'
    };
    
    const searchInputId = searchInputMap[type] || type + '-search';
    const searchElement = document.getElementById(searchInputId);
    const searchTerm = searchElement ? searchElement.value : '';
    const container = document.getElementById(containerId);
    
    if (showLoading) {
        container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-2">Loading services...</span></div>';
    }
    
        fetch(`{{ route('billing.services.public') }}?type=${type}&search=${encodeURIComponent(searchTerm)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Response is not JSON');
                }
                return response.json();
            })
            .then(data => {
            container.innerHTML = '';
            
            if (data.length === 0) {
                container.innerHTML = '<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No services found. Try adjusting your search terms.</div>';
                return;
            }
            
            data.forEach(service => {
                const serviceCard = document.createElement('div');
                serviceCard.className = 'card mb-2 service-card';
                const serviceName = String(service.name ?? '');
                const serviceType = String(service.service_type || type);
                serviceCard.innerHTML = `
                    <div class="card-body py-2">
                        <div class="row align-items-center">
                            <div class="col-md-5">
                                <h6 class="mb-1 text-primary">${escapeHtml(serviceName)}</h6>
                                <small class="text-muted">${escapeHtml(service.description || 'No description available')}</small>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <strong class="text-success fs-6">GH₵ ${parseFloat(service.base_price || 0).toFixed(2)}</strong>
                                    <br><small class="text-muted">${escapeHtml(serviceType)}</small>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="button" class="btn btn-sm btn-primary add-service-btn"
                                    data-id="${service.id}"
                                    data-name="${escapeHtml(serviceName)}"
                                    data-price="${parseFloat(service.base_price || 0)}"
                                    data-type="${escapeHtml(serviceType)}">
                                    <i class="bi bi-plus-circle"></i> Add to Invoice
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(serviceCard);
            });
        })
        .catch(error => {
            console.error('Error loading services:', error);
            if (error.message.includes('Response is not JSON') || error.message.includes('401')) {
                container.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Please log in to load services. <a href="{{ route("login") }}" class="alert-link">Login here</a></div>';
            } else if (error.message.includes('404')) {
                container.innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i>Services endpoint not found. Please contact administrator.</div>';
            } else {
                container.innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i>Error loading services: ' + error.message + '</div>';
            }
        });
}

// Add service to invoice
function addService(serviceId, serviceName, price, serviceType) {
    const service = {
        id: serviceId,
        name: serviceName,
        price: price,
        type: serviceType,
        quantity: 1
    };
    
    selectedServices.push(service);
    updateSelectedServices();
    calculateTotal();
}

// Remove service from invoice
function removeService(index) {
    selectedServices.splice(index, 1);
    updateSelectedServices();
    calculateTotal();
}

// Update selected services display
function updateSelectedServices() {
    const container = document.getElementById('selected-services');
    
    if (selectedServices.length === 0) {
        container.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No services selected. Use the service selection tabs above to add services to your invoice.
            </div>
        `;
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-sm">';
    html += '<thead><tr><th>Service</th><th>Type</th><th>Qty</th><th>Price</th><th>Total</th><th>Action</th></tr></thead><tbody>';
    
    selectedServices.forEach((service, index) => {
        const total = service.price * service.quantity;
        html += `
            <tr>
                <td>${service.name}</td>
                <td><span class="badge bg-secondary">${service.type}</span></td>
                <td>
                    <input type="number" class="form-control form-control-sm" value="${service.quantity}" 
                           min="0.01" step="0.01" onchange="updateServiceQuantity(${index}, this.value)">
                </td>
                <td>GH₵ ${parseFloat(service.price).toFixed(2)}</td>
                <td>GH₵ ${total.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeService(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

// Update service quantity
function updateServiceQuantity(index, quantity) {
    selectedServices[index].quantity = parseFloat(quantity);
    updateSelectedServices();
    calculateTotal();
}

// Add new manual item
document.getElementById('add-item').addEventListener('click', function() {
    itemIndex++;
    const itemsContainer = document.getElementById('invoice-items');
    const newItem = document.querySelector('.invoice-item').cloneNode(true);
    
    // Update the cloned item
    newItem.setAttribute('data-index', itemIndex);
    newItem.querySelector('.item-number').value = itemIndex + 1;
    newItem.querySelector('.item-description').name = `items[${itemIndex}][description]`;
    newItem.querySelector('.item-description').value = '';
    newItem.querySelector('.item-quantity').name = `items[${itemIndex}][quantity]`;
    newItem.querySelector('.item-quantity').value = '1';
    newItem.querySelector('.item-unit-price').name = `items[${itemIndex}][unit_price]`;
    newItem.querySelector('.item-unit-price').value = '';
    newItem.querySelector('.item-total').name = `items[${itemIndex}][total]`;
    newItem.querySelector('.item-total').value = '';
    
    itemsContainer.appendChild(newItem);
    updateItemNumbers();
    attachItemEventListeners(newItem);
});

// Remove item
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-item')) {
        const item = e.target.closest('.invoice-item');
        const itemsContainer = document.getElementById('invoice-items');
        
        if (itemsContainer.children.length > 1) {
            item.remove();
            updateItemNumbers();
        }
    }
});

// Update item numbers
function updateItemNumbers() {
    const items = document.querySelectorAll('.invoice-item');
    items.forEach((item, index) => {
        item.querySelector('.item-number').value = index + 1;
        item.setAttribute('data-index', index);
    });
}

// Attach event listeners to item
function attachItemEventListeners(item) {
    const quantityInput = item.querySelector('.item-quantity');
    const unitPriceInput = item.querySelector('.item-unit-price');
    const totalInput = item.querySelector('.item-total');
    
    function calculateItemTotal() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const unitPrice = parseFloat(unitPriceInput.value) || 0;
        const total = quantity * unitPrice;
        totalInput.value = total.toFixed(2);
        calculateTotal();
    }
    
    quantityInput.addEventListener('input', calculateItemTotal);
    unitPriceInput.addEventListener('input', calculateItemTotal);
}

// Calculate total
function calculateTotal() {
    let subtotal = 0;
    
    // Calculate from selected services
    selectedServices.forEach(service => {
        subtotal += service.price * service.quantity;
    });
    
    // Calculate from manual items
    document.querySelectorAll('.item-total').forEach(input => {
        subtotal += parseFloat(input.value) || 0;
    });
    
    const tax = parseFloat(document.getElementById('tax_amount').value) || 0;
    const discount = parseFloat(document.getElementById('discount_amount').value) || 0;
    const total = subtotal + tax - discount;
    
    document.getElementById('subtotal').textContent = `GH₵ ${subtotal.toFixed(2)}`;
    document.getElementById('tax-display').textContent = `GH₵ ${tax.toFixed(2)}`;
    document.getElementById('discount-display').textContent = `-GH₵ ${discount.toFixed(2)}`;
    document.getElementById('total').textContent = `GH₵ ${total.toFixed(2)}`;
    
    document.getElementById('subtotal_hidden').value = subtotal;
    document.getElementById('total_hidden').value = total;
}

// Attach event listeners to tax and discount inputs
document.getElementById('tax_amount').addEventListener('input', calculateTotal);
document.getElementById('discount_amount').addEventListener('input', calculateTotal);

// Attach event listeners to existing items
document.querySelectorAll('.invoice-item').forEach(item => {
    attachItemEventListeners(item);
});

// Form submission
document.getElementById('invoice-form').addEventListener('submit', function(e) {
    // Remove empty manual line items so validation passes when only catalog services are used
    document.querySelectorAll('.invoice-item').forEach(item => {
        const description = (item.querySelector('.item-description')?.value || '').trim();
        const unitPrice = parseFloat(item.querySelector('.item-unit-price')?.value) || 0;
        if (!description || unitPrice <= 0) {
            item.querySelectorAll('input').forEach(input => input.removeAttribute('name'));
        }
    });

    const hasManualItems = Array.from(document.querySelectorAll('.invoice-item')).some(item => {
        const description = (item.querySelector('.item-description')?.value || '').trim();
        const unitPrice = parseFloat(item.querySelector('.item-unit-price')?.value) || 0;
        return description && unitPrice > 0;
    });

    if (selectedServices.length === 0 && !hasManualItems) {
        e.preventDefault();
        alert('Please add at least one service or manual item to the invoice.');
        return;
    }

    if (!document.getElementById('patient_id').value) {
        e.preventDefault();
        alert('Please select a patient.');
        return;
    }

    // Remove previously injected hidden fields (prevents duplicates on re-submit)
    this.querySelectorAll('input[data-selected-service="1"]').forEach(el => el.remove());

    // Add selected services to form data
    selectedServices.forEach((service, index) => {
        const serviceInput = document.createElement('input');
        serviceInput.type = 'hidden';
        serviceInput.setAttribute('data-selected-service', '1');
        serviceInput.name = `selected_services[${index}][id]`;
        serviceInput.value = service.id;
        this.appendChild(serviceInput);
        
        const nameInput = document.createElement('input');
        nameInput.type = 'hidden';
        nameInput.setAttribute('data-selected-service', '1');
        nameInput.name = `selected_services[${index}][name]`;
        nameInput.value = service.name;
        this.appendChild(nameInput);
        
        const priceInput = document.createElement('input');
        priceInput.type = 'hidden';
        priceInput.setAttribute('data-selected-service', '1');
        priceInput.name = `selected_services[${index}][price]`;
        priceInput.value = service.price;
        this.appendChild(priceInput);
        
        const quantityInput = document.createElement('input');
        quantityInput.type = 'hidden';
        quantityInput.setAttribute('data-selected-service', '1');
        quantityInput.name = `selected_services[${index}][quantity]`;
        quantityInput.value = service.quantity;
        this.appendChild(quantityInput);
        
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.setAttribute('data-selected-service', '1');
        typeInput.name = `selected_services[${index}][type]`;
        typeInput.value = service.type;
        this.appendChild(typeInput);
    });
});

// Delegate add-service clicks
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.add-service-btn');
    if (!btn) {
        return;
    }
    addService(btn.dataset.id, btn.dataset.name, parseFloat(btn.dataset.price || 0), btn.dataset.type);
});

// Auto-load services when tabs are clicked
document.addEventListener('DOMContentLoaded', function() {
    $('#patient_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: $('#patient_id').data('placeholder') || 'Type to search by name or number...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '{{ route("patients.search") }}',
            dataType: 'json',
            delay: 300,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(response) {
                const patients = response.data || [];
                return {
                    results: patients.map(patient => ({
                        id: patient.id,
                        text: `${patient.patient_number} - ${patient.first_name} ${patient.last_name}`
                    }))
                };
            },
            cache: true
        },
        language: {
            noResults: function() { return 'No patient found.'; },
            searching: function() { return 'Searching...'; },
            inputTooShort: function() { return 'Type at least 2 characters...'; }
        }
    });

    // Auto-load services for active tab
    loadServices('lab_tests', 'lab-results');
    
    // Tab click handlers
    document.getElementById('lab-tab').addEventListener('click', function() {
        loadServices('lab_tests', 'lab-results');
    });
    
    document.getElementById('pharmacy-tab').addEventListener('click', function() {
        loadServices('drugs', 'pharmacy-results');
    });
    
    document.getElementById('radiology-tab').addEventListener('click', function() {
        loadServices('radiology', 'radiology-results');
    });
    
    document.getElementById('consultation-tab').addEventListener('click', function() {
        loadServices('consultation', 'consultation-results');
    });
    
    document.getElementById('services-tab').addEventListener('click', function() {
        loadServices('service_pricing', 'services-results');
    });
    
    // Manual tab click handler - show/hide manual invoice items
    document.getElementById('manual-tab').addEventListener('click', function() {
        const manualItemsSection = document.getElementById('manual-invoice-items');
        if (manualItemsSection) {
            manualItemsSection.classList.add('show');
        }
    });
    
    // Hide manual invoice items when other tabs are clicked
    const otherTabs = ['lab-tab', 'pharmacy-tab', 'radiology-tab', 'consultation-tab', 'services-tab'];
    otherTabs.forEach(tabId => {
        document.getElementById(tabId).addEventListener('click', function() {
            const manualItemsSection = document.getElementById('manual-invoice-items');
            if (manualItemsSection) {
                manualItemsSection.classList.remove('show');
            }
        });
    });
    
    // Real-time search functionality
    const searchInputs = [
        { id: 'lab-search', type: 'lab_tests', container: 'lab-results' },
        { id: 'pharmacy-search', type: 'drugs', container: 'pharmacy-results' },
        { id: 'radiology-search', type: 'radiology', container: 'radiology-results' },
        { id: 'consultation-search', type: 'consultation', container: 'consultation-results' },
        { id: 'services-search', type: 'service_pricing', container: 'services-results' }
    ];
    
    searchInputs.forEach(input => {
        const searchInput = document.getElementById(input.id);
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 2 || this.value.length === 0) {
                    loadServices(input.type, input.container);
                }
            }, 300); // Debounce search by 300ms
        });
    });
    
    // Enter key search
    searchInputs.forEach(input => {
        document.getElementById(input.id).addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                loadServices(input.type, input.container);
            }
        });
    });
});
</script>
@endpush
