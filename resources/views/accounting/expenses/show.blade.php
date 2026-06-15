@extends('layouts.app')

@section('title', 'Expense Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">{{ $expense->expense_reference ?? 'Expense #'.$expense->id }}</h1>
            <span class="badge {{ $expense->getStatusBadgeClass() }}">{{ ucfirst($expense->status) }}</span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ $backRoute ?? route('accounting.expenses.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
            @can('manage_expenses')
                @if($expense->isEditable())
                <a href="{{ route('accounting.expenses.edit', $expense) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                @endif
            @endcan
            @canany(['approve_expenses', 'manage_expenses'])
                @if($expense->status === 'pending' && ((int) $expense->created_by !== (int) auth()->id() || auth()->user()->hasRole(['admin', 'super_admin'])))
                <form method="POST" action="{{ route('accounting.expenses.approve', $expense) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this expense?')">Approve</button>
                </form>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                @endif
            @endcanany
            @can('manage_expenses')
                @if($expense->status === 'approved')
                <form method="POST" action="{{ route('accounting.expenses.mark-paid', $expense) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Mark Paid</button>
                </form>
                @endif
            @endcan
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header"><strong>Expense Details</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Category</dt>
                        <dd class="col-sm-8">{{ $expense->category?->name }}</dd>
                        <dt class="col-sm-4">Department</dt>
                        <dd class="col-sm-8">{{ $expense->getDepartmentLabel() }}</dd>
                        @if($expense->isInventoryAuto())
                        <dt class="col-sm-4">Source</dt>
                        <dd class="col-sm-8"><span class="badge bg-info">Auto — inventory PO receive</span></dd>
                        @endif
                        <dt class="col-sm-4">Amount</dt>
                        <dd class="col-sm-8 fw-bold">GH₵{{ number_format($expense->amount, 2) }}</dd>
                        <dt class="col-sm-4">Date</dt>
                        <dd class="col-sm-8">{{ $expense->expense_date?->format('M d, Y') }}</dd>
                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8">{{ $expense->description }}</dd>
                        <dt class="col-sm-4">Vendor</dt>
                        <dd class="col-sm-8">{{ $expense->vendor ?? '—' }}</dd>
                        <dt class="col-sm-4">Reference</dt>
                        <dd class="col-sm-8">{{ $expense->reference ?? '—' }}</dd>
                        <dt class="col-sm-4">Payment Method</dt>
                        <dd class="col-sm-8">{{ $expense->payment_method ? ucfirst(str_replace('_', ' ', $expense->payment_method)) : '—' }}</dd>
                        <dt class="col-sm-4">Branch</dt>
                        <dd class="col-sm-8">{{ $expense->branch?->name ?? '—' }}</dd>
                        <dt class="col-sm-4">Notes</dt>
                        <dd class="col-sm-8">{{ $expense->notes ?? '—' }}</dd>
                        @if($expense->status === 'rejected')
                        <dt class="col-sm-4">Rejection Reason</dt>
                        <dd class="col-sm-8 text-danger">{{ $expense->rejection_reason }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header"><strong>Audit Trail</strong></div>
                <div class="card-body small">
                    <p class="mb-2"><strong>Created by:</strong> {{ $expense->creator?->name ?? '—' }}<br>
                    <span class="text-muted">{{ $expense->created_at?->format('M d, Y H:i') }}</span></p>
                    @if($expense->approver)
                    <p class="mb-0"><strong>{{ $expense->status === 'rejected' ? 'Rejected' : 'Approved' }} by:</strong> {{ $expense->approver->name }}<br>
                    <span class="text-muted">{{ $expense->approved_at?->format('M d, Y H:i') }}</span></p>
                    @endif
                </div>
            </div>
            @canany(['manage_expenses', 'create_expenses'])
            @if(in_array($expense->status, ['draft', 'pending', 'rejected']) && (auth()->user()->can('manage_expenses') || (int)$expense->created_by === (int)auth()->id()))
            <div class="card mt-3 border-danger">
                <div class="card-body">
                    <form method="POST" action="{{ route('accounting.expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense permanently?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">Delete Expense</button>
                    </form>
                </div>
            </div>
            @endif
            @endcanany
        </div>
    </div>
</div>

@canany(['approve_expenses', 'manage_expenses'])
@if($expense->status === 'pending' && ((int) $expense->created_by !== (int) auth()->id() || auth()->user()->hasRole(['admin', 'super_admin'])))
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('accounting.expenses.reject', $expense) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Reject Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Reason <span class="text-danger">*</span></label>
                <textarea name="rejection_reason" class="form-control" rows="3" required maxlength="500"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject</button>
            </div>
        </form>
    </div>
</div>
@endif
@endcanany
@endsection
