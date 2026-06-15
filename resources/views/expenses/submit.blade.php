@extends('layouts.app')

@section('title', 'Record Expense')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0" style="color: #1e3a5f;">
                <i class="bi bi-wallet2 me-2"></i>Record Expense
            </h1>
            <p class="text-secondary mb-0">{{ $departmentLabel }} — submitted for accountant approval</p>
        </div>
        <a href="{{ route('expenses.my') }}" class="btn btn-outline-secondary btn-sm">My Expenses</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('expenses.submit.store') }}">
                        @csrf
                        <input type="hidden" name="department" value="{{ $department }}">
                        @include('accounting.expenses._form', [
                            'expense' => null,
                            'defaultBranchId' => $defaultBranchId,
                            'isStaffSubmit' => true,
                            'lockedDepartment' => $department,
                        ])
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-1"></i> Submit for Approval
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            @include('expenses._help')
        </div>
    </div>
</div>
@endsection
