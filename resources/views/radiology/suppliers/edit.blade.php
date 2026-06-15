@extends('layouts.app')

@section('title', 'Edit Radiology Supplier')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Edit Radiology Supplier</h1>
            <p class="text-secondary mb-0">{{ $supplier->name }}</p>
        </div>
        <a href="{{ route('radiology.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('radiology.suppliers.update', $supplier) }}">
                @csrf
                @method('PUT')
                @include('suppliers._form', ['allowedTypes' => $allowedTypes, 'supplier' => $supplier])
                <button type="submit" class="btn btn-primary">Update Supplier</button>
            </form>
        </div>
    </div>
</div>
@endsection
