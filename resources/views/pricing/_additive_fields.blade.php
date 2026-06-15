<div class="col-12">
    <div class="alert alert-info mb-0">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Additive pricing model:</strong>
        Module prices (lab tests, drugs, radiology studies, appointment fees) come from their own tables.
        <strong>Module Fees</strong> are administrative charges stacked on top when assigned to a module.
        <strong>Appointment Fees</strong> (native slot/doctor pricing) are separate from module fees — both can apply at booking when configured.
        Consultation module fees apply at <em>visit check-in</em>, not when the same fee is already charged at <em>appointment booked</em>.
    </div>
</div>

<div class="col-md-6">
    <label for="pricing_type" class="form-label">Pricing Type <span class="text-danger">*</span></label>
    <select class="form-select @error('pricing_type') is-invalid @enderror"
            id="pricing_type"
            name="pricing_type"
            required>
        @foreach($pricingTypes as $key => $label)
            <option value="{{ $key }}" {{ old('pricing_type', $pricing->pricing_type ?? 'module_fee') == $key ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <small class="text-muted">Module Fee = added on top. Item Override = replaces item price. Standalone = fixed charge only.</small>
    @error('pricing_type')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-6" id="applies_on_wrapper">
    <label for="applies_on" class="form-label">Applies On (optional)</label>
    <select class="form-select @error('applies_on') is-invalid @enderror" id="applies_on" name="applies_on">
        <option value="">Any / Not specified</option>
        @foreach($appliesOnOptions as $key => $label)
            <option value="{{ $key }}" {{ old('applies_on', $pricing->applies_on ?? '') == $key ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('applies_on')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="col-12" id="module_codes_wrapper">
    <label class="form-label">Assign to Modules</label>
    <div class="row g-2">
        @php
            $selectedModules = old('module_codes', $pricing->module_codes ?? []);
        @endphp
        @foreach($moduleCodes as $code)
            <div class="col-md-3 col-sm-4">
                <div class="form-check">
                    <input class="form-check-input module-code-checkbox"
                           type="checkbox"
                           name="module_codes[]"
                           value="{{ $code }}"
                           id="module_{{ $code }}"
                           {{ in_array($code, $selectedModules, true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="module_{{ $code }}">
                        {{ ucfirst($code) }}
                    </label>
                </div>
            </div>
        @endforeach
    </div>
    <small class="text-muted">Select which modules receive this fee. Use <code>Applies On</code> to control when the fee fires (prevents double-charging). Service ID e.g. <code>module_fee_lab</code>.</small>
    @error('module_codes')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-6" id="is_additive_wrapper">
    <div class="form-check form-switch">
        <input class="form-check-input"
               type="checkbox"
               id="is_additive"
               name="is_additive"
               value="1"
               {{ old('is_additive', $pricing->is_additive ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_additive">
            Additive (stack on module prices)
        </label>
    </div>
    <small class="text-muted">Enabled for module fees. Disabled for item overrides.</small>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pricingType = document.getElementById('pricing_type');
    const moduleWrapper = document.getElementById('module_codes_wrapper');
    const additiveWrapper = document.getElementById('is_additive_wrapper');
    const serviceIdInput = document.getElementById('service_id');

    function syncPricingTypeUI() {
        const type = pricingType.value;
        const isModuleFee = type === 'module_fee';
        const isItemOverride = type === 'item_override';

        moduleWrapper.style.display = isModuleFee ? 'block' : 'none';
        additiveWrapper.style.display = (isModuleFee || isItemOverride) ? 'block' : 'none';

        if (isModuleFee) {
            document.getElementById('is_additive').checked = true;
        } else if (isItemOverride) {
            document.getElementById('is_additive').checked = false;
        }
    }

    pricingType.addEventListener('change', syncPricingTypeUI);
    syncPricingTypeUI();

    document.querySelectorAll('.module-code-checkbox').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            if (pricingType.value !== 'module_fee' || !serviceIdInput || serviceIdInput.value.trim() !== '') {
                return;
            }
            const checked = Array.from(document.querySelectorAll('.module-code-checkbox:checked')).map(c => c.value);
            if (checked.length === 1) {
                serviceIdInput.value = 'module_fee_' + checked[0];
            }
        });
    });
});
</script>
@endpush
