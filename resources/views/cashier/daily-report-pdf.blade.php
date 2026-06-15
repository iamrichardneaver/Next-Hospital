<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Report - {{ $date }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 20px;
        }
        
        .logo {
            max-width: 100px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        
        .hospital-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 5px;
        }
        
        .hospital-details {
            font-size: 11px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .report-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a5f;
            margin-top: 15px;
        }
        
        .report-date {
            font-size: 14px;
            color: #666;
        }
        
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .stats-row {
            display: table-row;
        }
        
        .stat-box {
            display: table-cell;
            width: 25%;
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
        }
        
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a5f;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a5f;
            margin: 25px 0 15px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th,
        .table td {
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        .table th {
            background-color: #1e3a5f;
            color: white;
            font-weight: bold;
            font-size: 11px;
        }
        
        .table td {
            font-size: 11px;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    @include('components.print-header')

    <div class="report-title">Daily Financial Report</div>
    <div class="report-date">{{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</div>

    <!-- Statistics Summary -->
    <div class="stats-grid">
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-label">Patients Served</div>
                <div class="stat-value">{{ number_format($statistics['total_patients_served'] ?? 0) }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Payments</div>
                <div class="stat-value">{{ number_format($statistics['total_payments'] ?? 0) }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Amount Collected</div>
                <div class="stat-value">GH₵{{ number_format($statistics['total_collected'] ?? 0, 2) }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Outstanding Amount</div>
                <div class="stat-value">GH₵{{ number_format($statistics['outstanding_amount'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Payment Breakdown by Method -->
    @if(isset($paymentBreakdown) && $paymentBreakdown && $paymentBreakdown->count() > 0)
        <div class="section-title">Payment Breakdown by Method</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th class="text-center">Count</th>
                    <th class="text-right">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($paymentBreakdown as $breakdown)
                <tr>
                    <td>{{ \App\Enums\PaymentMethod::labelFor($breakdown->payment_method) }}</td>
                    <td class="text-center">{{ $breakdown->count }}</td>
                    <td class="text-right">GH₵{{ number_format($breakdown->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Recent Payments -->
    @if(isset($recentPayments) && $recentPayments && $recentPayments->count() > 0)
        <div class="section-title">Recent Payments</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Patient Name</th>
                    <th>Invoice Number</th>
                    <th class="text-right">Amount</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentPayments as $payment)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($payment->created_at)->format('H:i') }}</td>
                    <td>{{ $payment->invoice->patient->full_name }}</td>
                    <td>{{ $payment->invoice->invoice_number }}</td>
                    <td class="text-right">GH₵{{ number_format($payment->amount, 2) }}</td>
                    <td>
                        <span class="badge badge-info">{{ \App\Enums\PaymentMethod::labelFor($payment->payment_method) }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Pending Charges Summary -->
    @if(isset($pendingCharges) && is_array($pendingCharges) && count($pendingCharges) > 0)
        <div class="section-title">Pending Charges Summary</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Service Type</th>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingCharges as $charge)
                <tr>
                    <td>
                        <span class="badge badge-warning">{{ ucfirst(str_replace('_', ' ', $charge['type'])) }}</span>
                    </td>
                    <td>{{ $charge['description'] }}</td>
                    <td class="text-right">GH₵{{ number_format($charge['amount'], 2) }}</td>
                    <td>{{ \Carbon\Carbon::parse($charge['date'])->format('M d, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- No Data Message -->
    @if((!isset($recentPayments) || !$recentPayments || $recentPayments->count() == 0) && (!isset($pendingCharges) || !is_array($pendingCharges) || count($pendingCharges) == 0))
        <div class="no-data">
            No payment data available for {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div>Report generated on {{ $generated_at ?? now()->format('Y-m-d H:i:s') }} by {{ $generated_by ?? 'System' }}</div>
        <div>{{ $branding['business_name'] ?? 'Hospital Management System' }} - {{ $branding['business_address'] ?? 'Address not set' }}</div>
        @if(isset($branding['business_website']) && $branding['business_website'])
            <div>Website: {{ $branding['business_website'] }}</div>
        @endif
    </div>
</body>
</html>
