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
        
        .hospital-name {            font-family: 'Inter', 'DejaVu Sans', sans-serif;
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
        
        .receipt-title {
            font-size: 23pt;
            font-weight: bold;
            color: #2c5aa0;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .receipt-info {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        
        .receipt-info h3 {
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
        
        .payment-summary {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .payment-summary h3 {
            margin: 0 0 10px 0;
            color: #155724;
            font-size: 14pt;
        }
        
        .payment-details {
            display: table;
            width: 100%;
        }
        
        .payment-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        
        .payment-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }
        
        .total-amount {
            font-size: 14pt;
            font-weight: bold;
            color: #155724;
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
        
        .thank-you {
            text-align: center;
            font-size: 14pt;
            color: #2c5aa0;
            font-weight: bold;
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    @include('components.print-header')
    
    <!-- Receipt Information -->
    <div class="receipt-info-right">
        <div style="font-size: 9pt; color: #666; text-align: right;">
            <strong>Receipt Date:</strong> {{ $generated_at->format('d/m/Y H:i') }}<br>
            <strong>Branch:</strong> {{ $branch->name ?? 'Main Branch' }}<br>
            <strong>Receipt #:</strong> {{ $invoice->invoice_number }}<br>
            <strong>Status:</strong> 
            <span style="background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 8pt;">
                PAID
            </span>
        </div>
    </div>

    <!-- Receipt Title -->
    <div class="receipt-title">
        PAYMENT RECEIPT
    </div>

    <!-- Receipt Information -->
    <div class="receipt-info">
        <h3>RECEIPT DETAILS</h3>
        <div class="info-grid">
            <div class="info-column">
                <div class="info-row">
                    <span class="info-label">Receipt Number:</span>
                    <span class="info-value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Receipt Date:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Date:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($invoice->updated_at)->format('d/m/Y H:i') }}</span>
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
            </div>
        </div>
    </div>

    <!-- Payment Summary -->
    <div class="payment-summary">
        <h3>PAYMENT SUMMARY</h3>
        <div class="payment-details">
            <div class="payment-left">
                <div class="info-row">
                    <span class="info-label">Total Amount:</span>
                    <span class="info-value">{{ $pdfService->formatCurrency($invoice->total_amount) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Amount Paid:</span>
                    <span class="info-value">{{ $pdfService->formatCurrency($invoice->payments->sum('amount')) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Balance:</span>
                    <span class="info-value">{{ $pdfService->formatCurrency($invoice->total_amount - $invoice->payments->sum('amount')) }}</span>
                </div>
            </div>
            <div class="payment-right">
                <div class="total-amount">
                    {{ $pdfService->formatCurrency($invoice->payments->sum('amount')) }}
                </div>
                <div style="font-size: 9pt; color: #155724;">
                    Total Paid
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

    <!-- Payment History -->
    @if($invoice->payments && $invoice->payments->count() > 0)
        <div class="receipt-info">
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
                                <span style="background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 8pt;">
                                    {{ $pdfService->getPaymentMethodName($payment->payment_method) }}
                                </span>
                            </td>
                            <td>{{ $payment->reference_number ?? 'N/A' }}</td>
                            <td>
                                <span style="background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 8pt;">
                                    {{ ucfirst($payment->status ?? 'completed') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Thank You Message -->
    <div class="thank-you">
        Thank you for your payment!<br>
        <span style="font-size: 14pt; color: #6c757d;">Please keep this receipt for your records.</span>
    </div>

    <!-- QR Code for Verification -->
    <div class="qr-code">
        <img src="{{ $pdfService->generateQRCode($invoice->id) }}" alt="QR Code" />
        <div style="font-size: 8pt; color: #6c757d; margin-top: 5px;">
            Scan to verify receipt
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div style="text-align: center;">
            <strong>{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Hospital Name' }}</strong><br>
            {{ $branding['business_address'] ?? $settings['hospital_address'] ?? 'Hospital Address' }}<br>
            Tel: {{ $branding['business_phone'] ?? $settings['hospital_phone'] ?? 'Phone Number' }} | 
            Email: {{ $branding['business_email'] ?? $settings['hospital_email'] ?? 'Email Address' }}<br>
            <em>This receipt was generated on {{ $generated_at->format('d/m/Y H:i') }}</em>
        </div>
    </div>
</body>
</html>
