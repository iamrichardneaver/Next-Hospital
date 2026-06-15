@extends('layouts.app')

@section('title', 'New Pharmacy Supplier')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">New Pharmacy Supplier</h1>
            <p class="text-secondary mb-0">Add a vendor for pharmacy purchase orders</p>
        </div>
        <a href="{{ route('pharmacy.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('pharmacy.suppliers.store') }}">
                @csrf
                @include('suppliers._form', ['allowedTypes' => $allowedTypes, 'defaultType' => $defaultType])
                <button type="submit" class="btn btn-primary">Save Supplier</button>
            </form>
        </div>
    </div>
</div>
@endsection
