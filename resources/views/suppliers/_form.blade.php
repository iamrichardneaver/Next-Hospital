@php
    $typeLabels = [
        'pharmacy' => 'Pharmacy',
        'laboratory' => 'Laboratory',
        'radiology' => 'Radiology',
        'both' => 'Pharmacy & Lab',
        'general' => 'General',
        'equipment' => 'Equipment',
        'reagent' => 'Reagent',
        'consumable' => 'Consumable',
    ];
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $supplier->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Supplier Type <span class="text-danger">*</span></label>
        <select name="supplier_type" class="form-select @error('supplier_type') is-invalid @enderror" required>
            @foreach($allowedTypes as $type)
                <option value="{{ $type }}" {{ old('supplier_type', $supplier->supplier_type ?? ($defaultType ?? '')) === $type ? 'selected' : '' }}>
                    {{ $typeLabels[$type] ?? ucfirst($type) }}
                </option>
            @endforeach
        </select>
        @error('supplier_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Contact Person</label>
        <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $supplier->contact_person ?? '') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $supplier->phone ?? '') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $supplier->email ?? '') }}">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Tax ID</label>
        <input type="text" name="tax_id" class="form-control" value="{{ old('tax_id', $supplier->tax_id ?? '') }}">
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2">{{ old('address', $supplier->address ?? '') }}</textarea>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control" value="{{ old('city', $supplier->city ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">State/Region</label>
        <input type="text" name="state" class="form-control" value="{{ old('state', $supplier->state ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Country</label>
        <input type="text" name="country" class="form-control" value="{{ old('country', $supplier->country ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Payment Terms</label>
        <input type="text" name="payment_terms" class="form-control" placeholder="e.g. Net 30" value="{{ old('payment_terms', $supplier->payment_terms ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Delivery Terms</label>
        <input type="text" name="delivery_terms" class="form-control" value="{{ old('delivery_terms', $supplier->delivery_terms ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Website</label>
        <input type="url" name="website" class="form-control" value="{{ old('website', $supplier->website ?? '') }}">
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $supplier->notes ?? '') }}</textarea>
    </div>
</div>
