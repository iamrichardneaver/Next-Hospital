@php
    $isEdit = isset($setting);
    $values = $isEdit ? $setting : null;
@endphp

<div class="row mb-3">
    @unless($isEdit)
    <div class="col-md-6">
        <label class="form-label fw-bold">Entity Type <span class="text-danger">*</span></label>
        <select name="entity_type" class="form-select @error('entity_type') is-invalid @enderror" required>
            <option value="">-- Select Entity Type --</option>
            @foreach($availableTypes as $type => $label)
                <option value="{{ $type }}" {{ old('entity_type') === $type ? 'selected' : '' }}>
                    {{ $label }} ({{ $type }})
                </option>
            @endforeach
        </select>
        @error('entity_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">The system entity this ID pattern applies to</small>
    </div>
    @endunless

    <div class="col-md-{{ $isEdit ? '12' : '6' }}">
        <label class="form-label fw-bold">Description</label>
        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
               value="{{ old('description', $values->description ?? '') }}" maxlength="255"
               placeholder="e.g. Patient ID pattern: HWC/PAT/YYYYMMDD/00001">
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label fw-bold">Company Prefix <span class="text-danger">*</span></label>
        <input type="text" name="company_prefix" class="form-control @error('company_prefix') is-invalid @enderror"
               value="{{ old('company_prefix', $values->company_prefix ?? 'HWC') }}" maxlength="10" required>
        @error('company_prefix')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">e.g. HWC, NH, GH</small>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold">Module Prefix <span class="text-danger">*</span></label>
        <input type="text" name="module_prefix" class="form-control @error('module_prefix') is-invalid @enderror"
               value="{{ old('module_prefix', $values->module_prefix ?? '') }}" maxlength="10" required>
        @error('module_prefix')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">e.g. PAT, INV, LAB</small>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold">Separator <span class="text-danger">*</span></label>
        <input type="text" name="separator" class="form-control @error('separator') is-invalid @enderror"
               value="{{ old('separator', $values->separator ?? '/') }}" maxlength="5" required>
        @error('separator')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">Character between pattern parts</small>
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-bold">Pattern <span class="text-danger">*</span></label>
    <input type="text" name="pattern" id="pattern" class="form-control @error('pattern') is-invalid @enderror"
           value="{{ old('pattern', $values->pattern ?? '{company_prefix}/{module_prefix}/{year}{month}{day}/{sequence}') }}" required>
    @error('pattern')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small class="text-muted">
        Use placeholders: <code>{company_prefix}</code>, <code>{module_prefix}</code>,
        <code>{year}</code>, <code>{month}</code>, <code>{day}</code>, <code>{sequence}</code>
    </small>
</div>

@if(!empty($patternExamples))
<div class="mb-3">
    <label class="form-label fw-bold">Apply Example Pattern</label>
    <select id="patternExample" class="form-select">
        <option value="">-- Choose an example --</option>
        @foreach($patternExamples as $example => $pattern)
            <option value="{{ $pattern }}">{{ $example }}</option>
        @endforeach
    </select>
</div>
@endif

<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label fw-bold">Sequence Length <span class="text-danger">*</span></label>
        <input type="number" name="sequence_length" class="form-control @error('sequence_length') is-invalid @enderror"
               value="{{ old('sequence_length', $values->sequence_length ?? 5) }}" min="1" max="10" required>
        @error('sequence_length')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">Number of digits (e.g. 5 = 00001)</small>
    </div>
    <div class="col-md-8">
        <label class="form-label fw-bold">Date Components</label>
        <div class="d-flex flex-wrap gap-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="include_year" id="include_year" value="1"
                       {{ old('include_year', $values->include_year ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="include_year">Include Year</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="include_month" id="include_month" value="1"
                       {{ old('include_month', $values->include_month ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="include_month">Include Month</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="include_day" id="include_day" value="1"
                       {{ old('include_day', $values->include_day ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="include_day">Include Day</label>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const exampleSelect = document.getElementById('patternExample');
    const patternInput = document.getElementById('pattern');

    if (exampleSelect && patternInput) {
        exampleSelect.addEventListener('change', function() {
            if (this.value) {
                patternInput.value = this.value;
            }
        });
    }
});
</script>
@endpush
