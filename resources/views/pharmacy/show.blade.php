@extends('layouts.app')

@section('title', 'Drug Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">{{ $pharmacy->name }}</h1>
            <p class="text-secondary mb-0">Drug Information & Stock Details</p>
        </div>
        <div>
            <a href="{{ route('pharmacy.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            @can('edit_drugs')
            <a href="{{ route('pharmacy.edit', $pharmacy) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-capsule me-2"></i>Drug Information
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th width="180">Drug Name:</th>
                            <td><strong>{{ $pharmacy->name }}</strong></td>
                        </tr>
                        <tr>
                            <th>Generic Name:</th>
                            <td>{{ $pharmacy->generic_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Drug Code:</th>
                            <td>{{ $pharmacy->drug_code ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Category:</th>
                            <td><span class="badge bg-primary">{{ $pharmacy->category }}</span></td>
                        </tr>
                        <tr>
                            <th>Dosage Form:</th>
                            <td>{{ $pharmacy->dosage_form }}</td>
                        </tr>
                        <tr>
                            <th>Strength:</th>
                            <td>{{ $pharmacy->strength ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Unit:</th>
                            <td>{{ $pharmacy->unit ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Manufacturer:</th>
                            <td>{{ $pharmacy->manufacturer ?? '-' }}</td>
                        </tr>
                        @if($pharmacy->description)
                        <tr>
                            <th>Description:</th>
                            <td>{{ $pharmacy->description }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">
                        <i class="bi bi-currency-dollar me-2"></i>Pricing & Stock
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th width="180">Selling Price:</th>
                            <td><strong class="text-success">₵{{ number_format($pharmacy->selling_price ?? 0, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Cost Price:</th>
                            <td>₵{{ number_format($pharmacy->cost_price ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <th>NHIS Price:</th>
                            <td>₵{{ number_format($pharmacy->nhis_price ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Total Stock:</th>
                            <td>
                                @php
                                    $totalStock = $pharmacy->stocks()->sum('current_stock') ?? 0;
                                @endphp
                                <span class="badge bg-{{ $totalStock > 100 ? 'success' : ($totalStock > 0 ? 'warning' : 'danger') }}">
                                    {{ $totalStock }} units
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Stock Value:</th>
                            <td>₵{{ number_format($totalStock * ($pharmacy->cost_price ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <th>Requires Prescription:</th>
                            <td>
                                @if($pharmacy->requires_prescription)
                                    <span class="badge bg-warning">Yes</span>
                                @else
                                    <span class="badge bg-success">No</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>NHIS Covered:</th>
                            <td>
                                @if($pharmacy->nhis_covered)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                @if($pharmacy->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
