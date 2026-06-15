<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Insurance Analytics Report - {{ $hospital_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .hospital-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .hospital-details {
            font-size: 11px;
            color: #666;
            line-height: 1.3;
        }
        
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
            color: #333;
        }
        
        .report-meta {
            text-align: right;
            font-size: 10px;
            color: #666;
            margin-bottom: 30px;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #007bff;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: center;
            background-color: #f8f9fa;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 11px;
        }
        
        .table td {
            font-size: 10px;
        }
        
        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        
        .financial-summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .financial-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .financial-label {
            font-weight: bold;
        }
        
        .financial-value {
            color: #007bff;
            font-weight: bold;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="hospital-name">{{ $hospital_name }}</div>
        <div class="hospital-details">
            @if($hospital_address)
                {{ $hospital_address }}<br>
            @endif
            @if($hospital_phone)
                Phone: {{ $hospital_phone }}<br>
            @endif
            @if($hospital_email)
                Email: {{ $hospital_email }}
            @endif
        </div>
    </div>

    <div class="report-title">Insurance Analytics Report</div>
    
    <div class="report-meta">
        Generated on: {{ $generated_at }}<br>
        Report Type: {{ ucfirst($report_type) }}
    </div>

    <!-- Overview Statistics -->
    <div class="section">
        <div class="section-title">Overview Statistics</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">{{ $analytics['overview']['total_providers'] ?? 0 }}</div>
                <div class="stat-label">Insurance Providers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $analytics['overview']['total_policies'] ?? 0 }}</div>
                <div class="stat-label">Active Policies</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $analytics['overview']['total_claims'] ?? 0 }}</div>
                <div class="stat-label">Total Claims</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $analytics['overview']['total_pre_authorizations'] ?? 0 }}</div>
                <div class="stat-label">Pre-Authorizations</div>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="section">
        <div class="section-title">Financial Summary</div>
        <div class="financial-summary">
            <div class="financial-item">
                <span class="financial-label">Total Claim Amount:</span>
                <span class="financial-value">₵{{ number_format($analytics['financial']['total_claim_amount'] ?? 0, 2) }}</span>
            </div>
            <div class="financial-item">
                <span class="financial-label">Total Covered Amount:</span>
                <span class="financial-value">₵{{ number_format($analytics['financial']['total_covered_amount'] ?? 0, 2) }}</span>
            </div>
            <div class="financial-item">
                <span class="financial-label">Total Co-pay Amount:</span>
                <span class="financial-value">₵{{ number_format($analytics['financial']['total_co_pay_amount'] ?? 0, 2) }}</span>
            </div>
            <div class="financial-item">
                <span class="financial-label">Average Coverage Percentage:</span>
                <span class="financial-value">{{ number_format($analytics['financial']['average_coverage_percentage'] ?? 0, 1) }}%</span>
            </div>
        </div>
    </div>

    <!-- Claims Status Distribution -->
    <div class="section">
        <div class="section-title">Claims Status Distribution</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Percentage</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="status-badge status-approved">Approved</span></td>
                    <td>{{ $analytics['claims']['approved'] ?? 0 }}</td>
                    <td>{{ $analytics['claims']['approved_percentage'] ?? 0 }}%</td>
                    <td>₵{{ number_format($analytics['claims']['approved_amount'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td><span class="status-badge status-pending">Pending</span></td>
                    <td>{{ $analytics['claims']['pending'] ?? 0 }}</td>
                    <td>{{ $analytics['claims']['pending_percentage'] ?? 0 }}%</td>
                    <td>₵{{ number_format($analytics['claims']['pending_amount'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td><span class="status-badge status-rejected">Rejected</span></td>
                    <td>{{ $analytics['claims']['rejected'] ?? 0 }}</td>
                    <td>{{ $analytics['claims']['rejected_percentage'] ?? 0 }}%</td>
                    <td>₵{{ number_format($analytics['claims']['rejected_amount'] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Provider Performance -->
    @if(isset($analytics['providers']['performance']) && count($analytics['providers']['performance']) > 0)
    <div class="section page-break">
        <div class="section-title">Provider Performance</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Provider</th>
                    <th>Total Claims</th>
                    <th>Approval Rate</th>
                    <th>Avg Processing Time</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analytics['providers']['performance'] as $provider)
                <tr>
                    <td>{{ $provider['name'] ?? 'N/A' }}</td>
                    <td>{{ $provider['total_claims'] ?? 0 }}</td>
                    <td>{{ number_format($provider['approval_rate'] ?? 0, 1) }}%</td>
                    <td>{{ $provider['avg_processing_days'] ?? 0 }} days</td>
                    <td>₵{{ number_format($provider['total_amount'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Pre-Authorization Summary -->
    <div class="section">
        <div class="section-title">Pre-Authorization Summary</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">{{ $analytics['pre_authorizations']['pending'] ?? 0 }}</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $analytics['pre_authorizations']['approved'] ?? 0 }}</div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $analytics['pre_authorizations']['rejected'] ?? 0 }}</div>
                <div class="stat-label">Rejected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ number_format($analytics['pre_authorizations']['approval_rate'] ?? 0, 1) }}%</div>
                <div class="stat-label">Approval Rate</div>
            </div>
        </div>
    </div>

    <!-- Recent High-Value Claims -->
    @if(isset($analytics['claims']['recent_high_value']) && count($analytics['claims']['recent_high_value']) > 0)
    <div class="section page-break">
        <div class="section-title">Recent High-Value Claims</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Provider</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analytics['claims']['recent_high_value'] as $claim)
                <tr>
                    <td>{{ $claim['patient_name'] ?? 'N/A' }}</td>
                    <td>{{ $claim['provider_name'] ?? 'N/A' }}</td>
                    <td>₵{{ number_format($claim['total_amount'] ?? 0, 2) }}</td>
                    <td>
                        <span class="status-badge status-{{ $claim['status'] ?? 'unknown' }}">
                            {{ ucfirst($claim['status'] ?? 'Unknown') }}
                        </span>
                    </td>
                    <td>{{ $claim['submitted_date'] ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        This report was generated automatically by the {{ $hospitalBranding['name'] ?? 'Hospital' }} Insurance Management System
    </div>
</body>
</html>
