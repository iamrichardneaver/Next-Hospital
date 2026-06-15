{{--
    Shared payment method selector.
    Options: $idPrefix (default 'pm'), $showPaystack (default true), $selected (old value)
--}}
@php
    $idPrefix = $idPrefix ?? 'pm';
    $showPaystack = $showPaystack ?? true;
    $selected = $selected ?? old('payment_method');
    $required = $required ?? true;
@endphp

<div class="payment-method-fields" data-prefix="{{ $idPrefix }}">
    <div class="mb-3">
        <label for="{{ $idPrefix }}_payment_method" class="form-label">
            Payment Method @if($required)<span class="text-danger">*</span>@endif
        </label>
        <select class="form-select payment-method-select" id="{{ $idPrefix }}_payment_method" name="payment_method" @if($required) required @endif>
            <option value="">{{ $required ? 'Select Payment Method' : 'Unpaid (invoice only)' }}</option>
            <option value="cash" {{ $selected === 'cash' ? 'selected' : '' }}>Cash</option>
            @if($showPaystack)
            <option value="paystack" {{ $selected === 'paystack' ? 'selected' : '' }}>Paystack (Card / MoMo Online)</option>
            @endif
            <option value="mobile_money_offline" {{ in_array($selected, ['mobile_money_offline', 'momo', 'mobile_money']) ? 'selected' : '' }}>Mobile Money (Offline / USSD)</option>
        </select>
    </div>

    <div class="payment-method-panel payment-method-cash" id="{{ $idPrefix }}_cash_panel" style="display: none;">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="{{ $idPrefix }}_amount_tendered" class="form-label">Amount Tendered</label>
                <input type="number" class="form-control" id="{{ $idPrefix }}_amount_tendered" name="amount_tendered" min="0" step="0.01" placeholder="Cash received">
            </div>
            <div class="col-md-6 mb-3">
                <label for="{{ $idPrefix }}_change_due" class="form-label">Change</label>
                <input type="text" class="form-control" id="{{ $idPrefix }}_change_due" readonly placeholder="GH₵0.00">
            </div>
        </div>
    </div>

    <div class="payment-method-panel payment-method-offline-momo" id="{{ $idPrefix }}_offline_momo_panel" style="display: none;">
        <div class="alert alert-info py-2 small mb-3">
            Patient completes USSD on their phone. Enter the transaction reference after confirmation.
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="{{ $idPrefix }}_momo_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="{{ $idPrefix }}_momo_phone" name="momo_phone" placeholder="e.g. 0241234567">
            </div>
            <div class="col-md-6 mb-3">
                <label for="{{ $idPrefix }}_momo_network" class="form-label">Network <span class="text-danger">*</span></label>
                <select class="form-select" id="{{ $idPrefix }}_momo_network" name="momo_network">
                    <option value="">Select network</option>
                    <option value="MTN">MTN</option>
                    <option value="Vodafone">Vodafone</option>
                    <option value="AirtelTigo">AirtelTigo</option>
                </select>
            </div>
            <div class="col-12 mb-3">
                <label for="{{ $idPrefix }}_momo_reference" class="form-label">Transaction Reference <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="{{ $idPrefix }}_momo_reference" name="momo_reference" placeholder="MoMo transaction ID">
            </div>
        </div>
    </div>

    @if($showPaystack)
    <div class="payment-method-panel payment-method-paystack" id="{{ $idPrefix }}_paystack_panel" style="display: none;">
        <div class="alert alert-primary py-2 small mb-0">
            You will be redirected to Paystack to complete payment (card or mobile money).
        </div>
    </div>
    @endif
</div>

@once
@push('scripts')
<script>
(function () {
    function initPaymentMethodFields(root) {
        const prefix = root.dataset.prefix || 'pm';
        const select = root.querySelector('.payment-method-select');
        const cashPanel = document.getElementById(prefix + '_cash_panel');
        const offlinePanel = document.getElementById(prefix + '_offline_momo_panel');
        const paystackPanel = document.getElementById(prefix + '_paystack_panel');
        const amountTendered = document.getElementById(prefix + '_amount_tendered');
        const changeDue = document.getElementById(prefix + '_change_due');

        function togglePanels() {
            const method = select ? select.value : '';
            if (cashPanel) cashPanel.style.display = method === 'cash' ? 'block' : 'none';
            if (offlinePanel) offlinePanel.style.display = (method === 'mobile_money_offline' || method === 'momo') ? 'block' : 'none';
            if (paystackPanel) paystackPanel.style.display = method === 'paystack' ? 'block' : 'none';
        }

        function updateChange() {
            if (!amountTendered || !changeDue) return;
            const due = parseFloat(root.dataset.amountDue || '0');
            const tendered = parseFloat(amountTendered.value || '0');
            const change = tendered - due;
            changeDue.value = change >= 0 ? 'GH₵' + change.toFixed(2) : 'Short GH₵' + Math.abs(change).toFixed(2);
        }

        if (select) {
            select.addEventListener('change', togglePanels);
            togglePanels();
        }
        if (amountTendered) {
            amountTendered.addEventListener('input', updateChange);
        }
        root._getPaymentExtras = function () {
            const method = select ? select.value : '';
            const extras = { payment_method: method };
            if (method === 'cash' && amountTendered) {
                extras.amount_tendered = parseFloat(amountTendered.value || '0');
                extras.change_due = parseFloat(extras.amount_tendered) - parseFloat(root.dataset.amountDue || '0');
            }
            if (method === 'mobile_money_offline' || method === 'momo') {
                extras.momo_phone = document.getElementById(prefix + '_momo_phone')?.value || '';
                extras.momo_network = document.getElementById(prefix + '_momo_network')?.value || '';
                extras.momo_reference = document.getElementById(prefix + '_momo_reference')?.value || '';
                extras.reference_number = extras.momo_reference;
            }
            return extras;
        };
        root._validatePaymentMethod = function (amountDue) {
            root.dataset.amountDue = String(amountDue || 0);
            updateChange();
            const method = select ? select.value : '';
            if (!method) {
                alert('Please select a payment method');
                return false;
            }
            if (method === 'mobile_money_offline' || method === 'momo') {
                const ref = document.getElementById(prefix + '_momo_reference')?.value?.trim();
                const phone = document.getElementById(prefix + '_momo_phone')?.value?.trim();
                const network = document.getElementById(prefix + '_momo_network')?.value;
                if (!ref || !phone || !network) {
                    alert('Offline mobile money requires phone, network, and transaction reference.');
                    return false;
                }
            }
            if (method === 'cash' && amountTendered) {
                const tendered = parseFloat(amountTendered.value || '0');
                if (tendered < parseFloat(amountDue || 0)) {
                    alert('Amount tendered is less than amount due.');
                    return false;
                }
            }
            return true;
        };
    }

    document.querySelectorAll('.payment-method-fields').forEach(initPaymentMethodFields);
    window.initPaymentMethodFields = initPaymentMethodFields;
})();
</script>
@endpush
@endonce
