@extends('layouts.app')

@section('title', 'Edit Invoice')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1" style="color: #1e3a5f;">Edit Invoice</h1><p class="text-secondary mb-0">Update invoice information</p></div>
        <a href="{{ route('billing.show', 1) }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Cancel</a>
    </div>

    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('billing.update', 1) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="partial">Partially Paid</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('billing.show', 1) }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Invoice</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
