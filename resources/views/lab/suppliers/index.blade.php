@extends('layouts.app')

@section('title', 'Lab Suppliers')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;"><i class="bi bi-truck me-2"></i>Lab Suppliers</h1>
            <p class="text-secondary mb-0">Manage reagent and consumable vendors</p>
        </div>
        @can('manage_lab_suppliers')
        <a href="{{ route('lab.suppliers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New Supplier</a>
        @endcan
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, contact, email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary btn-sm w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr>
                        <td><strong>{{ $supplier->name }}</strong></td>
                        <td>{{ $supplier->contact_person ?? '—' }}</td>
                        <td>{{ $supplier->phone ?? '—' }}</td>
                        <td><span class="badge bg-light text-dark">{{ ucfirst($supplier->supplier_type) }}</span></td>
                        <td>
                            <span class="badge bg-{{ $supplier->is_active ? 'success' : 'secondary' }}">
                                {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            @can('manage_lab_suppliers')
                            <a href="{{ route('lab.suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            @if($supplier->is_active)
                            <form method="POST" action="{{ route('lab.suppliers.deactivate', $supplier) }}" class="d-inline" onsubmit="return confirm('Deactivate this supplier?')">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger">Deactivate</button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('lab.suppliers.activate', $supplier) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Activate</button></form>
                            @endif
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No suppliers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())
        <div class="card-footer">{{ $suppliers->links() }}</div>
        @endif
    </div>
</div>
@endsection
