@extends('layouts.app')

@section('title', 'Comprehensive Price List')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1" style="color: #1e3a5f;">
                    <i class="bi bi-currency-dollar me-2"></i>Comprehensive Price List
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('pricing.index') }}">Pricing</a></li>
                        <li class="breadcrumb-item active">Price List</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-primary">
                    <i class="bi bi-printer"></i> Print
                </button>
                <a href="{{ route('pricing.export', request()->all()) }}" class="btn btn-outline-success">
                    <i class="bi bi-download"></i> Export CSV
                </a>
                <a href="{{ route('pricing.index') }}" class="btn btn-primary">
                    <i class="bi bi-gear"></i> Manage Pricing
                </a>
            </div>
        </div>
    </div>

    {{-- Branch & Service Type Filters --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0 text-dark"><i class="bi bi-funnel me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('pricing.price-list') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter by Branch</label>
                        <select name="branch_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Filter by Service Type</label>
                        <select name="service_type" class="form-select" onchange="this.form.submit()">
                            <option value="">All Services</option>
                            <option value="consultation" {{ request('service_type') == 'consultation' ? 'selected' : '' }}>Consultation</option>
                            <option value="procedure" {{ request('service_type') == 'procedure' ? 'selected' : '' }}>Procedure</option>
                            <option value="lab_test" {{ request('service_type') == 'lab_test' ? 'selected' : '' }}>Lab Test</option>
                            <option value="imaging" {{ request('service_type') == 'imaging' ? 'selected' : '' }}>Imaging</option>
                            <option value="surgery" {{ request('service_type') == 'surgery' ? 'selected' : '' }}>Surgery</option>
                            <option value="emergency" {{ request('service_type') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                            <option value="other" {{ request('service_type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="{{ route('pricing.price-list') }}" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Service Pricing by Category --}}
    @forelse($servicePricing as $serviceType => $services)
    <div class="card mb-4">
        <div class="card-header" style="background-color: #1e3a5f;">
            <h5 class="mb-0 text-white">
                <i class="bi bi-{{
                    $serviceType === 'consultation' ? 'person-badge' :
                    ($serviceType === 'procedure' ? 'hospital' :
                    ($serviceType === 'lab_test' ? 'clipboard2-pulse' :
                    ($serviceType === 'imaging' ? 'x-diamond' :
                    ($serviceType === 'surgery' ? 'scissors' :
                    ($serviceType === 'emergency' ? 'ambulance' : 'clipboard-check')))))
                }} me-2"></i>
                {{ ucwords(str_replace('_', ' ', $serviceType)) }} Services
                <span class="badge bg-light text-dark ms-2">{{ count($services) }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="15%">Service ID</th>
                            <th width="40%">Service Name</th>
                            <th width="30%">Description</th>
                            <th width="15%" class="text-end">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($services as $service)
                        <tr>
                            <td><code class="text-primary fw-bold">{{ $service->service_id }}</code></td>
                            <td class="fw-bold text-dark">{{ $service->service_name }}</td>
                            <td><small class="text-muted">{{ Str::limit($service->description ?? 'N/A', 60) }}</small></td>
                            <td class="text-end">
                                <strong class="text-success fs-6">{{ $service->currency }} {{ number_format($service->base_price, 2) }}</strong>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
            <h5 class="text-muted">No services found</h5>
            <p class="text-muted">Try adjusting your filters or <a href="{{ route('pricing.index') }}">add new services</a>.</p>
        </div>
    </div>
    @endforelse

    {{-- Appointment Fees --}}
    @if(count($appointmentFees) > 0)
    <div class="card mb-4">
        <div class="card-header" style="background-color: #1e3a5f;">
            <h5 class="mb-0 text-white">
                <i class="bi bi-calendar-check me-2"></i>
                Appointment & Consultation Fees
                <span class="badge bg-light text-dark ms-2">{{ collect($appointmentFees)->flatten()->count() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="25%">Category</th>
                            <th width="25%">Type</th>
                            <th width="25%">Doctor/Branch</th>
                            <th width="25%" class="text-end">Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($appointmentFees as $category => $fees)
                            @foreach($fees as $fee)
                            <tr>
                                <td><span class="badge bg-info">{{ ucwords($category) }}</span></td>
                                <td>
                                    <span class="badge bg-secondary">{{ ucwords(str_replace('-', ' ', $fee->appointment_type)) }}</span>
                                </td>
                                <td class="text-dark">
                                    @if($fee->doctor)
                                        <i class="bi bi-person-badge me-1"></i>
                                        Dr. {{ $fee->doctor->firstname }} {{ $fee->doctor->lastname }}
                                    @else
                                        <i class="bi bi-building me-1"></i>
                                        {{ $fee->branch->name ?? 'General' }}
                                    @endif
                                </td>
                                <td class="text-end">
                                    <strong class="text-success fs-6">{{ $fee->currency }} {{ number_format($fee->base_fee, 2) }}</strong>
                                </td>
                            </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Pharmacy/Drug Pricing --}}
    @if(count($drugs) > 0)
    <div class="card mb-4">
        <div class="card-header" style="background-color: #1e3a5f;">
            <h5 class="mb-0 text-white">
                <i class="bi bi-capsule me-2"></i>
                Pharmacy & Medication Prices
                <span class="badge bg-light text-dark ms-2">{{ count($drugs) }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="30%">Drug Name</th>
                            <th width="20%">Generic Name</th>
                            <th width="15%">Form & Strength</th>
                            <th width="15%" class="text-end">Cash Price</th>
                            <th width="15%" class="text-end">NHIS Price</th>
                            <th width="5%" class="text-center">NHIS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($drugs as $drug)
                        <tr>
                            <td class="fw-bold text-dark">{{ $drug->name }}</td>
                            <td><small class="text-muted">{{ $drug->generic_name ?? 'N/A' }}</small></td>
                            <td>
                                <span class="badge bg-info">{{ $drug->dosage_form }}</span>
                                @if($drug->strength)
                                <small class="text-muted ms-1">{{ $drug->strength }}</small>
                                @endif
                            </td>
                            <td class="text-end">
                                <strong class="text-success fs-6">GHS {{ number_format($drug->selling_price, 2) }}</strong>
                            </td>
                            <td class="text-end">
                                @if($drug->nhis_price)
                                    <strong class="text-primary fs-6">GHS {{ number_format($drug->nhis_price, 2) }}</strong>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($drug->nhis_covered)
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                @else
                                    <i class="bi bi-x-circle text-muted"></i>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Footer Notes --}}
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-dark mb-3"><i class="bi bi-info-circle me-2"></i>Important Notes:</h6>
                    <ul class="text-muted small mb-0">
                        <li>All prices are in Ghana Cedis (GHS) unless otherwise stated</li>
                        <li>NHIS-covered services may have different rates for insured patients</li>
                        <li>Prices may vary by branch location</li>
                        <li>Emergency services may have additional charges</li>
                        <li>Prices are subject to change without prior notice</li>
                    </ul>
                </div>
                <div class="col-md-6 text-end">
                    <div class="alert alert-info mb-0">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-clock-history fs-4 me-3"></i>
                            <div class="text-start">
                                <strong class="d-block">Last Updated:</strong>
                                <small>{{ now()->format('F d, Y \a\t g:i A') }}</small>
                                <hr class="my-2">
                                <strong class="d-block">Generated By:</strong>
                                <small>{{ auth()->user()->firstname }} {{ auth()->user()->lastname }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Print Styles */
@media print {
    /* Hide navigation and buttons */
    .btn, nav, .sidebar, .header, .breadcrumb, .alert {
        display: none !important;
    }
    
    /* Reset card styling for print */
    .card {
        border: 1px solid #dee2e6 !important;
        page-break-inside: avoid;
        box-shadow: none !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
        border-bottom: 2px solid #dee2e6 !important;
    }
    
    /* Ensure text is visible when printed */
    body {
        background: white !important;
    }
    
    .text-white, .text-dark, .text-muted {
        color: #000 !important;
    }
    
    .table-dark {
        background-color: #f8f9fa !important;
    }
    
    .table-dark th {
        color: #000 !important;
        background-color: #e9ecef !important;
    }
    
    /* Make badges visible */
    .badge {
        border: 1px solid #000 !important;
        color: #000 !important;
    }
    
    /* Page breaks */
    .card {
        margin-bottom: 1rem;
    }
    
    h1, h5 {
        color: #000 !important;
    }
}

/* Screen-only enhancements */
@media screen {
    .table tbody tr:hover {
        background-color: rgba(30, 58, 95, 0.05);
    }
}
</style>
@endpush
@endsection
