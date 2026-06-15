<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $type }} Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .hospital-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 5px;
        }
        .hospital-address {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a5f;
        }
        .receipt-details {
            margin-bottom: 20px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px dotted #ddd;
        }
        .detail-label {
            font-weight: bold;
            color: #333;
        }
        .detail-value {
            color: #666;
        }
        .amount-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .amount-row {
            display: flex;
            justify-content: space-between;
            font-size: 16px;
            font-weight: bold;
            color: #1e3a5f;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-partial {
            background-color: #cce5ff;
            color: #004085;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <x-receipt-header :type="$type . ' Receipt'" />

        <div class="receipt-details">
            <div class="detail-row">
                <span class="detail-label">Receipt #:</span>
                <span class="detail-value">{{ $item->id ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value">{{ $date->format('d/m/Y H:i') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Patient:</span>
                <span class="detail-value">{{ $patient->first_name }} {{ $patient->last_name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Patient ID:</span>
                <span class="detail-value">{{ $patient->patient_number ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Doctor:</span>
                <span class="detail-value">{{ $doctor->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Service:</span>
                <span class="detail-value">{{ $type }}</span>
            </div>
            @if(isset($item->description))
            <div class="detail-row">
                <span class="detail-label">Description:</span>
                <span class="detail-value">{{ $item->description }}</span>
            </div>
            @endif
        </div>

        <div class="amount-section">
            <div class="amount-row">
                <span>Total Amount:</span>
                <span>GH₵ {{ number_format($amount, 2) }}</span>
            </div>
        </div>

        <x-receipt-footer />
    </div>
</body>
</html>
