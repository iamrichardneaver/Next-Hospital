@extends('layouts.app')

@section('title', 'New Radiology Purchase Order')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3" style="color: #1e3a5f;"><i class="bi bi-file-medical"></i> New Radiology Supplies Purchase Order</h1>
        <p class="text-secondary">Order contrast, films, or consumables — stock goes to radiology inventory only</p>
    </div>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('radiology.purchases.store') }}">
        @csrf
        <div class="card mb-4">
            <div class="card-header"><strong>Order Details</strong></div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">Select supplier</option>
                        @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->hasRole('super_admin'))
                <div class="col-md-6">
                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                    <select name="branch_id" class="form-select" required>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id', $defaultBranchId) == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Line Items</strong>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addLine()"><i class="bi bi-plus"></i> Add Line</button>
            </div>
            <div class="card-body" id="lineItems">
                <div class="row g-2 line-item mb-2">
                    <div class="col-md-4">
                        <select name="items[0][radiology_inventory_item_id]" class="form-select" required>
                            <option value="">Select item</option>
                            @foreach($items->groupBy('category') as $category => $group)
                            <optgroup label="{{ ucfirst($category) }}">
                                @foreach($group as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->sku ?? $item->unit }})</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><input type="number" step="0.01" name="items[0][quantity_ordered]" class="form-control" placeholder="Qty" min="0.01" required></div>
                    <div class="col-md-2"><input type="number" step="0.01" name="items[0][unit_cost]" class="form-control" placeholder="Unit cost" min="0" required></div>
                    <div class="col-md-2"><input type="text" name="items[0][batch_number]" class="form-control" placeholder="Batch"></div>
                    <div class="col-md-2"><input type="date" name="items[0][expiry_date]" class="form-control"></div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" name="submit_action" value="draft" class="btn btn-outline-secondary">Save Draft</button>
            <button type="submit" name="submit_action" value="order" class="btn btn-primary">Save &amp; Mark Ordered</button>
            <a href="{{ route('radiology.purchases.index') }}" class="btn btn-link">Cancel</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
let lineIndex = 1;
const itemOptions = document.querySelector('.line-item select').innerHTML;

function addLine() {
    const html = `<div class="row g-2 line-item mb-2">
        <div class="col-md-4"><select name="items[${lineIndex}][radiology_inventory_item_id]" class="form-select" required>${itemOptions}</select></div>
        <div class="col-md-2"><input type="number" step="0.01" name="items[${lineIndex}][quantity_ordered]" class="form-control" placeholder="Qty" min="0.01" required></div>
        <div class="col-md-2"><input type="number" step="0.01" name="items[${lineIndex}][unit_cost]" class="form-control" placeholder="Unit cost" min="0" required></div>
        <div class="col-md-2"><input type="text" name="items[${lineIndex}][batch_number]" class="form-control" placeholder="Batch"></div>
        <div class="col-md-2"><input type="date" name="items[${lineIndex}][expiry_date]" class="form-control"></div>
    </div>`;
    document.getElementById('lineItems').insertAdjacentHTML('beforeend', html);
    lineIndex++;
}
</script>
@endpush
@endsection
