@extends('layouts.app')

@section('title', 'Print Settings')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-print me-2"></i>Print Settings
                    </h1>
                    <p class="text-muted mb-0">Configure printing formats for invoices and receipts</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" onclick="saveSettings()">
                        <i class="fas fa-save me-1"></i>Save Settings
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Format Selection -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog me-2"></i>Print Format Configuration
                    </h5>
                </div>
                <div class="card-body">
                    <form id="printSettingsForm">
                        <!-- Default Format Selection -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label fw-bold">Default Print Format</label>
                                <div class="row">
                                    @foreach($formats as $format)
                                    <div class="col-md-4 mb-3">
                                        <div class="card format-option" data-format="{{ $format['value'] }}">
                                            <div class="card-body text-center p-3">
                                                <div class="format-icon mb-2">
                                                    <i class="{{ $format['icon'] }} fa-2x text-primary"></i>
                                                </div>
                                                <h6 class="card-title">{{ $format['label'] }}</h6>
                                                <p class="card-text small text-muted">{{ $format['description'] }}</p>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="default_format" 
                                                           value="{{ $format['value'] }}" id="format_{{ $format['value'] }}">
                                                    <label class="form-check-label" for="format_{{ $format['value'] }}">
                                                        Set as Default
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Additional Settings -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="auto_print" class="form-label fw-bold">Auto Print</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="auto_print" name="auto_print">
                                        <label class="form-check-label" for="auto_print">
                                            Automatically print after generation
                                        </label>
                                    </div>
                                    <div class="form-text">Enable automatic printing after generating invoices/receipts</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="printer_name" class="form-label fw-bold">Default Printer Name</label>
                                    <input type="text" class="form-control" id="printer_name" name="printer_name" 
                                           placeholder="Enter printer name (optional)">
                                    <div class="form-text">Specify default printer for automatic printing</div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Preview Section -->
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-eye me-2"></i>Format Preview
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadPreview()">
                            <i class="fas fa-refresh me-1"></i>Refresh Preview
                        </button>
                    </div>
                    
                    <!-- Format Preview Cards -->
                    <div id="formatPreview">
                        @foreach($formats as $format)
                        <div class="preview-card mb-3" id="preview_{{ $format['value'] }}" style="display: none;">
                            <div class="card border">
                                <div class="card-body p-2">
                                    <div class="text-center">
                                        <i class="{{ $format['icon'] }} fa-lg text-primary mb-2"></i>
                                        <h6 class="card-title small">{{ $format['label'] }}</h6>
                                        <div class="preview-content">
                                            @if($format['value'] === 'a4')
                                                <div class="preview-a4 bg-light p-2 rounded">
                                                    <div class="text-center">
                                                        <strong>Hospital Name</strong><br>
                                                        <small>Address, Phone, Email</small><br>
                                                        <hr class="my-1">
                                                        <strong>INVOICE/RECEIPT</strong><br>
                                                        <small>Patient: John Doe</small><br>
                                                        <small>Amount: GHS 100.00</small><br>
                                                        <hr class="my-1">
                                                        <small>Thank you!</small>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="preview-thermal bg-light p-1 rounded" style="font-family: monospace; font-size: 10px;">
                                                    <div class="text-center">
                                                        <strong>HOSPITAL NAME</strong><br>
                                                        Address, Phone<br>
                                                        ----------------<br>
                                                        <strong>RECEIPT</strong><br>
                                                        ----------------<br>
                                                        Patient: John Doe<br>
                                                        Amount: GHS 100.00<br>
                                                        ----------------<br>
                                                        Thank you!
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Print Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-vial me-2"></i>Test Print
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Test Invoice Print</h6>
                            <p class="text-muted">Generate a test invoice with your selected format</p>
                            <button type="button" class="btn btn-outline-success" onclick="testInvoicePrint()">
                                <i class="fas fa-file-invoice me-1"></i>Test Invoice
                            </button>
                        </div>
                        <div class="col-md-6">
                            <h6>Test Receipt Print</h6>
                            <p class="text-muted">Generate a test receipt with your selected format</p>
                            <button type="button" class="btn btn-outline-success" onclick="testReceiptPrint()">
                                <i class="fas fa-receipt me-1"></i>Test Receipt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0">Generating document...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let currentSettings = {};

// Load settings on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSettings();
    loadPreview();
    
    // Add click handlers for format selection
    document.querySelectorAll('.format-option').forEach(card => {
        card.addEventListener('click', function() {
            const format = this.dataset.format;
            document.querySelector(`#format_${format}`).checked = true;
            updatePreview();
        });
    });
});

// Load current settings
async function loadSettings() {
    try {
        const response = await fetch('/api/print-settings', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Authorization': 'Bearer ' + getAuthToken()
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            currentSettings = data.data;
            
            // Set form values
            if (currentSettings.default_format) {
                document.querySelector(`#format_${currentSettings.default_format}`).checked = true;
            }
            document.getElementById('auto_print').checked = currentSettings.auto_print || false;
            document.getElementById('printer_name').value = currentSettings.printer_name || '';
        }
    } catch (error) {
        console.error('Error loading settings:', error);
        showAlert('Error loading settings', 'danger');
    }
}

// Save settings
async function saveSettings() {
    const form = document.getElementById('printSettingsForm');
    const formData = new FormData(form);
    
    const settings = {
        default_format: formData.get('default_format'),
        auto_print: formData.get('auto_print') === 'on',
        printer_name: formData.get('printer_name')
    };
    
    try {
        showLoading();
        
        const response = await fetch('/api/print-settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Authorization': 'Bearer ' + getAuthToken()
            },
            body: JSON.stringify(settings)
        });
        
        if (response.ok) {
            const data = await response.json();
            currentSettings = data.data;
            showAlert('Settings saved successfully!', 'success');
            updatePreview();
        } else {
            const error = await response.json();
            showAlert(error.message || 'Error saving settings', 'danger');
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showAlert('Error saving settings', 'danger');
    } finally {
        hideLoading();
    }
}

// Load preview
function loadPreview() {
    updatePreview();
}

// Update preview based on selected format
function updatePreview() {
    const selectedFormat = document.querySelector('input[name="default_format"]:checked');
    
    if (selectedFormat) {
        // Hide all previews
        document.querySelectorAll('.preview-card').forEach(card => {
            card.style.display = 'none';
        });
        
        // Show selected preview
        const previewCard = document.getElementById(`preview_${selectedFormat.value}`);
        if (previewCard) {
            previewCard.style.display = 'block';
        }
    }
}

// Test invoice print
async function testInvoicePrint() {
    const selectedFormat = document.querySelector('input[name="default_format"]:checked');
    if (!selectedFormat) {
        showAlert('Please select a default format first', 'warning');
        return;
    }
    
    try {
        showLoading();
        
        // Create a test invoice or use existing one
        const response = await fetch(`/api/print-settings/test-invoice?format=${selectedFormat.value}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Authorization': 'Bearer ' + getAuthToken()
            }
        });
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `test_invoice_${selectedFormat.value}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } else {
            const error = await response.json();
            showAlert(error.message || 'Error generating test invoice', 'danger');
        }
    } catch (error) {
        console.error('Error testing invoice print:', error);
        showAlert('Error testing invoice print', 'danger');
    } finally {
        hideLoading();
    }
}

// Test receipt print
async function testReceiptPrint() {
    const selectedFormat = document.querySelector('input[name="default_format"]:checked');
    if (!selectedFormat) {
        showAlert('Please select a default format first', 'warning');
        return;
    }
    
    try {
        showLoading();
        
        // Create a test receipt or use existing one
        const response = await fetch(`/api/print-settings/test-receipt?format=${selectedFormat.value}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Authorization': 'Bearer ' + getAuthToken()
            }
        });
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `test_receipt_${selectedFormat.value}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } else {
            const error = await response.json();
            showAlert(error.message || 'Error generating test receipt', 'danger');
        }
    } catch (error) {
        console.error('Error testing receipt print:', error);
        showAlert('Error testing receipt print', 'danger');
    } finally {
        hideLoading();
    }
}

// Utility functions
function showLoading() {
    const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
    modal.show();
}

function hideLoading() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
    if (modal) {
        modal.hide();
    }
}

function showAlert(message, type = 'info') {
    // Create and show alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}

function getAuthToken() {
    // Get auth token from localStorage or cookie
    return localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.content || '';
}
</script>

<style>
.format-option {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.format-option:hover {
    border-color: #007bff;
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.15);
}

.format-option.selected {
    border-color: #007bff;
    background-color: rgba(0, 123, 255, 0.05);
}

.preview-a4 {
    min-height: 200px;
    border: 1px solid #dee2e6;
}

.preview-thermal {
    min-height: 150px;
    border: 1px solid #dee2e6;
    font-family: 'Courier New', monospace;
    font-size: 10px;
    line-height: 1.2;
}

.format-icon {
    color: #6c757d;
}

.format-option:hover .format-icon {
    color: #007bff;
}

.card {
    transition: box-shadow 0.15s ease-in-out;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
</style>
@endsection
