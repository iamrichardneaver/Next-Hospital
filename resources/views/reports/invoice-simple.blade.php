<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page_title ?? 'Invoice' }}</title>
    <style>
        @page {
            margin: 10mm;
            size: A4 portrait;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        /* Header styles are now in the print-header component */
        
        .invoice-title {
            font-size: 20pt;
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
            color: #2c5aa0;
            display: inline-block;
            width: 120px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #dee2e6;
            padding: 6px;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c5aa0;
        }
        
        .totals-section {
            margin-top: 20px;
            text-align: right;
        }
        
        .total-row {
            margin-bottom: 5px;
        }
        
        .total-label {
            display: inline-block;
            width: 150px;
            font-weight: bold;
        }
        
        .total-amount {
            display: inline-block;
            width: 100px;
            text-align: right;
        }
        
        .grand-total {
            font-size: 14pt;
            font-weight: bold;
            color: #2c5aa0;
            border-top: 2px solid #2c5aa0;
            padding-top: 5px;
            margin-top: 10px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    @include('components.print-header')

    <!-- Invoice Title -->
    <div class="invoice-title">INVOICE</div>

    <!-- Invoice Information -->
    <div class="invoice-info">
        <div class="info-grid">
            <div class="info-column">
                <div class="info-row">
                    <span class="info-label">Invoice #:</span>
                    {{ $invoice->invoice_number }}
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') : 'N/A' }}
                </div>
                <div class="info-row">
                    <span class="info-label">Due Date:</span>
                    {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : 'N/A' }}
                </div>
            </div>
            <div class="info-column">
                <div class="info-row">
                    <span class="info-label">Patient:</span>
                    {{ $invoice->patient->first_name }} {{ $invoice->patient->last_name }}
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    {{ $invoice->patient->phone ?? 'N/A' }}
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    {{ ucfirst($invoice->status) }}
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
            @php
                $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
                $items = $items ?: [];
                
                // Recalculate totals to ensure accuracy
                foreach ($items as &$item) {
                    $quantity = floatval($item['quantity'] ?? 1);
                    $unitPrice = floatval($item['unit_price'] ?? 0);
                    $item['total'] = $quantity * $unitPrice;
                }
            @endphp
            @if(is_array($items) && count($items) > 0)
                @foreach($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['description'] ?? $item['name'] ?? 'Service' }}</td>
                        <td>{{ $item['quantity'] ?? 1 }}</td>
                        <td>{{ $pdfService->formatCurrency($item['unit_price'] ?? 0) }}</td>
                        <td>{{ $pdfService->formatCurrency($item['total'] ?? 0) }}</td>
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
    @php
        // Recalculate totals from items for accuracy
        $calculatedSubtotal = 0;
        foreach ($items as $item) {
            $calculatedSubtotal += floatval($item['total'] ?? 0);
        }
        $calculatedTotal = $calculatedSubtotal + ($invoice->tax_amount ?? 0) - ($invoice->discount_amount ?? 0);
    @endphp
    <div class="totals-section">
        <div class="total-row">
            <span class="total-label">Subtotal:</span>
            <span class="total-amount">{{ $pdfService->formatCurrency($calculatedSubtotal) }}</span>
        </div>
        @if(($invoice->tax_amount ?? 0) > 0)
        <div class="total-row">
            <span class="total-label">Tax:</span>
            <span class="total-amount">{{ $pdfService->formatCurrency($invoice->tax_amount ?? 0) }}</span>
        </div>
        @endif
        @if(($invoice->discount_amount ?? 0) > 0)
        <div class="total-row">
            <span class="total-label">Discount:</span>
            <span class="total-amount">-{{ $pdfService->formatCurrency($invoice->discount_amount ?? 0) }}</span>
        </div>
        @endif
        <div class="total-row grand-total">
            <span class="total-label">Total:</span>
            <span class="total-amount">{{ $pdfService->formatCurrency($calculatedTotal) }}</span>
        </div>
    </div>

    @if($invoice->notes)
    <div style="margin-top: 20px;">
        <h3>Notes</h3>
        <p>{{ $invoice->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>Generated on {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
