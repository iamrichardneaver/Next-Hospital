<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page_title }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 landscape;
        }
        
        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.3;
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
            font-size: 8pt;
            color: #666;
            line-height: 1.3;
        }
        
        .report-title {            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 14pt;
            font-weight: bold;
            color: #2c5aa0;
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .period-info {
            background-color: #d1ecf1;
            padding: 10px;
            border: 1px solid #bee5eb;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
            color: #0c5460;
        }
        
        .claims-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 8pt;
        }
        
        .claims-table th,
        .claims-table td {
            border: 1px solid #dee2e6;
            padding: 6px;
            text-align: left;
        }
        
        .claims-table th {
            background-color: #2c5aa0;
            color: white;
            font-weight: bold;
        }
        
        .claims-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .summary-section {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-top: 20px;
        }
        
        .summary-section h3 {
            margin: 0 0 10px 0;
            color: #2c5aa0;
            font-size: 14pt;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
            margin-top: 10px;
        }
        
        .summary-item {
            display: table-cell;
            padding: 8px;
            text-align: center;
            border: 1px solid #dee2e6;
            background-color: white;
        }
        
        .summary-label {
            font-weight: bold;
            color: #666;
            font-size: 8pt;
        }
        
        .summary-value {
            font-size: 14pt;
            font-weight: bold;
            color: #2c5aa0;
            margin-top: 5px;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }
        
        .status-approved {
            background-color: #28a745;
            color: white;
        }
        
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        
        .status-rejected {
            background-color: #dc3545;
            color: white;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            font-size: 7pt;
            color: #666;
            text-align: center;
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
                        <img src="{{ $branding['logo_base64'] }}" alt="{{ $branding['business_name'] ?? 'Logo' }}" style="height: 35px; max-width: 180px; object-fit: contain;" />
                    </div>
                @elseif(isset($branding['logo_absolute_path']) && $branding['logo_absolute_path'] && file_exists($branding['logo_absolute_path']))
                    <div style="margin-bottom: 10px;">
                        <img src="{{ $branding['logo_absolute_path'] }}" alt="{{ $branding['business_name'] ?? 'Logo' }}" style="height: 35px; max-width: 180px; object-fit: contain;" />
                    </div>
                @endif
                <div class="hospital-name">{{ $branding['business_name'] ?? 'Hospital Name' }}</div>
                <div class="hospital-details">
                    {{ $branding['business_address'] ?? 'Hospital Address' }}<br>
                    Tel: {{ $branding['business_phone'] ?? 'Phone Number' }} | 
                    Email: {{ $branding['business_email'] ?? 'Email Address' }}
                </div>
            </div>
            <div class="header-right">
                <div style="font-size: 8pt; color: #666;">
                    <strong>Report Generated:</strong> {{ $generated_at->format('d/m/Y H:i') }}<br>
                    @if($branch)
                        <strong>Branch:</strong> {{ $branch->name }}<br>
                    @endif
                    <strong>Report Type:</strong> NHIS Claims Report
                </div>
            </div>
        </div>
    </div>

    <!-- Report Title -->
    <div class="report-title">
        NATIONAL HEALTH INSURANCE SCHEME (NHIS) CLAIMS REPORT
    </div>

    <!-- Period Information -->
    <div class="period-info">
        Report Period: {{ $period['start'] ?? 'N/A' }} to {{ $period['end'] ?? 'N/A' }}
    </div>

    <!-- Claims Table -->
    <table class="claims-table">
        <thead>
            <tr>
                <th style="width: 3%;">#</th>
                <th style="width: 10%;">Claim Number</th>
                <th style="width: 8%;">Date</th>
                <th style="width: 12%;">Patient ID</th>
                <th style="width: 15%;">Patient Name</th>
                <th style="width: 10%;">NHIS Number</th>
                <th style="width: 15%;">Service Description</th>
                <th style="width: 8%;">Amount (GH₵)</th>
                <th style="width: 7%;">Co-Pay (GH₵)</th>
                <th style="width: 12%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalAmount = 0;
                $totalCoPay = 0;
                $approvedCount = 0;
                $pendingCount = 0;
                $rejectedCount = 0;
            @endphp
            @forelse($claims as $index => $claim)
                @php
                    $totalAmount += $claim->total_amount ?? 0;
                    $totalCoPay += $claim->co_pay_amount ?? 0;
                    
                    if($claim->status == 'approved') $approvedCount++;
                    elseif($claim->status == 'pending') $pendingCount++;
                    elseif($claim->status == 'rejected') $rejectedCount++;
                @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $claim->claim_number ?? 'N/A' }}</td>
                <td>{{ isset($claim->claim_date) ? \Carbon\Carbon::parse($claim->claim_date)->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $claim->patient->patient_id ?? 'N/A' }}</td>
                <td>{{ ($claim->patient->firstname ?? '') . ' ' . ($claim->patient->lastname ?? '') }}</td>
                <td>{{ $claim->patient->nhis_number ?? 'N/A' }}</td>
                <td>{{ $claim->service_description ?? '-' }}</td>
                <td style="text-align: right;">{{ number_format($claim->total_amount ?? 0, 2) }}</td>
                <td style="text-align: right;">{{ number_format($claim->co_pay_amount ?? 0, 2) }}</td>
                <td>
                    <span class="status-badge status-{{ $claim->status ?? 'pending' }}">
                        {{ strtoupper($claim->status ?? 'PENDING') }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align: center; color: #999;">No NHIS claims found for this period</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($claims) > 0)
        <tfoot>
            <tr style="background-color: #e9ecef; font-weight: bold;">
                <td colspan="7" style="text-align: right;">TOTAL:</td>
                <td style="text-align: right;">GH₵ {{ number_format($totalAmount, 2) }}</td>
                <td style="text-align: right;">GH₵ {{ number_format($totalCoPay, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <!-- Summary Section -->
    @if(count($claims) > 0)
    <div class="summary-section">
        <h3>CLAIMS SUMMARY</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Claims</div>
                <div class="summary-value">{{ count($claims) }}</div>
            </div>
            <div class="summary-item" style="background-color: #d4edda;">
                <div class="summary-label">Approved</div>
                <div class="summary-value" style="color: #28a745;">{{ $approvedCount }}</div>
            </div>
            <div class="summary-item" style="background-color: #fff3cd;">
                <div class="summary-label">Pending</div>
                <div class="summary-value" style="color: #856404;">{{ $pendingCount }}</div>
            </div>
            <div class="summary-item" style="background-color: #f8d7da;">
                <div class="summary-label">Rejected</div>
                <div class="summary-value" style="color: #dc3545;">{{ $rejectedCount }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Amount</div>
                <div class="summary-value">GH₵ {{ number_format($totalAmount, 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Co-Pay</div>
                <div class="summary-value">GH₵ {{ number_format($totalCoPay, 2) }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>This is an official NHIS claims report generated by {{ $branding['business_name'] ?? 'Next Hospital' }} computerized system.</p>
        <p>{{ $branding['business_name'] ?? 'Next Hospital' }} | {{ $branding['business_phone'] ?? '' }} | {{ $branding['business_email'] ?? '' }}</p>
    </div>
</body>
</html>

