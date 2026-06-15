/**
 * Print Format Selector Component
 * Provides a reusable interface for selecting print formats and generating documents
 */
class PrintFormatSelector {
    constructor(options = {}) {
        this.container = options.container || document.body;
        this.onFormatChange = options.onFormatChange || null;
        this.onPrint = options.onPrint || null;
        this.defaultFormat = options.defaultFormat || 'a4';
        this.formats = [];
        this.currentFormat = this.defaultFormat;
        this.settings = {};
        
        this.init();
    }

    async init() {
        await this.loadFormats();
        await this.loadSettings();
        this.render();
        this.bindEvents();
    }

    async loadFormats() {
        try {
            const response = await fetch('/api/print-settings/formats', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': 'Bearer ' + this.getAuthToken()
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.formats = data.data;
            } else {
                console.error('Failed to load print formats');
                this.formats = this.getDefaultFormats();
            }
        } catch (error) {
            console.error('Error loading print formats:', error);
            this.formats = this.getDefaultFormats();
        }
    }

    async loadSettings() {
        try {
            const response = await fetch('/api/print-settings', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': 'Bearer ' + this.getAuthToken()
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.settings = data.data;
                if (this.settings.default_format) {
                    this.currentFormat = this.settings.default_format;
                }
            }
        } catch (error) {
            console.error('Error loading print settings:', error);
        }
    }

    getDefaultFormats() {
        return [
            {
                value: 'a4',
                label: 'A4 Paper',
                description: 'Standard A4 size for regular printers',
                icon: 'fas fa-file-alt'
            },
            {
                value: 'thermal_80mm',
                label: 'Thermal 80mm',
                description: '80mm thermal printer receipt format',
                icon: 'fas fa-receipt'
            },
            {
                value: 'thermal_58mm',
                label: 'Thermal 58mm',
                description: '58mm thermal printer receipt format',
                icon: 'fas fa-receipt'
            }
        ];
    }

    render() {
        const selectorHtml = `
            <div class="print-format-selector">
                <div class="print-format-dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-print me-2"></i>
                        <span class="format-label">Print Format</span>
                        <i class="fas fa-chevron-down ms-2"></i>
                    </button>
                    <ul class="dropdown-menu print-format-menu">
                        ${this.formats.map(format => `
                            <li>
                                <a class="dropdown-item format-option ${format.value === this.currentFormat ? 'active' : ''}" 
                                   href="#" data-format="${format.value}">
                                    <div class="d-flex align-items-center">
                                        <i class="${format.icon} me-3"></i>
                                        <div>
                                            <div class="fw-bold">${format.label}</div>
                                            <small class="text-muted">${format.description}</small>
                                        </div>
                                        ${format.value === this.currentFormat ? '<i class="fas fa-check ms-auto text-success"></i>' : ''}
                                    </div>
                                </a>
                            </li>
                        `).join('')}
                    </ul>
                </div>
                
                <div class="print-actions ms-2">
                    <button class="btn btn-primary print-btn" type="button">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    <button class="btn btn-outline-secondary preview-btn" type="button">
                        <i class="fas fa-eye me-1"></i>Preview
                    </button>
                </div>
                
                <div class="print-format-info ms-2">
                    <small class="text-muted format-description"></small>
                </div>
            </div>
        `;

        this.container.innerHTML = selectorHtml;
        this.updateFormatInfo();
    }

    bindEvents() {
        // Format selection
        this.container.querySelectorAll('.format-option').forEach(option => {
            option.addEventListener('click', (e) => {
                e.preventDefault();
                const format = e.currentTarget.dataset.format;
                this.selectFormat(format);
            });
        });

        // Print button
        this.container.querySelector('.print-btn').addEventListener('click', () => {
            this.handlePrint();
        });

        // Preview button
        this.container.querySelector('.preview-btn').addEventListener('click', () => {
            this.handlePreview();
        });
    }

    selectFormat(format) {
        this.currentFormat = format;
        
        // Update UI
        this.container.querySelectorAll('.format-option').forEach(option => {
            option.classList.remove('active');
            if (option.dataset.format === format) {
                option.classList.add('active');
                option.querySelector('.fas.fa-check')?.remove();
                option.querySelector('.d-flex').innerHTML += '<i class="fas fa-check ms-auto text-success"></i>';
            } else {
                option.querySelector('.fas.fa-check')?.remove();
            }
        });

        // Update format label and description
        const selectedFormat = this.formats.find(f => f.value === format);
        if (selectedFormat) {
            this.container.querySelector('.format-label').textContent = selectedFormat.label;
            this.updateFormatInfo();
        }

        // Trigger callback
        if (this.onFormatChange) {
            this.onFormatChange(format, selectedFormat);
        }
    }

    updateFormatInfo() {
        const selectedFormat = this.formats.find(f => f.value === this.currentFormat);
        if (selectedFormat) {
            this.container.querySelector('.format-description').textContent = selectedFormat.description;
        }
    }

    async handlePrint() {
        if (this.onPrint) {
            await this.onPrint(this.currentFormat);
        } else {
            // Default print behavior
            await this.defaultPrint(this.currentFormat);
        }
    }

    async handlePreview() {
        try {
            const previewUrl = this.getPreviewUrl();
            if (previewUrl) {
                window.open(previewUrl, '_blank', 'width=800,height=600');
            } else {
                this.showAlert('Preview not available', 'warning');
            }
        } catch (error) {
            console.error('Error opening preview:', error);
            this.showAlert('Error opening preview', 'danger');
        }
    }

    async defaultPrint(format) {
        try {
            this.showLoading();
            
            const printUrl = this.getPrintUrl(format);
            if (printUrl) {
                // Create hidden iframe for printing
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = printUrl;
                document.body.appendChild(iframe);
                
                iframe.onload = () => {
                    setTimeout(() => {
                        iframe.contentWindow.print();
                        document.body.removeChild(iframe);
                        this.hideLoading();
                    }, 1000);
                };
            } else {
                this.showAlert('Print URL not available', 'warning');
                this.hideLoading();
            }
        } catch (error) {
            console.error('Error printing:', error);
            this.showAlert('Error printing document', 'danger');
            this.hideLoading();
        }
    }

    getPrintUrl(format) {
        // This should be overridden by the parent component
        // or passed as an option
        return null;
    }

    getPreviewUrl() {
        // This should be overridden by the parent component
        // or passed as an option
        return null;
    }

    showLoading() {
        const btn = this.container.querySelector('.print-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Printing...';
        }
    }

    hideLoading() {
        const btn = this.container.querySelector('.print-btn');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-print me-1"></i>Print';
        }
    }

    showAlert(message, type = 'info') {
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

    getAuthToken() {
        // Get auth token from localStorage or meta tag
        return localStorage.getItem('auth_token') || 
               document.querySelector('meta[name="csrf-token"]')?.content || 
               '';
    }

    // Public methods
    setPrintUrl(urlFunction) {
        this.getPrintUrl = urlFunction;
    }

    setPreviewUrl(urlFunction) {
        this.getPreviewUrl = urlFunction;
    }

    getCurrentFormat() {
        return this.currentFormat;
    }

    setCurrentFormat(format) {
        if (this.formats.find(f => f.value === format)) {
            this.selectFormat(format);
        }
    }
}

// Invoice Print Selector - Specific implementation for invoices
class InvoicePrintSelector extends PrintFormatSelector {
    constructor(invoiceId, options = {}) {
        super(options);
        this.invoiceId = invoiceId;
        this.onPrint = options.onPrint || this.defaultInvoicePrint.bind(this);
    }

    async defaultInvoicePrint(format) {
        try {
            this.showLoading();
            
            const response = await fetch(`/api/print-settings/invoices/${this.invoiceId}/print?format=${format}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': 'Bearer ' + this.getAuthToken()
                }
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `invoice_${this.invoiceId}_${format}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                const error = await response.json();
                this.showAlert(error.message || 'Error generating invoice', 'danger');
            }
        } catch (error) {
            console.error('Error printing invoice:', error);
            this.showAlert('Error printing invoice', 'danger');
        } finally {
            this.hideLoading();
        }
    }

    getPreviewUrl() {
        return `/api/print-settings/invoices/${this.invoiceId}/preview?format=${this.currentFormat}`;
    }
}

// Receipt Print Selector - Specific implementation for receipts
class ReceiptPrintSelector extends PrintFormatSelector {
    constructor(invoiceId, options = {}) {
        super(options);
        this.invoiceId = invoiceId;
        this.onPrint = options.onPrint || this.defaultReceiptPrint.bind(this);
    }

    async defaultReceiptPrint(format) {
        try {
            this.showLoading();
            
            const response = await fetch(`/api/print-settings/receipts/${this.invoiceId}/print?format=${format}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': 'Bearer ' + this.getAuthToken()
                }
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `receipt_${this.invoiceId}_${format}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                const error = await response.json();
                this.showAlert(error.message || 'Error generating receipt', 'danger');
            }
        } catch (error) {
            console.error('Error printing receipt:', error);
            this.showAlert('Error printing receipt', 'danger');
        } finally {
            this.hideLoading();
        }
    }

    getPreviewUrl() {
        return `/api/print-settings/receipts/${this.invoiceId}/preview?format=${this.currentFormat}`;
    }
}

// Export for use in other scripts
window.PrintFormatSelector = PrintFormatSelector;
window.InvoicePrintSelector = InvoicePrintSelector;
window.ReceiptPrintSelector = ReceiptPrintSelector;
