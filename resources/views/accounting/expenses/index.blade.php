@extends('layouts.app')

@section('title', 'Expenses Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-wallet2 me-2"></i>Expenses Management
            </h1>
            <p class="text-secondary mb-0">Record, approve, and track hospital operating expenses</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('accounting.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Hub</a>
            @if($stats['pending'] > 0)
            <a href="{{ route('accounting.expenses.index', ['status' => 'pending']) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-hourglass-split"></i> Approval Queue ({{ $stats['pending'] }})
            </a>
            @endif
            @include('accounting.partials.export-buttons')
            @can('manage_expenses')
            <a href="{{ route('accounting.expenses.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New Expense</a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card primary">
                <div class="stat-label">Filtered Total</div>
                <div class="stat-value">GH₵{{ number_format($stats['total'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card success">
                <div class="stat-label">Approved/Paid</div>
                <div class="stat-value">GH₵{{ number_format($stats['approved'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card warning">
                <div class="stat-label">Pending Approval</div>
                <div class="stat-value">{{ number_format($stats['pending']) }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">From</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">To</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Source</label>
                    <select name="source" class="form-select form-select-sm">
                        <option value="">All expenses</option>
                        <option value="manual" {{ request('source') === 'manual' ? 'selected' : '' }}>Manual / operational</option>
                        <option value="inventory" {{ request('source') === 'inventory' ? 'selected' : '' }}>Inventory PO (auto)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Department</label>
                    <select name="department" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($departments ?? [] as $code => $label)
                            <option value="{{ $code }}" {{ request('department') === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach(['draft','pending','approved','rejected','paid'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->hasRole('super_admin'))
                <div class="col-md-2">
                    <label class="form-label small">Branch</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string)request('branch_id') === (string)$branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label small">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Description, ref...">
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ref</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Department</th>
                        <th>Description</th>
                        <th>Branch</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    <tr>
                        <td><a href="{{ route('accounting.expenses.show', $expense) }}">{{ $expense->expense_reference ?? $expense->id }}</a></td>
                        <td>{{ $expense->expense_date?->format('M d, Y') }}</td>
                        <td>{{ $expense->category?->name ?? '—' }}</td>
                        <td>{{ $expense->getDepartmentLabel() }}</td>
                        <td class="text-truncate" style="max-width:200px">{{ $expense->description }}</td>
                        <td>{{ $expense->branch?->name ?? '—' }}</td>
                        <td class="text-end">GH₵{{ number_format($expense->amount, 2) }}</td>
                        <td><span class="badge {{ $expense->getStatusBadgeClass() }}">{{ ucfirst($expense->status) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('accounting.expenses.show', $expense) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No expenses found. @can('manage_expenses')<a href="{{ route('accounting.expenses.create') }}">Create one</a>.@endcan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages())
        <div class="card-footer">{{ $expenses->links() }}</div>
        @endif
    </div>
</div>
@endsection
