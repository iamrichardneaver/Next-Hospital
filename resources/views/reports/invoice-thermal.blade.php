<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page_title }} - Thermal</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto; /* 80mm width, auto height for thermal printers */
        }
        
        /* Fallback for 58mm thermal printers */
        @media print {
            @page {
                size: 58mm auto;
            }
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.2;
            color: #000;
            margin: 0;
            padding: 5px;
            width: 100%;
            max-width: 80mm;
        }
        
        /* 58mm printer adjustments */
        @media (max-width: 58mm) {
            body {
                font-size: 10px;
                padding: 3px;
            }
        }
        
        /* Header styles are now in the print-header component */
        
        .invoice-info-thermal {
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        
        .invoice-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin: 8px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 4px 0;
        }
        
        .invoice-info {
            margin-bottom: 8px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 10px;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 40%;
        }
        
        .info-value {
            text-align: right;
            flex: 1;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        
        .items-section {
            margin-bottom: 8px;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 10px;
        }
        
        .item-name {
            flex: 2;
            font-weight: bold;
        }
        
        .item-details {
            flex: 1;
            text-align: right;
        }
        
        .item-quantity {
            font-size: 9px;
            color: #666;
        }
        
        .item-price {
            font-weight: bold;
        }
        
        .totals-section {
            border-top: 1px dashed #000;
            padding-top: 6px;
            margin-top: 8px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 11px;
        }
        
        .total-label {
            font-weight: bold;
        }
        
        .total-amount {
            font-weight: bold;
        }
        
        .grand-total {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 6px;
        }
        
        .status-badge {
            background-color: #000;
            color: #fff;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-paid {
            background-color: #28a745;
        }
        
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        
        .status-overdue {
            background-color: #dc3545;
        }
        
        .status-partial {
            background-color: #fd7e14;
        }
        
        .notes-section {
            margin-top: 8px;
            border-top: 1px dashed #000;
            padding-top: 6px;
        }
        
        .notes-title {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .notes-text {
            font-size: 9px;
            line-height: 1.2;
        }
        
        .footer {
            text-align: center;
            font-size: 8px;
            line-height: 1.1;
            margin-top: 8px;
            border-top: 1px dashed #000;
            padding-top: 6px;
        }
        
        .qr-code {
            text-align: center;
            margin: 6px 0;
        }
        
        .qr-code img {
            width: 60px;
            height: 60px;
        }
        
        .payment-info {
            margin-top: 8px;
            border-top: 1px dashed #000;
            padding-top: 6px;
        }
        
        .payment-method {
            background-color: #000;
            color: #fff;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        /* Print-specific styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .no-print {
                display: none !important;
            }
            
            /* Ensure proper spacing for thermal printers */
            .header, .invoice-title, .invoice-info, .items-section, 
            .totals-section, .payment-info, .notes-section, .footer {
                break-inside: avoid;
            }
        }
        
        /* Thermal printer specific adjustments */
        .thermal-print {
            width: 100%;
            max-width: 80mm;
        }
        
        .thermal-58mm {
            max-width: 58mm;
        }
        
        /* Center alignment for thermal printers */
        .center {
            text-align: center;
        }
        
        .text-center {
            text-align: center;
        }
        
        /* Compact spacing for thermal printers */
        .compact {
            margin: 2px 0;
            padding: 1px 0;
        }
    </style>
</head>
<body class="thermal-print {{ $options['printer_width'] === '58mm' ? 'thermal-58mm' : '' }}">
    <!-- Header Section -->
    @include('components.print-header')
    
    <!-- Invoice Information -->
    <div class="invoice-info-thermal">
        <div class="info-row">
            <span class="info-label">Invoice #:</span>
            <span class="info-value">{{ $invoice->invoice_number }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span class="info-value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Due:</span>
            <span class="info-value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Branch:</span>
            <span class="info-value">{{ $branch->name ?? 'Main Branch' }}</span>
        </div>
    </div>

    <!-- Invoice Title -->
    <div class="invoice-title">
        Invoice
    </div>

    <!-- Patient Information -->
    <div class="invoice-info">
        <div class="info-row">
            <span class="info-label">Patient:</span>
            <span class="info-value">{{ $invoice->patient->first_name }} {{ $invoice->patient->last_name }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Patient ID:</span>
            <span class="info-value">{{ $invoice->patient->id }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">DOB:</span>
            <span class="info-value">{{ $invoice->patient->date_of_birth ? \Carbon\Carbon::parse($invoice->patient->date_of_birth)->format('d/m/Y') : 'Not provided' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Age:</span>
            <span class="info-value">{{ $pdfService->calculateAge($invoice->patient->date_of_birth) }} years</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value">
                <span class="status-badge status-{{ $invoice->status }}">
                    {{ $pdfService->getInvoiceStatusName($invoice->status) }}
                </span>
            </span>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Items Section -->
    <div class="items-section">
        @if(isset($invoice->items) && is_array($invoice->items))
            @foreach($invoice->items as $item)
                <div class="item-row">
                    <div class="item-name">{{ $item['description'] ?? 'Service' }}</div>
                    <div class="item-details">
                        <div class="item-quantity">Qty: {{ $item['quantity'] ?? 1 }}</div>
                        <div class="item-price">{{ $pdfService->formatCurrency($item['total'] ?? 0) }}</div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="item-row">
                <div class="item-name">General Service</div>
                <div class="item-details">
                    <div class="item-price">{{ $pdfService->formatCurrency($invoice->total_amount) }}</div>
                </div>
            </div>
        @endif
    </div>

    <div class="divider"></div>

    <!-- Totals Section -->
    <div class="totals-section">
        @if(isset($invoice->subtotal) && $invoice->subtotal != $invoice->total_amount)
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                <span class="total-amount">{{ $pdfService->formatCurrency($invoice->subtotal) }}</span>
            </div>
        @endif
        
        @if(isset($invoice->tax_amount) && $invoice->tax_amount > 0)
            <div class="total-row">
                <span class="total-label">Tax:</span>
                <span class="total-amount">{{ $pdfService->formatCurrency($invoice->tax_amount) }}</span>
            </div>
        @endif
        
        @if(isset($invoice->discount_amount) && $invoice->discount_amount > 0)
            <div class="total-row">
                <span class="total-label">Discount:</span>
                <span class="total-amount">-{{ $pdfService->formatCurrency($invoice->discount_amount) }}</span>
            </div>
        @endif
        
        <div class="total-row grand-total">
            <span class="total-label">TOTAL:</span>
            <span class="total-amount">{{ $pdfService->formatCurrency($invoice->total_amount) }}</span>
        </div>
        
        @if($invoice->payments && $invoice->payments->count() > 0)
            <div class="total-row">
                <span class="total-label">PAID:</span>
                <span class="total-amount">{{ $pdfService->formatCurrency($invoice->payments->sum('amount')) }}</span>
            </div>
            
            @php
                $balance = $invoice->total_amount - $invoice->payments->sum('amount');
            @endphp
            
            @if($balance > 0)
                <div class="total-row">
                    <span class="total-label">BALANCE:</span>
                    <span class="total-amount">{{ $pdfService->formatCurrency($balance) }}</span>
                </div>
            @else
                <div class="total-row">
                    <span class="total-label">STATUS:</span>
                    <span class="total-amount" style="color: #28a745;">PAID</span>
                </div>
            @endif
        @endif
    </div>

    <!-- Payment Information -->
    @if($invoice->payments && $invoice->payments->count() > 0)
        <div class="payment-info">
            <div class="info-row">
                <span class="info-label">Payment Method:</span>
                <span class="info-value">
                    <span class="payment-method">{{ $pdfService->getPaymentMethodName($invoice->payments->first()->payment_method) }}</span>
                </span>
            </div>
            
            @if($invoice->payments->first()->reference_number)
                <div class="info-row">
                    <span class="info-label">Reference:</span>
                    <span class="info-value">{{ $invoice->payments->first()->reference_number }}</span>
                </div>
            @endif
            
            <div class="info-row">
                <span class="info-label">Payment Date:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($invoice->payments->first()->payment_date)->format('d/m/Y') }}</span>
            </div>
        </div>
    @else
        <div class="payment-info">
            <div class="info-row">
                <span class="info-label">Payment Method:</span>
                <span class="info-value">
                    <span class="payment-method">{{ $pdfService->getPaymentMethodName($invoice->payment_method ?? 'cash') }}</span>
                </span>
            </div>
        </div>
    @endif

    <!-- Notes Section -->
    @if(isset($invoice->notes) && $invoice->notes)
        <div class="notes-section">
            <div class="notes-title">Notes:</div>
            <div class="notes-text">{{ $invoice->notes }}</div>
        </div>
    @endif

    <!-- QR Code -->
    <div class="qr-code">
        <img src="{{ $pdfService->generateQRCode($invoice->id) }}" alt="QR Code" />
        <div style="font-size: 8px;">Scan to verify</div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>{{ $branding['business_name'] ?? 'Hospital Name' }}</div>
        <div>{{ $branding['business_address'] ?? 'Hospital Address' }}</div>
        <div>Tel: {{ $branding['business_phone'] ?? 'Phone Number' }}</div>
        <div>Generated: {{ $generated_at->format('d/m/Y H:i') }}</div>
    </div>
</body>
</html>
