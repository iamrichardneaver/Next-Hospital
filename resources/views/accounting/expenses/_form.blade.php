@php
    $isStaffSubmit = $isStaffSubmit ?? false;
    $lockedDepartment = $lockedDepartment ?? null;
@endphp
<div class="row g-3">
    @if($isStaffSubmit && $lockedDepartment)
    <div class="col-md-6">
        <label class="form-label">Department</label>
        <input type="text" class="form-control" value="{{ \App\Models\Expense::DEPARTMENTS[$lockedDepartment] ?? ucfirst($lockedDepartment) }}" readonly disabled>
    </div>
    @elseif(!empty($departments) && !($isStaffSubmit ?? false))
    <div class="col-md-6">
        <label class="form-label">Department</label>
        <select name="department" class="form-select @error('department') is-invalid @enderror">
            <option value="">— General —</option>
            @foreach($departments as $code => $label)
                <option value="{{ $code }}" {{ old('department', $expense->department ?? '') === $code ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @endif
    <div class="col-md-6">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
            <option value="">Select category</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ old('category_id', $expense->category_id ?? '') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @if(auth()->user()->hasRole('super_admin') && !($isStaffSubmit ?? false))
    <div class="col-md-6">
        <label class="form-label">Branch <span class="text-danger">*</span></label>
        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
            <option value="">Select branch</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ old('branch_id', $expense->branch_id ?? $defaultBranchId ?? '') == $branch->id ? 'selected' : '' }}>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>
        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @endif
    <div class="col-md-4">
        <label class="form-label">Amount (GH₵) <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror"
               value="{{ old('amount', $expense->amount ?? '') }}" required>
        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Expense Date <span class="text-danger">*</span></label>
        <input type="date" name="expense_date" class="form-control @error('expense_date') is-invalid @enderror"
               value="{{ old('expense_date', isset($expense) ? $expense->expense_date?->format('Y-m-d') : now()->toDateString()) }}" required>
        @error('expense_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Payment Method</label>
        <select name="payment_method" class="form-select">
            <option value="">— Not specified —</option>
            @foreach($paymentMethods as $method)
                <option value="{{ $method->value }}" {{ old('payment_method', $expense->payment_method ?? '') === $method->value ? 'selected' : '' }}>
                    {{ $method->label() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8">
        <label class="form-label">Description <span class="text-danger">*</span></label>
        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
               value="{{ old('description', $expense->description ?? '') }}" required maxlength="500"
               placeholder="e.g. Courier fee for urgent drug delivery">
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Vendor / Payee</label>
        <input type="text" name="vendor" class="form-control" value="{{ old('vendor', $expense->vendor ?? '') }}" maxlength="150">
    </div>
    <div class="col-md-4">
        <label class="form-label">Receipt / Reference #</label>
        <input type="text" name="reference" class="form-control" value="{{ old('reference', $expense->reference ?? '') }}" maxlength="100">
    </div>
    <div class="col-md-8">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2" maxlength="1000">{{ old('notes', $expense->notes ?? '') }}</textarea>
    </div>
</div>
