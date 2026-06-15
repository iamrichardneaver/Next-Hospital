@extends('layouts.app')

@section('title', 'Edit Expense')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0" style="color: #1e3a5f;"><i class="bi bi-pencil me-2"></i>Edit Expense</h1>
        <a href="{{ route('accounting.expenses.show', $expense) }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('accounting.expenses.update', $expense) }}">
                @csrf
                @method('PUT')
                @include('accounting.expenses._form', [
                    'departments' => $departments ?? \App\Models\Expense::DEPARTMENTS,
                    'isStaffSubmit' => false,
                ])
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="submit_action" value="submit" class="btn btn-primary">Update</button>
                    @if($expense->status === 'draft')
                    <button type="submit" name="submit_action" value="draft" class="btn btn-outline-secondary">Keep as Draft</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
