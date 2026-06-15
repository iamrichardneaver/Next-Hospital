<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page_title }}</title>
    <style>
        @page {
            margin: 15mm;
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
        
        .report-title {            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            font-size: 23pt;
            font-weight: bold;
            color: #2c5aa0;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border: 2px solid #2c5aa0;
            border-radius: 8px;
        }
        
        .qc-summary {
            background-color: #e7f3ff;
            padding: 20px;
            border: 2px solid #007bff;
            margin-bottom: 25px;
            border-radius: 8px;
        }
        
        .qc-summary h3 {
            margin: 0 0 15px 0;
            color: #007bff;
            font-size: 14pt;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
        }
        
        .summary-column {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 10px;
            border-right: 1px solid #007bff;
        }
        
        .summary-column:last-child {
            border-right: none;
        }
        
        .summary-value {
            font-size: 23pt;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-size: 9pt;
            color: #495057;
            font-weight: bold;
        }
        
        .qc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .qc-table th {
            background-color: #2c5aa0;
            color: white;
            font-weight: bold;
            padding: 12px 8px;
            text-align: left;
            border: 1px solid #2c5aa0;
            font-size: 9pt;
        }
        
        .qc-table td {
            padding: 10px 8px;
            border: 1px solid #dee2e6;
            font-size: 9pt;
            vertical-align: top;
        }
        
        .qc-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .status-pass {
            background-color: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8pt;
        }
        
        .status-fail {
            background-color: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8pt;
        }
        
        .status-warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8pt;
        }
        
        .trend-chart {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
        }
        
        .trend-chart h4 {
            margin: 0 0 15px 0;
            color: #2c5aa0;
            font-size: 14pt;
        }
        
        .chart-placeholder {
            background-color: white;
            border: 2px dashed #dee2e6;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }
        
        .equipment-section {
            background-color: #fff3cd;
            padding: 20px;
            border: 2px solid #ffc107;
            margin: 25px 0;
            border-radius: 8px;
        }
        
        .equipment-section h3 {
            margin: 0 0 15px 0;
            color: #856404;
            font-size: 14pt;
            border-bottom: 2px solid #ffc107;
            padding-bottom: 10px;
        }
        
        .equipment-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .equipment-table th {
            background-color: #ffc107;
            color: #856404;
            font-weight: bold;
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #ffc107;
            font-size: 9pt;
        }
        
        .equipment-table td {
            padding: 8px;
            border: 1px solid #ffc107;
            font-size: 9pt;
            vertical-align: top;
        }
        
        .calibration-due {
            background-color: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8pt;
        }
        
        .calibration-ok {
            background-color: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8pt;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            font-size: 8pt;
            color: #6c757d;
            text-align: center;
        }
        
        .signature-section {
            margin-top: 30px;
            display: table;
            width: 100%;
        }
        
        .signature-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 20px 10px;
            border: 1px solid #dee2e6;
            margin: 0 5px;
            border-radius: 6px;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 8px;
            height: 40px;
        }
        
        .signature-label {
            font-size: 9pt;
            color: #6c757d;
            font-weight: bold;
        }
        
        .signature-name {
            font-size: 8pt;
            color: #495057;
            margin-top: 5px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                @if(isset($branding['logo_base64']) && $branding['logo_base64'])
                    <div style="margin-bottom: 10px;">
                        <img src="{{ $branding['logo_base64'] }}" alt="{{ $branding['business_name'] ?? 'Logo' }}" style="height: 40px; max-width: 200px; object-fit: contain;" />
                    </div>
                @endif
                <div class="hospital-name">{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Hospital Name' }}</div>
                <div class="hospital-details">
                    {{ $branding['business_address'] ?? $settings['hospital_address'] ?? 'Hospital Address' }}<br>
                    Tel: {{ $branding['business_phone'] ?? $settings['hospital_phone'] ?? 'Phone Number' }} | 
                    Email: {{ $branding['business_email'] ?? $settings['hospital_email'] ?? 'Email Address' }}<br>
                    @if(isset($branding['business_website']) && $branding['business_website'])
                        Website: {{ $branding['business_website'] }}
                    @elseif(isset($settings['hospital_website']))
                        Website: {{ $settings['hospital_website'] }}
                    @endif
                </div>
            </div>
            <div class="header-right">
                <div style="font-size: 9pt; color: #666;">
                    <strong>Report Date:</strong> {{ $generated_at->format('d/m/Y H:i') }}<br>
                    <strong>Branch:</strong> {{ $branch->name ?? 'Main Branch' }}<br>
                    <strong>Report ID:</strong> QC-{{ now()->format('YmdHis') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Report Title -->
    <div class="report-title">
        QUALITY CONTROL REPORT
    </div>

    <!-- QC Summary -->
    <div class="qc-summary">
        <h3>QUALITY CONTROL SUMMARY</h3>
        <div class="summary-grid">
            <div class="summary-column">
                <div class="summary-value">{{ $qcRecords->count() }}</div>
                <div class="summary-label">Total Tests</div>
            </div>
            <div class="summary-column">
                <div class="summary-value">{{ $qcRecords->where('status', 'pass')->count() }}</div>
                <div class="summary-label">Passed</div>
            </div>
            <div class="summary-column">
                <div class="summary-value">{{ $qcRecords->where('status', 'fail')->count() }}</div>
                <div class="summary-label">Failed</div>
            </div>
            <div class="summary-column">
                <div class="summary-value">{{ number_format(($qcRecords->where('status', 'pass')->count() / max($qcRecords->count(), 1)) * 100, 1) }}%</div>
                <div class="summary-label">Pass Rate</div>
            </div>
        </div>
    </div>

    <!-- QC Results Table -->
    @if($qcRecords->count() > 0)
        <table class="qc-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 20%;">Test Parameter</th>
                    <th style="width: 15%;">QC Level</th>
                    <th style="width: 15%;">Expected Value</th>
                    <th style="width: 15%;">Actual Value</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 10%;">Bias %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($qcRecords as $record)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($record->test_date)->format('d/m/Y') }}</td>
                        <td>{{ $record->parameter_name }}</td>
                        <td>{{ $record->qc_level }}</td>
                        <td>{{ $record->expected_value }} {{ $record->unit }}</td>
                        <td>{{ $record->actual_value }} {{ $record->unit }}</td>
                        <td>
                            @if($record->status === 'pass')
                                <span class="status-pass">PASS</span>
                            @elseif($record->status === 'fail')
                                <span class="status-fail">FAIL</span>
                            @else
                                <span class="status-warning">WARNING</span>
                            @endif
                        </td>
                        <td>{{ number_format($record->bias_percentage, 2) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            No quality control records found for the selected period.
        </div>
    @endif

    <!-- Trend Chart Placeholder -->
    <div class="trend-chart">
        <h4>QC TREND ANALYSIS</h4>
        <div class="chart-placeholder">
            QC Trend Chart would be displayed here<br>
            (Chart generation requires additional charting library)
        </div>
    </div>

    <!-- Equipment Calibration Status -->
    <div class="equipment-section">
        <h3>EQUIPMENT CALIBRATION STATUS</h3>
        <table class="equipment-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Equipment</th>
                    <th style="width: 20%;">Serial Number</th>
                    <th style="width: 15%;">Last Calibration</th>
                    <th style="width: 15%;">Next Due</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 10%;">Days Left</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $equipment = collect([
                        (object)[
                            'name' => 'Automated Analyzer',
                            'serial' => 'AA-001',
                            'last_calibration' => now()->subDays(30),
                            'next_due' => now()->addDays(5),
                            'status' => 'due_soon'
                        ],
                        (object)[
                            'name' => 'Microscope',
                            'serial' => 'MIC-002',
                            'last_calibration' => now()->subDays(60),
                            'next_due' => now()->addDays(30),
                            'status' => 'ok'
                        ],
                        (object)[
                            'name' => 'Centrifuge',
                            'serial' => 'CEN-003',
                            'last_calibration' => now()->subDays(90),
                            'next_due' => now()->addDays(10),
                            'status' => 'ok'
                        ]
                    ]);
                @endphp
                
                @foreach($equipment as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->serial }}</td>
                        <td>{{ $item->last_calibration->format('d/m/Y') }}</td>
                        <td>{{ $item->next_due->format('d/m/Y') }}</td>
                        <td>
                            @if($item->status === 'due_soon')
                                <span class="calibration-due">DUE SOON</span>
                            @else
                                <span class="calibration-ok">OK</span>
                            @endif
                        </td>
                        <td>{{ $item->next_due->diffInDays(now()) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Quality Control Manager</div>
            <div class="signature-name">_________________________</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Laboratory Director</div>
            <div class="signature-name">_________________________</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Quality Assurance Officer</div>
            <div class="signature-name">_________________________</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>
            <strong>{{ $settings['hospital_name'] ?? 'Hospital Name' }}</strong><br>
            {{ $settings['hospital_address'] ?? 'Hospital Address' }} | 
            Tel: {{ $settings['hospital_phone'] ?? 'Phone Number' }} | 
            Email: {{ $settings['hospital_email'] ?? 'Email Address' }}<br>
            <em>This quality control report was generated on {{ $generated_at->format('d/m/Y H:i') }} and covers the period from {{ $qcRecords->min('test_date') ? \Carbon\Carbon::parse($qcRecords->min('test_date'))->format('d/m/Y') : 'N/A' }} to {{ $qcRecords->max('test_date') ? \Carbon\Carbon::parse($qcRecords->max('test_date'))->format('d/m/Y') : 'N/A' }}.</em>
        </div>
    </div>
</body>
</html>
