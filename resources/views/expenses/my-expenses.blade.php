@extends('layouts.app')

@section('title', 'My Expenses')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-wallet2 me-2"></i>My Expenses
            </h1>
            <p class="text-secondary mb-0">Track your submitted operational expenses</p>
        </div>
        @can('create_expenses')
        <a href="{{ route('expenses.submit.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Record Expense
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card warning">
                <div class="stat-label">Pending Approval</div>
                <div class="stat-value">{{ $stats['pending'] }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card success">
                <div class="stat-label">Approved / Paid</div>
                <div class="stat-value">{{ $stats['approved'] }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card danger">
                <div class="stat-label">Rejected</div>
                <div class="stat-value">{{ $stats['rejected'] }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                <label class="small mb-0">Status:</label>
                <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach(['pending','approved','paid','rejected','draft'] as $st)
                        <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
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
                        <th>Department</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    <tr>
                        <td>{{ $expense->expense_reference ?? $expense->id }}</td>
                        <td>{{ $expense->expense_date?->format('M d, Y') }}</td>
                        <td>{{ $expense->getDepartmentLabel() }}</td>
                        <td>{{ $expense->category?->name ?? '—' }}</td>
                        <td class="text-truncate" style="max-width:180px">{{ $expense->description }}</td>
                        <td class="text-end">GH₵{{ number_format($expense->amount, 2) }}</td>
                        <td><span class="badge {{ $expense->getStatusBadgeClass() }}">{{ ucfirst($expense->status) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('accounting.expenses.show', $expense) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No expenses yet.
                            @can('create_expenses')
                            <a href="{{ route('expenses.submit.create') }}">Record your first expense</a>.
                            @endcan
                        </td>
                    </tr>
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
