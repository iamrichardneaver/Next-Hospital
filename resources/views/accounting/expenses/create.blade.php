@extends('layouts.app')

@section('title', 'New Expense')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0" style="color: #1e3a5f;"><i class="bi bi-plus-circle me-2"></i>Record Expense</h1>
        <a href="{{ route('accounting.expenses.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('accounting.expenses.store') }}">
                @csrf
                @include('accounting.expenses._form', [
                    'expense' => null,
                    'defaultBranchId' => $defaultBranchId ?? null,
                    'departments' => $departments ?? \App\Models\Expense::DEPARTMENTS,
                    'isStaffSubmit' => false,
                ])
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="submit_action" value="submit" class="btn btn-primary">Submit for Approval</button>
                    <button type="submit" name="submit_action" value="draft" class="btn btn-outline-secondary">Save as Draft</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
