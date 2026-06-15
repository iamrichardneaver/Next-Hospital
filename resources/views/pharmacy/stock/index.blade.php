@extends('layouts.app')

@section('title', 'Stock Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-box-seam"></i> Stock Management</h1>
            <p class="text-secondary mb-0">Manage drug inventory and stock levels</p>
        </div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('pharmacy.stock.export'),
                'permission' => 'manage_pharmacy_inventory',
                'params' => request()->only(['branch_id', 'status', 'search']),
            ])
            @can('manage_pharmacy_inventory')
            <button type="button" class="btn btn-success" onclick="showAddStockModal()">
                <i class="bi bi-plus-circle"></i> Add New Stock
            </button>
            @endcan
            <a href="{{ route('pharmacy.prescriptions') }}" class="btn btn-info">
                <i class="bi bi-prescription"></i> Prescriptions
            </a>
            <a href="{{ route('pharmacy.dispensing') }}" class="btn btn-primary">
                <i class="bi bi-capsule-pill"></i> Dispensing
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(isset($criticalAlerts) && (
        $criticalAlerts['out_of_stock_drugs']->count() > 0 ||
        $criticalAlerts['expired_drugs']->count() > 0 ||
        $criticalAlerts['critical_low_stock']->count() > 0
    ))
    <div class="card border-danger shadow-sm mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-exclamation-octagon"></i> Critical Inventory Alerts</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @if($criticalAlerts['out_of_stock_drugs']->count() > 0)
                <div class="col-md-4 mb-3">
                    <h6 class="text-danger"><i class="bi bi-x-circle"></i> Out of Stock</h6>
                    <ul class="list-group list-group-flush">
                        @foreach($criticalAlerts['out_of_stock_drugs'] as $stock)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>{{ $stock->drug?->name ?? 'Unknown drug' }}</span>
                            <span class="badge bg-danger">0 units</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if($criticalAlerts['expired_drugs']->count() > 0)
                <div class="col-md-4 mb-3">
                    <h6 class="text-danger"><i class="bi bi-calendar-x"></i> Expired</h6>
                    <ul class="list-group list-group-flush">
                        @foreach($criticalAlerts['expired_drugs'] as $stock)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>{{ $stock->drug?->name ?? 'Unknown drug' }}</span>
                            <span class="badge bg-danger">{{ $stock->expiry_date?->format('M d, Y') }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if($criticalAlerts['critical_low_stock']->count() > 0)
                <div class="col-md-4 mb-3">
                    <h6 class="text-warning"><i class="bi bi-exclamation-triangle"></i> Critical Low Stock</h6>
                    <ul class="list-group list-group-flush">
                        @foreach($criticalAlerts['critical_low_stock'] as $stock)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>{{ $stock->drug?->name ?? 'Unknown drug' }}</span>
                            <span class="badge bg-warning text-dark">{{ $stock->current_stock }} / {{ $stock->reorder_level }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            <div class="text-end">
                <a href="{{ route('pharmacy.stock', ['status' => 'low_stock']) }}" class="btn btn-sm btn-outline-danger">View All Alerts</a>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stat-label">Total Items</div>
                <div class="stat-value">{{ number_format($statistics['total_items']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-label">Low Stock</div>
                <div class="stat-value">{{ number_format($statistics['low_stock']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-label">Out of Stock</div>
                <div class="stat-value">{{ number_format($statistics['out_of_stock']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-label">Expiring Soon</div>
                <div class="stat-value">{{ number_format($statistics['expiring_soon']) }}</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" id="stockFilterForm" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Drug name, code...">
                </div>
                <div class="col-md-2">
                    <label for="branch_id" class="form-label">Branch</label>
                    <select class="form-select" id="branch_id" name="branch_id">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (int) ($branchId ?? $userBranchId) === (int) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="low_stock" {{ request('status') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                        <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                        <option value="expiring_soon" {{ request('status') == 'expiring_soon' ? 'selected' : '' }}>Expiring Soon</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('pharmacy.stock') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Stock Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Drug Stock</h5>
        </div>
        <div class="card-body">
            @if($stocks->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Drug</th>
                                <th>Branch</th>
                                <th>Current Stock</th>
                                <th>Reorder Level</th>
                                <th>Status</th>
                                <th>Expiry Date</th>
                                <th>Cost Price</th>
                                <th>Selling Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stocks as $stock)
                            <tr>
                                <td>
                                    @if($stock->drug)
                                    <div>
                                        <strong>{{ $stock->drug->name }}</strong>
                                        @if($stock->drug->generic_name)
                                            <br><small class="text-muted">{{ $stock->drug->generic_name }}</small>
                                        @endif
                                        <br><small class="text-muted">{{ $stock->drug->strength }} {{ $stock->drug->unit }}</small>
                                    </div>
                                    @else
                                    <div>
                                        <strong class="text-danger">Drug Not Found</strong>
                                        <br><small class="text-muted">Deleted or Invalid</small>
                                    </div>
                                    @endif
                                </td>
                                <td>{{ $stock->branch->name ?? 'N/A' }}</td>
                                <td>
                                    @php
                                        $reorderLevel = $stock->reorder_level ?? $stock->minimum_stock ?? 10;
                                    @endphp
                                    <span class="badge bg-{{ $stock->current_stock > $reorderLevel ? 'success' : ($stock->current_stock > 0 ? 'warning text-dark' : 'danger') }}">
                                        {{ $stock->current_stock }}
                                    </span>
                                </td>
                                <td>{{ $stock->reorder_level }}</td>
                                <td>
                                    @php
                                        $statusClass = $stock->getStockStatusBadgeClass();
                                        $statusText = match($stock->getStockStatus()) {
                                            'expired' => 'Expired',
                                            'expiring_soon' => 'Expiring Soon',
                                            'low_stock' => 'Low Stock',
                                            'normal' => 'Normal',
                                            default => 'Unknown'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                </td>
                                <td>
                                    @if($stock->expiry_date)
                                        {{ $stock->expiry_date->format('M d, Y') }}
                                        @if($stock->isExpiringSoon())
                                            @php $daysLeft = $stock->getDaysUntilExpiry(); @endphp
                                            @if($daysLeft !== null)
                                            <br><small class="text-warning">{{ $daysLeft }} days left</small>
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>₵{{ number_format($stock->cost_price, 2) }}</td>
                                <td>₵{{ number_format($stock->selling_price, 2) }}</td>
                                <td>
                                    <div class="dropdown position-static">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow">
                                            @can('manage_pharmacy_inventory')
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="event.preventDefault(); updateStock({{ $stock->id }}, {{ $stock->current_stock }}, {{ $stock->reorder_level ?? 0 }})">
                                                    <i class="bi bi-pencil"></i> Update Stock
                                                </a>
                                            </li>
                                            @endcan
                                            @if($stock->drug)
                                            <li>
                                                <a class="dropdown-item" href="{{ route('pharmacy.show', $stock->drug) }}">
                                                    <i class="bi bi-eye"></i> View Drug
                                                </a>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $stocks->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No stock items found</h5>
                    <p class="text-muted">No stock items match your current filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Add New Stock Modal -->
@can('manage_pharmacy_inventory')
<div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStockModalLabel"><i class="bi bi-plus-circle"></i> Add New Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('pharmacy.stock.add') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="add_drug_id" class="form-label">Select Drug <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_drug_id" name="drug_id" required>
                                <option value="">-- Select Drug --</option>
                                @php
                                    $allDrugs = \App\Models\Drug::where('is_active', true)
                                        ->orderBy('name')
                                        ->get();
                                @endphp
                                @foreach($allDrugs as $drug)
                                    <option value="{{ $drug->id }}">
                                        {{ $drug->name }}
                                        @if($drug->generic_name)
                                            ({{ $drug->generic_name }})
                                        @endif
                                        - {{ $drug->strength }} {{ $drug->unit }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_branch_id" name="branch_id" required>
                                <option value="">-- Select Branch --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ $loop->first ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_batch_number" class="form-label">Batch Number</label>
                            <input type="text" class="form-control" id="add_batch_number" name="batch_number" placeholder="e.g., BAT-001">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_current_stock" class="form-label">Initial Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="add_current_stock" name="current_stock" min="0" value="0" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_minimum_stock" class="form-label">Minimum Stock</label>
                            <input type="number" class="form-control" id="add_minimum_stock" name="minimum_stock" min="0" value="10">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_reorder_level" class="form-label">Reorder Level <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="add_reorder_level" name="reorder_level" min="0" value="20" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_expiry_date" class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" id="add_expiry_date" name="expiry_date" min="{{ date('Y-m-d') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_cost_price" class="form-label">Cost Price (GH₵) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="add_cost_price" name="cost_price" min="0" value="0" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_selling_price" class="form-label">Selling Price (GH₵) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="add_selling_price" name="selling_price" min="0" value="0" required>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="add_supplier" class="form-label">Supplier</label>
                            <input type="text" class="form-control" id="add_supplier" name="supplier" placeholder="e.g., Pharmanova Ltd">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<!-- Update Stock Modal -->
@can('manage_pharmacy_inventory')
<div class="modal fade" id="updateStockModal" tabindex="-1" aria-labelledby="updateStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStockModalLabel">Update Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('pharmacy.stock.update') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="stock_id" name="stock_id">
                    
                    <div class="mb-3">
                        <label for="current_stock" class="form-label">Current Stock</label>
                        <input type="number" class="form-control" id="current_stock" name="current_stock" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reorder_level" class="form-label">Reorder Level</label>
                        <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Stock update notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<style>
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    padding: 1.5rem;
    color: white;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.stat-card.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card.danger {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
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

<script>
function showAddStockModal() {
    var modal = new bootstrap.Modal(document.getElementById('addStockModal'));
    modal.show();
}

function updateStock(stockId, currentStock, reorderLevel) {
    document.getElementById('stock_id').value = stockId;
    document.getElementById('current_stock').value = currentStock;
    document.getElementById('reorder_level').value = reorderLevel;
    
    var modal = new bootstrap.Modal(document.getElementById('updateStockModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    var filterForm = document.getElementById('stockFilterForm');
    if (!filterForm) return;

    filterForm.querySelectorAll('select').forEach(function(el) {
        el.addEventListener('change', function() { filterForm.submit(); });
    });

    var searchInput = filterForm.querySelector('#search');
    var searchTimer;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() { filterForm.submit(); }, 400);
        });
    }
});
</script>
@endsection
