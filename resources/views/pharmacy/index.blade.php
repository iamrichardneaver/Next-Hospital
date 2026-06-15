@extends('layouts.app')

@section('title', 'Pharmacy')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Pharmacy & Drug Inventory</h1>
            <p class="text-secondary mb-0">Manage drugs, prescriptions, and inventory</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('pharmacy.export'),
                'permission' => 'manage_pharmacy_inventory',
                'params' => request()->only(['search', 'category', 'stock_status', 'prescription_required', 'nhis_covered']),
            ])
            <a href="{{ route('pharmacy.prescriptions') }}" class="btn btn-info">
                <i class="bi bi-prescription"></i> Prescriptions
            </a>
            <a href="{{ route('pharmacy.dispensing') }}" class="btn btn-warning me-2">
                <i class="bi bi-capsule-pill"></i> Dispensing
            </a>
            <a href="{{ route('pharmacy.stock') }}" class="btn btn-success me-2">
                <i class="bi bi-box-seam"></i> Stock Management
            </a>
            <a href="{{ route('pharmacy.history') }}" class="btn btn-secondary me-2">
                <i class="bi bi-clock-history"></i> History
            </a>
            <a href="{{ route('pharmacy.analytics') }}" class="btn btn-info me-2">
                <i class="bi bi-graph-up"></i> Analytics
            </a>
            @can('create_drugs')
            <a href="{{ route('pharmacy.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Drug
            </a>
            @endcan
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-2 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-capsule"></i>
                </div>
                <div class="stat-label">Total Drugs</div>
                <div class="stat-value">{{ number_format($statistics['total_drugs']) }}</div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-label">Low Stock</div>
                <div class="stat-value">{{ number_format($statistics['low_stock']) }}</div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="stat-label">Expiring Soon</div>
                <div class="stat-value">{{ number_format($statistics['expiring_soon']) }}</div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-label">Out of Stock</div>
                <div class="stat-value">{{ number_format($statistics['out_of_stock']) }}</div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-label">Inventory Value</div>
                <div class="stat-value">GH₵{{ number_format($statistics['total_value'], 2) }}</div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark">Filter Drugs</h5>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="bi bi-funnel"></i> Toggle Filters
            </button>
        </div>
        <div class="collapse {{ request()->hasAny(['search', 'category', 'stock_status', 'prescription_required', 'nhis_covered']) ? 'show' : '' }}" id="filterCollapse">
            <div class="card-body">
                <form method="GET" action="{{ route('pharmacy.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, generic name, code..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Stock Status</label>
                        <select name="stock_status" class="form-select">
                            <option value="">All</option>
                            <option value="in_stock" {{ request('stock_status') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                            <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Low Stock</option>
                            <option value="out" {{ request('stock_status') == 'out' ? 'selected' : '' }}>Out of Stock</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Prescription</label>
                        <select name="prescription_required" class="form-select">
                            <option value="">All</option>
                            <option value="1" {{ request('prescription_required') == '1' ? 'selected' : '' }}>Required</option>
                            <option value="0" {{ request('prescription_required') == '0' ? 'selected' : '' }}>Not Required</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <div class="col-md-12 text-end">
                        <a href="{{ route('pharmacy.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark">Drug Inventory</h5>
            <div class="btn-group" role="group">
                <a href="{{ route('pharmacy.index') }}" class="btn btn-outline-primary btn-sm {{ !request()->hasAny(['stock_status']) ? 'active' : '' }}">All</a>
                <a href="{{ route('pharmacy.index', ['stock_status' => 'in_stock']) }}" class="btn btn-outline-success btn-sm {{ request('stock_status') === 'in_stock' ? 'active' : '' }}">In Stock</a>
                <a href="{{ route('pharmacy.index', ['stock_status' => 'low']) }}" class="btn btn-outline-warning btn-sm {{ request('stock_status') === 'low' ? 'active' : '' }}">Low Stock</a>
                <a href="{{ route('pharmacy.index', ['stock_status' => 'out']) }}" class="btn btn-outline-danger btn-sm {{ request('stock_status') === 'out' ? 'active' : '' }}">Out of Stock</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Drug Name</th>
                            <th>Generic Name</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Unit Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($drugs as $drug)
                        <tr>
                            <td><strong>{{ $drug->name }}</strong></td>
                            <td>{{ $drug->generic_name ?? '-' }}</td>
                            <td><span class="badge bg-secondary">{{ $drug->category }}</span></td>
                            <td>
                                @php
                                    $totalStock = $drug->stocks()->sum('current_stock') ?? 0;
                                @endphp
                                @if($totalStock < 100)
                                    <span class="badge bg-danger">{{ $totalStock }}</span>
                                @elseif($totalStock < 500)
                                    <span class="badge bg-warning">{{ $totalStock }}</span>
                                @else
                                    <span class="badge bg-success">{{ $totalStock }}</span>
                                @endif
                            </td>
                            <td>₵{{ number_format($drug->selling_price ?? 0, 2) }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    @can('view_drugs')
                                    <a href="{{ route('pharmacy.show', $drug) }}" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @endcan
                                    @can('edit_drugs')
                                    <a href="{{ route('pharmacy.edit', $drug) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    @can('delete_drugs')
                                    <form action="{{ route('pharmacy.destroy', $drug) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this drug?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <p class="text-secondary">No drugs in inventory</p>
                                @can('create_drugs')
                                <a href="{{ route('pharmacy.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Add First Drug
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($drugs->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Showing {{ $drugs->firstItem() ?? 0 }} to {{ $drugs->lastItem() ?? 0 }} of {{ $drugs->total() }} entries
                </div>
                <div>
                    {{ $drugs->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
