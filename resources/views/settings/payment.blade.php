@extends('layouts.app')

@section('title', 'Payment Settings')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-credit-card me-2"></i>Payment Gateway Settings
            </h1>
            <p class="text-secondary mb-0">Configure Paystack payment gateway for online payments</p>
        </div>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Settings
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form action="{{ route('settings.payment.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Paystack Configuration -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-wallet2 me-2"></i>Paystack Configuration
                </h5>
            </div>
            <div class="card-body">
                <!-- Environment Selection -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Environment <span class="text-danger">*</span></label>
                        <select class="form-select" name="environment" id="environment" required>
                            <option value="sandbox" {{ ($paymentSettings->environment ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>
                                Test/Sandbox
                            </option>
                            <option value="live" {{ ($paymentSettings->environment ?? '') === 'live' ? 'selected' : '' }}>
                                Live/Production
                            </option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Use "Test/Sandbox" for testing, "Live/Production" for real payments
                        </small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                   {{ ($paymentSettings->is_active ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Enable Paystack Payments
                            </label>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Turn on to accept Paystack payments
                        </small>
                    </div>
                </div>

                <!-- API Keys -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Public Key <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="text" class="form-control" name="public_key" id="public_key"
                                   value="{{ $paymentSettings->public_key ?? '' }}" 
                                   placeholder="pk_test_xxxxxx or pk_live_xxxxxx" required>
                        </div>
                        <small class="text-muted">
                            Get this from your Paystack Dashboard → Settings → API Keys
                        </small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Secret Key <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" class="form-control" name="secret_key" id="secret_key"
                                   value="{{ $paymentSettings->secret_key ?? '' }}" 
                                   placeholder="sk_test_xxxxxx or sk_live_xxxxxx" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleSecretKey()">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <small class="text-muted">
                            Get this from your Paystack Dashboard → Settings → API Keys
                        </small>
                    </div>
                </div>

                <!-- Information about Webhook Security -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Webhook Security:</strong> Paystack uses your Secret Key to sign webhook requests. 
                            No separate webhook secret is needed. The system automatically validates webhooks using your Secret Key above.
                        </div>
                    </div>
                </div>

                <!-- Supported Payment Methods -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Supported Payment Methods</label>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="payment_methods[]" value="card" 
                                           id="method_card" checked>
                                    <label class="form-check-label" for="method_card">
                                        <i class="bi bi-credit-card"></i> Card
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="payment_methods[]" value="bank" 
                                           id="method_bank" checked>
                                    <label class="form-check-label" for="method_bank">
                                        <i class="bi bi-bank"></i> Bank Transfer
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="payment_methods[]" value="mobile_money" 
                                           id="method_mobile" checked>
                                    <label class="form-check-label" for="method_mobile">
                                        <i class="bi bi-phone"></i> Mobile Money
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="payment_methods[]" value="ussd" 
                                           id="method_ussd">
                                    <label class="form-check-label" for="method_ussd">
                                        <i class="bi bi-hash"></i> USSD
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Webhook Configuration (Read-Only) -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="bi bi-link-45deg me-2"></i>Webhook Configuration
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Important:</strong> Copy the webhook URL below and add it to your Paystack Dashboard
                </div>

                <!-- Webhook URL -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Webhook URL (Copy this to Paystack)</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-light" id="webhookUrl" 
                                   value="{{ $webhookUrl }}" readonly>
                            <button class="btn btn-primary" type="button" onclick="copyToClipboard('webhookUrl')">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                        <small class="text-muted">
                            This URL is auto-generated based on your domain. Add this to Paystack Dashboard → Settings → Webhooks
                        </small>
                    </div>
                </div>

                <!-- Callback URL -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Callback URL (Auto-configured)</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-light" id="callbackUrl" 
                                   value="{{ $callbackUrl }}" readonly>
                            <button class="btn btn-primary" type="button" onclick="copyToClipboard('callbackUrl')">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                        <small class="text-muted">
                            This URL is automatically used when initializing payments (no action needed)
                        </small>
                    </div>
                </div>

                <!-- Events to Enable -->
                <div class="row">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Events to Enable in Paystack Dashboard</label>
                        <div class="list-group">
                            <div class="list-group-item">
                                <i class="bi bi-check-circle text-success"></i> charge.success
                                <small class="text-muted float-end">When payment is successful</small>
                            </div>
                            <div class="list-group-item">
                                <i class="bi bi-x-circle text-danger"></i> charge.failed
                                <small class="text-muted float-end">When payment fails</small>
                            </div>
                            <div class="list-group-item">
                                <i class="bi bi-check-circle text-success"></i> transfer.success
                                <small class="text-muted float-end">When transfer succeeds (refunds)</small>
                            </div>
                            <div class="list-group-item">
                                <i class="bi bi-x-circle text-danger"></i> transfer.failed
                                <small class="text-muted float-end">When transfer fails</small>
                            </div>
                            <div class="list-group-item">
                                <i class="bi bi-arrow-counterclockwise text-primary"></i> refund.processed
                                <small class="text-muted float-end">When refund is processed</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Setup Instructions -->
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5 class="mb-0">
                    <i class="bi bi-list-check me-2"></i>Setup Instructions
                </h5>
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    <li class="mb-2">
                        <strong>Login to Paystack:</strong> Go to 
                        <a href="https://dashboard.paystack.com" target="_blank" rel="noopener">
                            dashboard.paystack.com <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </li>
                    <li class="mb-2">
                        <strong>Get API Keys:</strong> Navigate to Settings → API Keys & Webhooks
                    </li>
                    <li class="mb-2">
                        <strong>Copy Keys:</strong> 
                        <ul>
                            <li>For Testing: Copy <code>Test Public Key</code> (pk_test_xxx) and <code>Test Secret Key</code> (sk_test_xxx)</li>
                            <li>For Production: Copy <code>Live Public Key</code> (pk_live_xxx) and <code>Live Secret Key</code> (sk_live_xxx)</li>
                        </ul>
                    </li>
                    <li class="mb-2">
                        <strong>Setup Webhook:</strong>
                        <ul>
                            <li>In Paystack Dashboard, go to Settings → API Keys & Webhooks</li>
                            <li>Scroll to "Webhooks" section</li>
                            <li>Click "Add Webhook URL"</li>
                            <li>Paste the webhook URL from the section above</li>
                            <li>Select all 5 events listed in the "Webhook Configuration" section</li>
                            <li>Click Save (No webhook secret needed - Paystack uses your Secret Key)</li>
                        </ul>
                    </li>
                    <li class="mb-2">
                        <strong>Enter Details:</strong> Fill in Public Key and Secret Key above
                    </li>
                    <li class="mb-2">
                        <strong>Choose Environment:</strong> Select "Test/Sandbox" for testing or "Live/Production" for real payments
                    </li>
                    <li class="mb-2">
                        <strong>Enable Payments:</strong> Toggle "Enable Paystack Payments" to ON
                    </li>
                    <li class="mb-2">
                        <strong>Save Settings:</strong> Click "Save Payment Settings" button below
                    </li>
                </ol>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                <i class="bi bi-x-circle me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Save Payment Settings
            </button>
        </div>
    </form>
</div>

<script>
function toggleSecretKey() {
    const input = document.getElementById('secret_key');
    const icon = document.getElementById('toggleIcon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

function copyToClipboard(elementId) {
    const input = document.getElementById(elementId);
    input.select();
    input.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Show feedback
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
    btn.classList.remove('btn-primary');
    btn.classList.add('btn-success');
    
    setTimeout(() => {
        btn.innerHTML = originalHtml;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-primary');
    }, 2000);
}

// Environment change warning
document.getElementById('environment').addEventListener('change', function() {
    if (this.value === 'live') {
        if (!confirm('⚠️ WARNING: You are switching to LIVE mode. Make sure you have entered LIVE API keys from Paystack. Continue?')) {
            this.value = 'sandbox';
        }
    }
});
</script>
@endsection

