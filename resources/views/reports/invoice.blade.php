<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page_title }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }
        
        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 14pt;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .header {
            border-bottom: 3px solid {{ $branding['primary_color'] ?? '#2c5aa0' }};
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header-content {
            display: table;
            width: 100%;
        }
        
        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        
        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }
        
        .hospital-name {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 23pt;
            font-weight: bold;
            color: {{ $branding['primary_color'] ?? '#2c5aa0' }};
            margin-bottom: 5px;
        }
        
        .hospital-details {
            font-size: 9pt;
            color: #666;
            line-height: 1.3;
        }
        
        .invoice-title {
            font-size: 23pt;
            font-weight: bold;
            color: #2c5aa0;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .invoice-info {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        
        .invoice-info h3 {
            margin: 0 0 10px 0;
            color: #2c5aa0;
            font-size: 14pt;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        
        .info-row {
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            color: #495057;
            display: inline-block;
            width: 120px;
        }
        
        .info-value {
            color: #212529;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th {
            background-color: #2c5aa0;
            color: white;
            font-weight: bold;
            padding: 10px 12px;
            text-align: left;
            border: 1px solid #2c5aa0;
            font-size: 9pt;
        }
        
        .items-table td {
            padding: 10px 12px;
            border: 1px solid #dee2e6;
            font-size: 9pt;
            vertical-align: top;
        }
        
        .items-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .item-description {
            font-weight: bold;
            color: #495057;
        }
        
        .item-quantity {
            text-align: center;
        }
        
        .item-price {
            text-align: right;
        }
        
        .item-total {
            text-align: right;
            font-weight: bold;
        }
        
        .totals-section {
            margin-top: 20px;
            display: table;
            width: 100%;
        }
        
        .totals-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        
        .totals-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }
        
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            font-size: 9pt;
        }
        
        .totals-table .label {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .totals-table .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .grand-total {
            background-color: #2c5aa0;
            color: white;
            font-size: 14pt;
            font-weight: bold;
        }
        
        .payment-info {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-top: 20px;
        }
        
        .payment-info h3 {
            margin: 0 0 10px 0;
            color: #2c5aa0;
            font-size: 14pt;
        }
        
        .payment-method {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .payment-cash {
            background-color: #28a745;
            color: white;
        }
        
        .payment-card {
            background-color: #007bff;
            color: white;
        }
        
        .payment-momo {
            background-color: #fd7e14;
            color: white;
        }
        
        .payment-insurance {
            background-color: #6f42c1;
            color: white;
        }
        
        .payment-bank {
            background-color: #17a2b8;
            color: white;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            font-size: 8pt;
            color: #6c757d;
        }
        
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        
        .qr-code img {
            width: 80px;
            height: 80px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .status-paid {
            background-color: #28a745;
            color: white;
        }
        
        .status-overdue {
            background-color: #dc3545;
            color: white;
        }
        
        .status-sent {
            background-color: #007bff;
            color: white;
        }
        
        .status-draft {
            background-color: #6c757d;
            color: white;
        }
        
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        
        .status-partial {
            background-color: #ffc107;
            color: #000;
        }
        
        .notes-section {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-top: 20px;
        }
        
        .notes-section h3 {
            margin: 0 0 10px 0;
            color: #2c5aa0;
            font-size: 14pt;
        }
        
        .notes-text {
            line-height: 1.5;
            color: #495057;
        }
    </style>
</head>
<body>
    <!-- Header Section - Cross-Platform Compatible -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                @if(isset($branding['logo_base64']) && $branding['logo_base64'])
                    <div style="margin-bottom: 10px;">
                        <img src="{{ $branding['logo_base64'] }}" alt="{{ $branding['business_name'] ?? 'Logo' }}" style="height: 40px; max-width: 200px; object-fit: contain;" />
                    </div>
                @elseif(isset($branding['logo_absolute_path']) && $branding['logo_absolute_path'] && file_exists($branding['logo_absolute_path']))
                    <div style="margin-bottom: 10px;">
                        <img src="{{ $branding['logo_absolute_path'] }}" alt="{{ $branding['business_name'] ?? 'Logo' }}" style="height: 40px; max-width: 200px; object-fit: contain;" />
                    </div>
                @endif
                <div class="hospital-name">{{ $branding['business_name'] ?? 'Hospital Name' }}</div>
                <div class="hospital-details">
                    {{ $branding['business_address'] ?? 'Hospital Address' }}<br>
                    Tel: {{ $branding['business_phone'] ?? 'Phone Number' }} | 
                    Email: {{ $branding['business_email'] ?? 'Email Address' }}<br>
                    @if(isset($branding['business_website']) && $branding['business_website'])
                        Website: {{ $branding['business_website'] }}
                    @endif
                </div>
            </div>
            <div class="header-right">
                <div style="font-size: 9pt; color: #666;">
                    <strong>Invoice Date:</strong> {{ $generated_at->format('d/m/Y H:i') }}<br>
                    <strong>Branch:</strong> {{ $branch->name ?? 'Main Branch' }}<br>
                    <strong>Invoice #:</strong> {{ $invoice->invoice_number }}<br>
                    <strong>Status:</strong> 
                    <span class="status-badge status-{{ $invoice->status }}">
                        {{ $pdfService->getInvoiceStatusName($invoice->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Title -->
    <div class="invoice-title">
        INVOICE
    </div>

    <!-- Invoice Information -->
    <div class="invoice-info">
        <h3>INVOICE DETAILS</h3>
        <div class="info-grid">
            <div class="info-column">
                <div class="info-row">
                    <span class="info-label">Invoice Number:</span>
                    <span class="info-value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Invoice Date:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Due Date:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Method:</span>
                    <span class="info-value">{{ $pdfService->getPaymentMethodName($invoice->payment_method ?? 'cash') }}</span>
                </div>
            </div>
            <div class="info-column">
                <div class="info-row">
                    <span class="info-label">Patient Name:</span>
                    <span class="info-value">{{ $invoice->patient->first_name }} {{ $invoice->patient->last_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Patient ID:</span>
                    <span class="info-value">{{ $invoice->patient->id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date of Birth:</span>
                    <span class="info-value">{{ $invoice->patient->date_of_birth ? \Carbon\Carbon::parse($invoice->patient->date_of_birth)->format('d/m/Y') : 'Not provided' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Age:</span>
                    <span class="info-value">{{ $pdfService->calculateAge($invoice->patient->date_of_birth) }} years</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Items -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Description</th>
                <th style="width: 15%;">Quantity</th>
                <th style="width: 15%;">Unit Price</th>
                <th style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($invoice->items) && is_array($invoice->items))
                @foreach($invoice->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="item-description">{{ $item['description'] ?? 'Service' }}</td>
                        <td class="item-quantity">{{ $item['quantity'] ?? 1 }}</td>
                        <td class="item-price">{{ $pdfService->formatCurrency($item['unit_price'] ?? 0) }}</td>
                        <td class="item-total">{{ $pdfService->formatCurrency($item['total'] ?? 0) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="5" style="text-align: center; color: #6c757d; font-style: italic; padding: 20px;">
                        No items found
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Totals Section -->
    <div class="totals-section">
        <div class="totals-left">
            @if(isset($invoice->notes) && $invoice->notes)
                <div class="notes-section">
                    <h3>Notes</h3>
                    <div class="notes-text">{{ $invoice->notes }}</div>
                </div>
            @endif
        </div>
        <div class="totals-right">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="amount">{{ $pdfService->formatCurrency($invoice->subtotal ?? 0) }}</td>
                </tr>
                @if(isset($invoice->tax_amount) && $invoice->tax_amount > 0)
                    <tr>
                        <td class="label">Tax:</td>
                        <td class="amount">{{ $pdfService->formatCurrency($invoice->tax_amount) }}</td>
                    </tr>
                @endif
                @if(isset($invoice->discount_amount) && $invoice->discount_amount > 0)
                    <tr>
                        <td class="label">Discount:</td>
                        <td class="amount">-{{ $pdfService->formatCurrency($invoice->discount_amount) }}</td>
                    </tr>
                @endif
                <tr class="grand-total">
                    <td class="label">Total Amount:</td>
                    <td class="amount">{{ $pdfService->formatCurrency($invoice->total_amount) }}</td>
                </tr>
                @if($invoice->payments && $invoice->payments->count() > 0)
                    <tr>
                        <td class="label">Paid Amount:</td>
                        <td class="amount">{{ $pdfService->formatCurrency($invoice->payments->sum('amount')) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Balance:</td>
                        <td class="amount">{{ $pdfService->formatCurrency($invoice->total_amount - $invoice->payments->sum('amount')) }}</td>
                    </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Payment Information -->
    @if($invoice->payments && $invoice->payments->count() > 0)
        <div class="payment-info">
            <h3>PAYMENT HISTORY</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Payment Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->payments as $payment)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                            <td class="item-total">{{ $pdfService->formatCurrency($payment->amount) }}</td>
                            <td>
                                <span class="payment-method payment-{{ $payment->payment_method }}">
                                    {{ $pdfService->getPaymentMethodName($payment->payment_method) }}
                                </span>
                            </td>
                            <td>{{ $payment->reference_number ?? 'N/A' }}</td>
                            <td>
                                <span class="status-badge status-{{ $payment->status ?? 'completed' }}">
                                    {{ ucfirst($payment->status ?? 'completed') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- QR Code for Verification -->
    <div class="qr-code">
        <img src="{{ $pdfService->generateQRCode($invoice->id) }}" alt="QR Code" />
        <div style="font-size: 8pt; color: #6c757d; margin-top: 5px;">
            Scan to verify invoice
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div style="text-align: center;">
            <strong>{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Hospital Name' }}</strong><br>
            {{ $branding['business_address'] ?? $settings['hospital_address'] ?? 'Hospital Address' }}<br>
            Tel: {{ $branding['business_phone'] ?? $settings['hospital_phone'] ?? 'Phone Number' }} | 
            Email: {{ $branding['business_email'] ?? $settings['hospital_email'] ?? 'Email Address' }}<br>
            <em>This invoice was generated on {{ $generated_at->format('d/m/Y H:i') }}</em>
        </div>
    </div>
</body>
</html>
