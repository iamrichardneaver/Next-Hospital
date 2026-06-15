@extends('layouts.app')

@section('title', 'Edit Lab Supplier')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Edit Supplier — {{ $supplier->name }}</h1>
        </div>
        <a href="{{ route('lab.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('lab.suppliers.update', $supplier) }}">
                @csrf
                @method('PUT')
                @include('suppliers._form', ['allowedTypes' => $allowedTypes, 'supplier' => $supplier])
                <button type="submit" class="btn btn-primary">Update Supplier</button>
            </form>
        </div>
    </div>
</div>
@endsection
